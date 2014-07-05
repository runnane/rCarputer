<?php

/***************************************/
/* Settings                            */
/***************************************/

$accepted_ssids = array("tungland","fwg");
define("SQLITE_FILE", "/ssd/db/rcarputer.db");

/***************************************/
/* Helper functions                    */
/***************************************/

function ping($host, $port, $timeout) { 
  $tB = microtime(true); 
  $fP = @fSockOpen($host, $port, $errno, $errstr, $timeout); 
  if (!$fP) { return false; } 
  $tA = microtime(true); 
  return round((($tA - $tB) * 1000), 0)." ms"; 
}

function logit($text){
 $msecparts = explode(" ",microtime());
 $msec = substr($msecparts[0],2,6);
 file_put_contents("/ssd/log/rcarputer.log", date("Y-m-d H:i:s") . "." . $msec .  " gpsuploader: " . $text . "\n", FILE_APPEND | LOCK_EX );
}

/***************************************/
/* Code starting here                  */
/***************************************/

logit("Initializing");

$createdb = "create table if not exists gpslog(time TEXT, speed TEXT, lat TEXT, lon TEXT, alt TEXT, extra TEXT, time2 TEXT, epv TEXT, ept TEXT, track TEXT, climb TEXT, distance TEXT);";
$db = new SQLite3(SQLITE_FILE);
if(!$db){
	logit("Could not connect to local db, aborting.");
	die();
}
logit("Connected to local SQLite db");
$db->query($createdb);
$db->close();

// Main loop
while(1){
	$ssid = exec("/sbin/iwgetid -r");
	logit("Connected to SSID={$ssid}");
	if(!in_array($ssid, $accepted_ssids)){
		logit("Not connected to a known SSID, sleeping 60 sec");
		sleep(60);
		continue;
	}

	$r = ping("172.20.100.2","22","1000");
	if($r === false){
		logit("... No internet/LAN access, sleeping 60 sec");
		sleep(60);
		continue;
	}
	$r = connectAndSyncDB();
	if($r == 100){
		logit("Sleeping 1 sec since we have more rows in queue ... ");
		sleep(1);
	
	}else{
		logit("Sleeping 10 sec ...");
		sleep(10);
	}
	continue;
}

function connectAndSyncDB() {
	logit("Trying to sync db ...");
	$localdb = new SQLite3(SQLITE_FILE);
	if(!$localdb){
		logit("Could not connect to local db... breaking.");
		return;
	}
	$remotedb = new MySQLi("172.20.100.2","gps","b4y4vy4tbyrty","gps");
	if(!$remotedb){
		logit("Could not connect to remote db... breaking.");
		$localdb->close();
		return;
	}
	$localdb->busyTimeout(10000);

	logit("Syncing...");
	$num = syncDB($localdb, $remotedb);
	logit("Synced {$num} rows");
	$localdb->close();
	$remotedb->close();
	return $num;
}

function syncDB($localdb, $remotedb){
	logit("Sync starting");
	$query = "SELECT rowid, * FROM gpslog ORDER BY time2 LIMIT 100";
	$result = $localdb->query($query);
	if(!isset($result)){
		logit("Not possible to get local records, breaking");
		return -1;
	}
	$count=0;
	while($rowf = $result->fetchArray(SQLITE3_ASSOC)){
		$query = "INSERT INTO gps.gpslog (
			time,time2,lat,lon,speed,alt,extra,epv,ept,track,climb,distance
			) VALUES (
			'{$rowf['time']}','{$rowf['time2']}','{$rowf['lat']}','{$rowf['lon']}','{$rowf['speed']}','{$rowf['alt']}','{$rowf['extra']}','{$rowf['epv']}','{$rowf['ept']}','{$rowf['track']}','{$rowf['climb']}','{$rowf['distance']}'
			)";
		if($remotedb->query($query)){
			$count++;
			$last=$rowf['rowid'];
		}else{
			logit("Failed to insert on remote server, skipping.");
			break;
		}
	}
	if(isset($last)){
		$query = "DELETE FROM gpslog WHERE rowid<='{$last}'";
		$localdb->exec($query);
	}
	return $count;

}

?>
