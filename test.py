import config
import pandas as pd
from sqlalchemy import create_engine

def main():
  engine = create_engine(config.sqlalchemy_uri)
  sql = "SELECT meter_id, GROUP_CONCAT(value) AS value_csv, GROUP_CONCAT(recorded) AS recorded_csv FROM meter_data GROUP BY meter_id"
  df = pd.read_sql_query(sql, engine)
  print(df)

main()