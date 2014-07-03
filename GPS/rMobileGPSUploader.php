<?php

function ping($host, $port, $timeout) { 
  $tB = microtime(true); 
  $fP = @fSockOpen($host, $port, $errno, $errstr, $timeout); 
  if (!$fP) { return false; } 
  $tA = microtime(true); 
  return round((($tA - $tB) * 1000), 0)." ms"; 
}

$accepted_ssids = array("tungland");

$createdb = "create table if not exists gpslog(time TEXT, speed TEXT, lat TEXT, lon TEXT, alt TEXT, extra TEXT, time2 TEXT, epv TEXT, ept TEXT, track TEXT, climb TEXT, distance TEXT);";
$db = new SQLite3("/var/tmp/gps.db");
if(!$db){
	die("Could not connect to local db... breaking.\n");
}
$db->query($createdb);
$db->close();

while(1){
	$ssid = exec("iwgetid -r");
	echo "connected to SSID={$ssid}\n";
	if(!in_array($ssid, $accepted_ssids)){
		echo "Not connected to a known SSID, sleeping\n";
		sleep(60);
		continue;
	}

	$r = ping("172.20.100.2","22","1000");
	if($r === false){
		echo "... No internet/LAN access, sleeping 10 sec\n";
		sleep(60);
		continue;
	}
	$r = connectAndSyncDB();
	if($r == 100){
		echo "Sleeping 1 sec since we have more rows in queue ... \n";
		sleep(1);
	
	}else{
		echo "Sleeping 60 sec ... \n";
		sleep(10);
	}
	continue;
}

function connectAndSyncDB() {
	echo "Trying to sync db ... \n";
	$localdb = new SQLite3("/var/tmp/gps.db");
	if(!$localdb){
		echo "Could not connect to local db... breaking.\n";
		return;
	}
	$remotedb = new MySQLi("172.20.100.2","gps","b4y4vy4tbyrty","gps");
	if(!$remotedb){
		echo "Could not connect to remote db... breaking.\n";
		$localdb->close();
		return;
	}
	$localdb->busyTimeout(10000);

	echo "Syncing...\n";
	$num = syncDB($localdb, $remotedb);
	echo "Synced {$num} rows \n";
	$localdb->close();
	$remotedb->close();
	return $num;
}

function syncDB($localdb, $remotedb){
	$query = "SELECT rowid, * FROM gpslog ORDER BY time2 LIMIT 100";
	$result = $localdb->query($query);
	if(!isset($result)){
		echo "Not possible to get local records, breaking\n";
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
			echo "Failed to insert on remote server, skipping.\n";
			break;
		}
		//if($count%50==0){
		//	echo "Waiting 1 sec for other processes\n";
		//	sleep(2);
		//}
	}
	if(isset($last)){
		$query = "DELETE FROM gpslog WHERE rowid<='{$last}'";
		$localdb->exec($query);
		echo "Deleting: {$query}\n";
	}
	return $count;

}

?>
