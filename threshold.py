import config
from sqlalchemy import create_engine

import pandas as pd
import numpy as np

import matplotlib
import seaborn
import matplotlib.dates as md
from matplotlib import pyplot as plt

from sklearn import preprocessing
from sklearn.decomposition import PCA
from sklearn.cluster import KMeans
from sklearn.covariance import EllipticEnvelope
from sklearn.ensemble import IsolationForest
from sklearn.svm import OneClassSVM
# from pyemma import msm


def main():
    engine = create_engine(config.sqlalchemy_uri)
    sql = "SELECT value, recorded FROM meter_data WHERE meter_id = 464 AND resolution = 'quarterhour' AND value IS NOT NULL ORDER BY recorded ASC"
    df = pd.read_sql_query(sql, engine)
    df['recorded'] = pd.to_datetime(df['recorded'], unit='s')
    df['hours'] = df['recorded'].dt.hour
    df['daylight'] = ((df['hours'] >= 7) & (df['hours'] <= 22)).astype(int)
    # the day of the week (Monday=0, Sunday=6) and if it's a week end day or week day.
    df['DayOfTheWeek'] = df['recorded'].dt.dayofweek
    df['WeekDay'] = (df['DayOfTheWeek'] < 5).astype(int)
    # An estimation of anomly population of the dataset (necessary for several algorithm)
    outliers_fraction = 0.01
    df['time_epoch'] = (df['recorded'].astype(np.int64) / 100000000000).astype(np.int64)
    # creation of 4 distinct categories that seem useful (week end/day week & night/day)
    df['categories'] = df['WeekDay'] * 2 + df['daylight']
    df['leak'] = 0

    print(df.head())

def 
main()
