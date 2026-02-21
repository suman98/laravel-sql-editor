This the the database URL which need to correct in [file](/python/get_sql_response.py)

dont take DATABASE_URL from env but make DATABASE_URL look at env 
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nepsealpha
DB_USERNAME=suman
DB_PASSWORD=toot


also DB_CONNECTION can be mysql  too adjust
from database.session import DATABASE_URL

```python
import os
from dotenv import load_dotenv
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

# Load environment variables from .env file
load_dotenv()

# Read database config from environment variables
DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_DATABASE = os.getenv('DB_DATABASE', 'postgres')
DB_USERNAME = os.getenv('DB_USERNAME', 'postgres')
DB_PASSWORD = os.getenv('DB_PASSWORD', '')
DB_PORT = os.getenv('DB_PORT', '5432')

# Construct the database URL
DATABASE_URL = f"postgresql://{DB_USERNAME}:{DB_PASSWORD}@{DB_HOST}:{DB_PORT}/{DB_DATABASE}"

# Create SQLAlchemy engine with read-only mode
engine = create_engine(DATABASE_URL, execution_options={"readonly": True})

# Create a configured "Session" class
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

# Create a session instance
db_session = SessionLocal()

# Example usage: Test connection
try:
    with engine.connect().execution_options(readonly=True) as connection:
        pass
except Exception as e:
    print(f"Error connecting to database: {e}")
    raise e

```
