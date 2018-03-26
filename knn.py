import pymysql.cursors
import config
def main():
    db = pymysql.connect(host="159.89.232.129", port=3306, user=config.username, password=config.password, db="envs356", autocommit=True)
    cur = db.cursor()

    cur.execute("SELECT id FROM meters")
    for meters in cur.fetchall():
        cur.execute("SELECT value FROM meter_data WHERE meter_id = 316 AND resolution = 'hour' ORDER BY recorded DESC",
                    (int(meter[0]), res))
