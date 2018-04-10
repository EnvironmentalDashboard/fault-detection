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
    sql = "SELECT value, recorded FROM meter_data WHERE meter_id = 343 AND resolution = 'quarterhour' AND value IS NOT NULL ORDER BY recorded ASC"
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

    a = df.loc[df['categories'] == 0, 'value']
    b = df.loc[df['categories'] == 1, 'value']
    c = df.loc[df['categories'] == 2, 'value']
    d = df.loc[df['categories'] == 3, 'value']

    fig, ax = plt.subplots()
    a_heights, a_bins = np.histogram(a)
    b_heights, b_bins = np.histogram(b, bins=a_bins)
    c_heights, c_bins = np.histogram(c, bins=a_bins)
    d_heights, d_bins = np.histogram(d, bins=a_bins)

    width = (a_bins[1] - a_bins[0]) / 6

    ax.bar(a_bins[:-1], a_heights * 100 / a.count(), width=width, facecolor='blue', label='WeekEndNight')
    ax.bar(b_bins[:-1] + width, (b_heights * 100 / b.count()), width=width, facecolor='green', label='WeekEndLight')
    ax.bar(c_bins[:-1] + width * 2, (c_heights * 100 / c.count()), width=width, facecolor='red', label='WeekDayNight')
    ax.bar(d_bins[:-1] + width * 3, (d_heights * 100 / d.count()), width=width, facecolor='black', label='WeekDayLight')

    plt.legend()
    plt.show()
    print(df.head())
    print(df.info())
    # print(df.loc['value' == 0])


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
