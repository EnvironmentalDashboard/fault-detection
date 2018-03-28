import config
import pandas as pd
from sqlalchemy import create_engine

def main():
  engine = create_engine(config.sqlalchemy_uri)
  sql = "SELECT meter_id, value, recorded FROM meter_data ORDER BY meter_id"
  df = pd.read_sql_query(sql, engine)

main()