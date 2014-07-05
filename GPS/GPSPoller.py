import gps
import os
import sqlite3
import datetime
from math import radians, cos, sin, asin, sqrt

def logit(text):
	with open("/ssd/log/rcarputer.log","a+") as fp:
		fp.write(str(datetime.datetime.now()) + " gpspoller: " + text + "\n")	

# grabbed from http://stackoverflow.com/questions/4913349/haversine-formula-in-python-bearing-and-distance-between-two-gps-points (Michael Dunn)
def haversine(lon1, lat1, lon2, lat2):
    lon1, lat1, lon2, lat2 = map(radians, [lon1, lat1, lon2, lat2])
    dlon = lon2 - lon1 
    dlat = lat2 - lat1 
    a = sin(dlat/2)**2 + cos(lat1) * cos(lat2) * sin(dlon/2)**2
    c = 2 * asin(sqrt(a)) 
    km = 6367 * c
    return km 

logit("Initializing")

# Connect to local GPSD
session = gps.gps("localhost", "2947")
session.stream(gps.WATCH_ENABLE | gps.WATCH_NEWSTYLE)
logit("Connected to GPSD")

# Connnect to local SQLite
db = sqlite3.connect('/ssd/db/rcarputer.db', 10)
cursor = db.cursor()
logit("Connected to SQLite db")

createdb = 'create table if not exists gpslog(time TEXT, speed TEXT, lat TEXT, lon TEXT, alt TEXT, extra TEXT, time2 TEXT, epv TEXT, ept TEXT, track TEXT, climb TEXT, distance TEXT);'

cursor.execute(createdb)
db.commit()

attribs = ['time', 'speed', 'alt', 'lat', 'lon', 'epv', 'ept', 'track', 'climb']

# Loop through GPS data
logit("Initializated, starting to read data")
while True:
	try:
		report = session.next()
		if report['class'] == 'TPV' and hasattr(report, 'lat') and hasattr(report,'lon'):
			distance=-1;
			if 'prevreport' in locals():
				distance = haversine(report.lon, report.lat, prevreport.lon, prevreport.lat) * 1000
				logit("Moved " + str(distance) + " m from " + str(prevreport.lon) + "," + str(prevreport.lat) + " to " + str(report.lon) + "," + str(report.lat))

			if distance > 0 and distance < 10:
				continue;

			sqlnames = "time2,distance"
			sqlvalues = "datetime(),'" + str(distance) + "'"
			
			log = "added";
			for attrib in attribs:
				if hasattr(report, attrib):
					sqlnames += ',' + attrib
					sqlvalues += ",'" + str(report[attrib])  + "'"
					log += "\n                                      " + str(attrib) + ': ' + str(report[attrib])
			logit(log)

			sql = "INSERT INTO gpslog (" + sqlnames + ") VALUES (" + sqlvalues + ")"
			cursor.execute(sql)
			db.commit()
			prevreport = report;

	except KeyError:
		pass
	except KeyboardInterrupt:
		logit("Stopping")
		quit()
	except StopIteration:
		session = None
		logit("Stopping, GPSD has terminated")
