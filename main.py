import config
from sqlalchemy import *
import threshold as th

def main():
	engine = create_engine(config.sqlalchemy_uri)
	con = engine.connect()
	ids = con.execute("SELECT id FROM users")
	for user in ids:
		stmt = text("SELECT meter_id FROM active_meters WHERE user_id = :id")
		meters = con.execute(stmt, id=user['id']);
		for meter in meters:
			result = water_leak(con, meter['meter_id'])
			if result[0]:
				notify(con, user['id'], result)
	con.close()

# returns an array where first param is whether or not to send an email, second is some indication of how much water has been lost
def water_leak(con, meter_id):
	df, peak_th, base_th = th.threshold(464)
	return [false, 'rest of data']

def notify(con, user_id, data):
	stmt = text("SELECT email FROM notified_emails WHERE user_id = :id")
	recipients = con.execute(stmt, id=user_id)
	for recipient in recipients:
		stmt = text("INSERT INTO outbox (recipient, subject, message) VALUES (:email, :subj, :msg)")
		con.execute(stmt, email=recipient['email'], subj='Water leak found', msg='Water leak found')

main()
