generateQueryFromTitle

Replace this method with this script using symphony process and add the response in the sql editor 


```python
import os
from langchain_openai import ChatOpenAI
from langchain_community.utilities.sql_database import SQLDatabase
from langchain_experimental.sql import SQLDatabaseChain
from dotenv import load_dotenv
from src.utils import debugIt
from langchain.prompts import PromptTemplate
from src.database.session import DATABASE_URL

# Default prompt template for SQLDatabaseChain
DEFAULT_PROMPT_TEMPLATE = """
You are a database financial assistant. You can answer questions using only the following table(s): {table_info}.
Only write SQL queries using the allowed tables: {table_info}.
- Do not hallucinate about tables or fields that do not exist.
- Try to answer best possible answer using the table(s) {table_info}

User Question: {input}
Response in plain text:
"""

def get_sql_response(payload):
    """
    Accepts a dict payload.
    payload = {
        "user_question": "<user's question or input>",
        "prompt_template": "<optional custom prompt template as a string>"
    }
    Returns: string
    """
    load_dotenv()

    api_key = os.getenv("OPEN_AI_API_KEY")
    if not api_key:
        raise ValueError("OpenAI API key not found in environment variables")
    os.environ["OPENAI_API_KEY"] = api_key

    # Specify the tables that can be used
    allowed_tables = ["web_today_price"]
    db = SQLDatabase.from_uri(DATABASE_URL, include_tables=allowed_tables)

    # Prepare LLM
    llm = ChatOpenAI(temperature=0, model="gpt-3.5-turbo")

    user_question = "Which stock has the highest change to close positive today"
    custom_prompt = None

    # Select prompt template
    if custom_prompt and isinstance(custom_prompt, str) and custom_prompt.strip():
        prompt = PromptTemplate.from_template(custom_prompt)
    else:
        prompt = PromptTemplate.from_template(DEFAULT_PROMPT_TEMPLATE)

    db_chain = SQLDatabaseChain.from_llm(
        llm,
        db,
        verbose=True,
        use_query_checker=True,
        top_k=1,
        prompt=prompt,
        return_intermediate_steps=False
    )

    try:
        response = db_chain.run(user_question)
    except Exception as e:
        response = f"I cannot answer this question."

    return response

# For legacy/test purposes, support old main() signature
def main(payload):
    result = get_sql_response(payload)
    print(result)
    return result

```