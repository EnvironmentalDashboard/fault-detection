import time
import config
import threshold as th
from sqlalchemy import *

def main():
	engine = create_engine(config.sqlalchemy_uri)
	con = engine.connect()
	ids = con.execute("SELECT id FROM users")
	for user in ids:
		stmt = text("SELECT meter_id FROM active_meters WHERE user_id = :id AND meter_id IN (SELECT id FROM meters WHERE last_leak = 0)")
		meters = con.execute(stmt, id=user['id']);
		for meter in meters:
			result = water_leak(con, meter['meter_id'])
			if result[0]:
				notify(con, user['id'], meter['meter_id'], result)
	con.close()

# returns an array where first param is whether or not to send an email, second is some indication of how much water has been lost
def water_leak(con, meter_id):
	df, peak_th, base_th = th.threshold(meter_id)
	return [false, 'rest of data']

def notify(con, user_id, meter_id, data):
	stmt = text("UPDATE meters SET last_leak = UNIX_TIMESTAMP() WHERE id = :id")
	con.execute(stmt, id=meter_id)
	stmt = text("SELECT email FROM notified_emails WHERE user_id = :id")
	recipients = con.execute(stmt, id=user_id)
	stmt = text("SELECT name FROM buildings WHERE bos_id IN (SELECT building_id FROM meters WHERE id = :id)")
	meter_name = con.execute(stmt, id=meter_id) + ' '
	stmt = text("SELECT name FROM meters WHERE id = :id")
	meter_name += con.execute(stmt, id=meter_id)
	start_date = 0
	base64_ts = ''
	now = time.strftime('%A, %b %d at %H:%M', time.localtime())
	html = (
		'<p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">'
		meter_name+' appears to be leaking. From '+start_date+' to '+now+', this meter has consumed '+actual_consumption+' gallons when this meter typically only consumes '+typical_consumption+' gallons over the same time span.'
		'This excess water has cost an estimated $'+((actual_consumption - typical_consumption)*0.0015)+'. Below is a time series of consumption:</p>'
		'<img src="'+base64_ts+'" alt="" style="width: 100%">'
	)
	for recipient in recipients:
		stmt = text("INSERT INTO outbox (recipient, subject, message) VALUES (:email, :subj, :msg)")
		con.execute(stmt, email=recipient['email'], subj='Water leak found', msg=html)

main()
