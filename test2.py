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
from pyemma import msm


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

    print(df.value.describe())
    print("===============pre standardized============")
    print(df.head())
    data = standardize(df)
    print("===============post standardized============")
    print(data.head())
    print(data.value.describe())

    qlist = defineStates(data)
    df_mm = markovStates(df,qlist)

    # getting the anomaly labels for our dataset (evaluating sequence of 5 values and anomaly = less than 20% probable)
    df_anomaly = markovAnomaly(df_mm, 5, 0.20)
    df_anomaly = pd.Series(df_anomaly)
    print(df_anomaly.value_counts())

def standardize(df):
    data = df[['value', 'hours', 'daylight', 'DayOfTheWeek', 'WeekDay']]
    min_max_scaler = preprocessing.StandardScaler()
    np_scaled = min_max_scaler.fit_transform(data[['value']])
    temp_df = pd.DataFrame(np_scaled)
    data['value'] = temp_df[0]
    return data


def defineStates(df):
    q2 = df.value.quantile(0.2)
    q4 = df.value.quantile(0.4)
    q6 = df.value.quantile(0.6)
    q8 = df.value.quantile(0.8)
    return q2,q4,q6,q8


def markovStates(df, qlist):
    # definition of the different state
    print(qlist)
    x1 = (df['value'] <= qlist[0]).astype(int)
    x2 = ((df['value'] > qlist[0]) & (df['value'] <= qlist[1])).astype(int)
    x3 = ((df['value'] > qlist[1]) & (df['value'] <= qlist[2])).astype(int)
    x4 = ((df['value'] > qlist[2]) & (df['value'] <= qlist[3])).astype(int)
    x5 = (df['value'] > qlist[3]).astype(int)
    df_mm = x1 + 2 * x2 + 3 * x3 + 4 * x4 + 5 * x5
    return df_mm

def markovAnomaly(df, windows_size, threshold):
    transition_matrix = getTransitionMatrix(df)
    real_threshold = threshold ** windows_size
    df_anomaly = []
    for j in range(0, len(df)):
        if (j < windows_size):
            df_anomaly.append(0)
        else:
            sequence = df[j - windows_size:j]
            sequence = sequence.reset_index(drop=True)
            df_anomaly.append(anomalyElement(sequence, real_threshold, transition_matrix))
    return df_anomaly


# return the success probability of the state change
def successProbabilityMetric(state1, state2, transition_matrix):
    proba = 0
    for k in range(0, len(transition_matrix)):
        if (k != (state2 - 1)):
            proba += transition_matrix[state1 - 1][k]
    return 1 - proba


# return the success probability of the whole sequence
def sucessScore(sequence, transition_matrix):
    proba = 0
    for i in range(1, len(sequence)):
        if (i == 1):
            proba = successProbabilityMetric(sequence[i - 1], sequence[i], transition_matrix)
        else:
            proba = proba * successProbabilityMetric(sequence[i - 1], sequence[i], transition_matrix)
    return proba


# return if the sequence is an anomaly considering a threshold
def anomalyElement(sequence, threshold, transition_matrix):
    if (sucessScore(sequence, transition_matrix) > threshold):
        return 0
    else:
        return 1


# train markov model to get transition matrix
def getTransitionMatrix(df):
    df = np.array(df)
    model = msm.estimate_markov_model(df, 1)
    return model.transition_matrix


main()
