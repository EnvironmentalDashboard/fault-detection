import config
from sqlalchemy import create_engine
import pandas as pd
from datetime import timedelta,date



def threshold(meterid):

    engine = create_engine(config.sqlalchemy_uri)
    sql = "SELECT value, recorded FROM meter_data WHERE meter_id = meterid AND resolution = 'quarterhour' AND value IS NOT NULL ORDER BY recorded ASC"
    df = pd.read_sql_query(sql, engine)
    df['recorded'] = pd.to_datetime(df['recorded'], unit='s')
    df['hours'] = df['recorded'].dt.hour.astype(str)
    df['date'] = df['recorded'].dt.date.astype(str)
    df['datehour'] = df['date']+':'+df['hours']
    # the day of the week (Monday=0, Sunday=6) and if it's a week end day or week day.
    # An estimation of anomly population of the dataset (necessary for several algorithm)
    outliers_fraction = 0.01
    # creation of 4 distinct categories that seem useful (week end/day week & night/day)
    df['leak'] = 0
    Q3 = 0.75
    Q1 = 0.25
    # print(df.head(10))
    df, base_thres = detect_baseload(df, Q3, Q1)
    df, peak_thres = detect_peakload(df, Q3, Q1)
    print(df.leak.sum())
    return df, peak_thres, base_thres

def detect_peakload(df,q3,q1):
    hourly_df = df.groupby('datehour').mean()
    Q3 = hourly_df.value.quantile(q3)
    Q1 = hourly_df.value.quantile(q1)
    IQR = Q3 - Q1
    peak_thres = Q3 + IQR
    outliers = hourly_df[hourly_df.value > peak_thres]
    for idx, row in outliers.iterrows():
        df.loc[df['datehour'] == idx,'leak'] = 1

    return df, peak_thres


def detect_baseload(df, q3, q1):
    start_date = date(2017,1,1)
    stop_date = date(2017,12,31)
    bl_list = []
    while start_date < stop_date:
        temp = df[df['date'] == str(start_date)]
        temp = temp.groupby('datehour').mean()
        baseload = temp.value.min()
        base_idx = temp.value.idxmin()
        bl_list.append([baseload,base_idx])
        start_date = start_date + timedelta(days = 1)

    bl_df = pd.DataFrame(bl_list, columns= list('ab'))
    Q3 = bl_df['a'].quantile(q3)
    Q1 = bl_df['a'].quantile(q1)
    IQR = Q3 - Q1
    base_thres = Q3 + IQR
    outliers = bl_df[bl_df.a > base_thres]
    for idx, row in outliers.iterrows():
        df.loc[df['datehour'] == idx,'leak'] = 1
    return df, base_thres
