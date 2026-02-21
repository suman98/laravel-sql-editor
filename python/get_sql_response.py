#!/usr/bin/env python3
"""
LangChain-based SQL query generator using OpenAI GPT-3.5-turbo.
Takes natural language input and generates SQL queries based on database schema.
"""

import json
import sys
import os
from dotenv import load_dotenv
from langchain_openai import ChatOpenAI
from langchain_community.utilities import SQLDatabase
from langchain_community.agent_toolkits import SQLDatabaseToolkit
from langchain.agents import create_sql_agent
from langchain.agents.agent_types import AgentType

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

def get_sql_response(payload):
    """
    Generate SQL from natural language using LangChain and OpenAI.
    
    Args:
        payload (dict): Dictionary with keys:
            - user_question: Natural language query description
            - prompt_template: Optional custom prompt template
    
    Returns:
        str: Generated SQL query
    """
    user_question = payload.get('user_question', '').strip()
    if not user_question:
        raise ValueError('user_question is required')
    
    # Get database configuration from environment variables
    database_url = get_database_url()
    
    openai_api_key = os.getenv('OPENAI_API_KEY')
    if not openai_api_key:
        raise ValueError('OPENAI_API_KEY environment variable not set')
    
    try:
        # Initialize database connection
        db = SQLDatabase.from_uri(database_url)
        
        # Initialize LLM
        llm = ChatOpenAI(
            api_key=openai_api_key,
            model='gpt-3.5-turbo',
            temperature=0
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
        response = agent.run(user_question)
        
        # Extract SQL from response (agent may return explanation + SQL)
        # Try to extract just the SQL portion
        sql = response.strip()
        
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
    
    try:
        sql = get_sql_response(payload)
        print(sql)
    except Exception as e:
        print(json.dumps({'error': str(e)}), file=sys.stderr)
        sys.exit(1)

if __name__ == '__main__':
    main()
