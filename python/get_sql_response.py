#!/usr/bin/env python3
"""
LangChain-based SQL query generator using OpenAI GPT-3.5-turbo.
Takes natural language input and generates SQL queries based on database schema.
Includes table access control for security.
"""

import json
import sys
import os
import re
from dotenv import load_dotenv
from langchain_openai import ChatOpenAI
from langchain_community.utilities import SQLDatabase
from langchain_community.agent_toolkits import SQLDatabaseToolkit
from langchain.agents import create_sql_agent
from langchain.agents.agent_types import AgentType
from sqlalchemy import inspect, create_engine

MAX_TABLES = 20
MAX_STRING_LENGTH = 100
SAMPLE_ROWS_IN_TABLE_INFO = 0

# Load environment variables
load_dotenv()

def get_database_url():
    """
    Construct database URL from individual environment variables.
    Supports PostgreSQL (pgsql) and MySQL connections.
    
    Returns:
        str: Database URL compatible with SQLAlchemy
    """
    db_connection = os.getenv('DB_CONNECTION', 'pgsql')
    db_host = os.getenv('DB_HOST', 'localhost')
    db_port = os.getenv('DB_PORT', '5432' if db_connection == 'pgsql' else '3306')
    db_database = os.getenv('DB_DATABASE', '')
    db_username = os.getenv('DB_USERNAME', '')
    db_password = os.getenv('DB_PASSWORD', '')
    
    if not db_database:
        raise ValueError('DB_DATABASE environment variable not set')
    if not db_username:
        raise ValueError('DB_USERNAME environment variable not set')
    
    # Construct database URL based on connection type
    if db_connection == 'mysql':
        # MySQL connection string: mysql+pymysql://user:password@host:port/database
        database_url = f"mysql+pymysql://{db_username}:{db_password}@{db_host}:{db_port}/{db_database}"
    else:
        # PostgreSQL connection string (default): postgresql://user:password@host:port/database
        database_url = f"postgresql://{db_username}:{db_password}@{db_host}:{db_port}/{db_database}"
    
    return database_url

def get_available_tables(database_url):
    """
    Get all available tables from the database.
    
    Args:
        database_url (str): Database URL connection string
    
    Returns:
        list: List of table names available in the database
    """
    try:
        engine = create_engine(database_url)
        inspector = inspect(engine)
        tables = inspector.get_table_names()
        return tables
    except Exception as e:
        raise RuntimeError(f'Failed to retrieve database tables: {str(e)}')

def get_tables_list():
    """
    Retrieve and return all available tables in the database.
    This function is called to populate the multi-select dropdown for table selection.
    
    Returns:
        dict: JSON response with list of available tables
    """
    try:
        database_url = get_database_url()
        tables = get_available_tables(database_url)
        return {
            'success': True,
            'tables': tables,
            'count': len(tables)
        }
    except Exception as e:
        return {
            'success': False,
            'error': str(e),
            'tables': [],
            'count': 0
        }

def extract_table_references(user_question):
    """
    Extract potential table name references from user question.
    Looks for common SQL patterns like "from table", "in table", "table contains", etc.
    
    Args:
        user_question (str): Natural language user question
    
    Returns:
        list: List of potential table names mentioned in the question
    """
    # Convert to lowercase for pattern matching but preserve original for later use
    question_lower = user_question.lower()
    
    # Simple patterns to extract table names
    # This is a heuristic approach - in practice, more sophisticated NLP might be needed
    patterns = [
        r'from\s+(\w+)',           # from table_name
        r'in\s+(\w+)',             # in table_name
        r'from\s+the\s+(\w+)',     # from the table_name
        r'in\s+the\s+(\w+)',       # in the table_name
        r'table\s+(\w+)',          # table table_name
        r'(\w+)\s+table',          # table_name table
    ]
    
    mentioned_tables = []
    for pattern in patterns:
        matches = re.findall(pattern, question_lower)
        mentioned_tables.extend(matches)
    
    # Remove duplicates while preserving order
    mentioned_tables = list(dict.fromkeys(mentioned_tables))
    return mentioned_tables

def validate_table_access(user_question, allowed_tables):
    """
    Validate that the user question only references allowed tables.
    
    Args:
        user_question (str): Natural language user question
        allowed_tables (list): List of tables the user is allowed to query
    
    Returns:
        tuple: (is_valid: bool, unauthorized_tables: list, error_message: str)
    """
    if not allowed_tables:
        return True, [], None
    
    mentioned_tables = extract_table_references(user_question)
    
    if not mentioned_tables:
        # If no tables are explicitly mentioned, allow it
        # (LangChain will infer from context)
        return True, [], None
    
    # Convert to lowercase for comparison
    allowed_tables_lower = [t.lower() for t in allowed_tables]
    unauthorized = [t for t in mentioned_tables if t.lower() not in allowed_tables_lower]
    
    if unauthorized:
        error_msg = f'Access denied to table(s): {", ".join(unauthorized)}. Allowed tables: {", ".join(allowed_tables)}'
        return False, unauthorized, error_msg
    
    return True, [], None

def get_sql_response(payload):
    """
    Generate SQL from natural language using LangChain and OpenAI.
    
    Args:
        payload (dict): Dictionary with keys:
            - user_question: Natural language query description (required)
            - selected_tables: List of table names user selected to query (optional)
                              If not provided, all available tables will be used
            - allowed_tables: List of table names user can query (optional, for access control)
                             Ignored if selected_tables provided
            - prompt_template: Optional custom prompt template
    
    Returns:
        str: Generated SQL query
    """
    user_question = payload.get('user_question', '').strip()
    if not user_question:
        raise ValueError('user_question is required')
    
    # Get database configuration from environment variables
    database_url = get_database_url()
    
    # Prioritize selected_tables over allowed_tables
    # User explicitly selected these tables from the UI
    selected_tables = payload.get('selected_tables', None)
    allowed_tables = payload.get('allowed_tables', None)
    
    # Determine which tables to use
    tables_to_query = None
    
    if selected_tables:
        # User explicitly selected specific tables
        if not isinstance(selected_tables, list):
            raise ValueError('selected_tables must be a list')
        if not selected_tables:
            raise ValueError('selected_tables cannot be empty')
        tables_to_query = selected_tables
    elif allowed_tables:
        # Use allowed_tables if provided
        tables_to_query = allowed_tables
    else:
        # If neither provided, get all available tables from database
        try:
            tables_to_query = get_available_tables(database_url)
        except Exception as e:
            raise RuntimeError(f'Failed to retrieve database tables: {str(e)}')
    
    # Validate that user question only references tables from tables_to_query
    is_valid, unauthorized_tables, error_msg = validate_table_access(user_question, tables_to_query)
    if not is_valid:
        raise ValueError(error_msg)

    if len(tables_to_query) > MAX_TABLES:
        raise ValueError(
            f'Too many tables selected ({len(tables_to_query)}). '
            f'Please select {MAX_TABLES} or fewer tables.'
        )
    
    openai_api_key = os.getenv('OPENAI_API_KEY')
    if not openai_api_key:
        raise ValueError('OPENAI_API_KEY environment variable not set')
    
    try:
        # Initialize LLM first (before database connection)
        llm = ChatOpenAI(
            api_key=openai_api_key,
            model='gpt-3.5-turbo',
            temperature=0
        )
        
        # Initialize database connection with only selected/allowed tables
        db = SQLDatabase.from_uri(
            database_url,
            include_tables=tables_to_query,
            sample_rows_in_table_info=SAMPLE_ROWS_IN_TABLE_INFO,
            max_string_length=MAX_STRING_LENGTH
        )
        
        # Create SQL agent
        toolkit = SQLDatabaseToolkit(db=db, llm=llm)
        agent = create_sql_agent(
            llm=llm,
            toolkit=toolkit,
            agent_type=AgentType.ZERO_SHOT_REACT_DESCRIPTION,
            verbose=False,
            handle_parsing_errors=True
        )
        
        # Generate SQL
        # The agent will understand the natural language and convert to SQL
        response = agent.invoke({"input": user_question})
        
        # Extract SQL from response (agent may return explanation + SQL)
        # Try to extract just the SQL portion
        sql = response.get("output", "").strip()
        
        # Clean up the response if it contains explanation text
        if 'SELECT' in sql.upper():
            # Find the first occurrence of SELECT
            idx = sql.upper().find('SELECT')
            if idx > 0:
                sql = sql[idx:]
        
        return sql
    
    except Exception as e:
        raise RuntimeError(f'Failed to generate SQL: {str(e)}')

def main():
    """Main entry point for CLI execution."""
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'Payload argument required'}), file=sys.stderr)
        sys.exit(1)
    
    try:
        payload = json.loads(sys.argv[1])
    except json.JSONDecodeError as e:
        print(json.dumps({'error': f'Invalid JSON payload: {str(e)}'}), file=sys.stderr)
        sys.exit(1)
    
    # Check if this is a request to get available tables
    action = payload.get('action', 'generate_query')
    
    try:
        if action == 'get_tables':
            # Return list of all available tables in database
            result = get_tables_list()
            print(json.dumps(result))
        else:
            # Default action: generate SQL query from natural language
            sql = get_sql_response(payload)
            print(sql)
    except Exception as e:
        print(json.dumps({'error': str(e)}), file=sys.stderr)
        sys.exit(1)

if __name__ == '__main__':
    main()
