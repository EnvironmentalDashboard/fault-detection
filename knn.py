import pymysql.cursors
import config 
db = pymysql.connect(host="159.89.232.129", port=3306, user=config.username, password=config.password, db="envs356", autocommit=True)
cur = db.cursor()

cur.execute("SELECT id FROM meters")