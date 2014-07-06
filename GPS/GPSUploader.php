<?php

/***************************************/
/* Settings                            */
/***************************************/

require_once("settings.php");

/***************************************/
/* Helper functions                    */
/***************************************/

require_once("common.php");

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

	$localdb = new SQLite3(SQLITE_FILE);
	if(!$localdb){
		logit("Could not connect to local db... breaking.");
		return;
	}
	$localdb->busyTimeout(10000);

	// check if we have rows to sync	
	$p = post("ping",false);
	if(!is_object($p) || $p->result != "pong"){
		logit("... no REST access, sleeping 10 sec");
		$localdb->close();
		sleep(10);
		continue;
	}
	logit("Connected to REST interface");

	logit("Syncing rows...");
	$r = syncDB($localdb);
	$localdb->close();
	logit("Synced {$num} rows");
	if($r == 100){
		logit("Sleeping 1 sec since we have more rows in queue ... ");
		sleep(1);
	
	}else{
		logit("Sleeping 10 sec ...");
		sleep(10);
	}
	continue;
}

function syncDB($localdb){
	logit("Sync starting");
	$query = "SELECT rowid, * FROM gpslog ORDER BY rowid LIMIT 100";
	$result = $localdb->query($query);
	if(!isset($result)){
		logit("Not possible to get local records, breaking");
		return -1;
	}
	$count=0;
	$return = array("rows" => array());
	while($return["rows"][] = $result->fetchArray(SQLITE3_ASSOC)){
		$count++;
	}

	$result = post("SaveGPS",json_encode($return));
	if(!is_object($result)){
		logit("no object returned from rest, aborting");
		return 0;
	}
	if(!isset($result->result)){
		logit("no object result inner object returned from rest, aborting");
		return 0;
	}
	if($result->result->count > 0){
		logit("Will delete {$result->result->count} rows from local db");
		$query = "DELETE FROM gpslog WHERE rowid<='{$result->result->max}' AND rowid >='{$result->result->min}' LIMIT {$result->result->count}";
		$localdb->exec($query);
		logit($query);
		
	}
	

	return $result->result->count;

}

?>
