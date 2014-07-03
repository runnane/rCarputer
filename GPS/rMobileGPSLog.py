import gps
import os
import MySQLdb
import sqlite3
from math import radians, cos, sin, asin, sqrt

# grabbed from http://stackoverflow.com/questions/4913349/haversine-formula-in-python-bearing-and-distance-between-two-gps-points (Michael Dunn)

def haversine(lon1, lat1, lon2, lat2):
    """
    Calculate the great circle distance between two points 
    on the earth (specified in decimal degrees)
    """
    # convert decimal degrees to radians 
    lon1, lat1, lon2, lat2 = map(radians, [lon1, lat1, lon2, lat2])

    # haversine formula 
    dlon = lon2 - lon1 
    dlat = lat2 - lat1 
    a = sin(dlat/2)**2 + cos(lat1) * cos(lat2) * sin(dlon/2)**2
    c = 2 * asin(sqrt(a)) 

    # 6367 km is the radius of the Earth
    km = 6367 * c
    return km 
 
session = gps.gps("localhost", "2947")
session.stream(gps.WATCH_ENABLE | gps.WATCH_NEWSTYLE)

db = sqlite3.connect('/var/tmp/gps.db',10)
cursor = db.cursor()

createdb = 'create table if not exists gpslog(time TEXT, speed TEXT, lat TEXT, lon TEXT, alt TEXT, extra TEXT, time2 TEXT, epv TEXT, ept TEXT, track TEXT, climb TEXT, distance TEXT);'

cursor.execute(createdb)
db.commit()

attribs = ['time', 'speed', 'alt', 'lat', 'lon', 'epv', 'ept', 'track', 'climb']
while True:
	try:
		report = session.next()
		if report['class'] == 'TPV' and hasattr(report, 'lat') and hasattr(report,'lon'):
			print '+-----------------------------+'
			distance=9999999;
			if 'prevreport' in locals():
				distance = haversine(report.lon, report.lat, prevreport.lon, prevreport.lat) * 1000
				print "Moved ", distance, " m"
				print " from " , prevreport.lon ,",", prevreport.lat
				print " to " , report.lon ,",", report.lat
			if distance < 5:
				continue;
			sqlnames = "time2,distance"
			sqlvalues = "datetime(),'" + str(distance) + "'"
			
			for attrib in attribs:
				if hasattr(report, attrib):
					sqlnames += ',' + attrib
					sqlvalues += ",'" + str(report[attrib])  + "'"
					print attrib + ': ', report[attrib]

			print '+-----------------------------+'
			sql = "INSERT INTO gpslog (" + sqlnames + ") VALUES (" + sqlvalues + ")"
			
			print sql
			
			cursor.execute(sql)
			db.commit()
			#print report
			prevreport = report;
	except KeyError:
		pass
	except KeyboardInterrupt:
		quit()
	except StopIteration:
		session = None
		print "GPSD has terminated"
