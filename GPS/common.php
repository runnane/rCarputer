<?php

/***************************************/
/* Helper functions                    */
/***************************************/

function ping2($host, $port, $timeout) { 
  $tB = microtime(true); 
  $fP = @fSockOpen($host, $port, $errno, $errstr, $timeout); 
  if (!$fP) { return false; } 
  $tA = microtime(true); 
  return round((($tA - $tB) * 1000), 0)." ms"; 
}

function ping($host, $timeout = 1) {
	$package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
	$socket  = socket_create(AF_INET, SOCK_RAW, 1);
	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
	socket_connect($socket, $host, null);

	$ts = microtime(true);
	socket_send($socket, $package, strLen($package), 0);
	if (socket_read($socket, 255)){
		$result = microtime(true) - $ts;
        }else{
		$result = false;
	}
	socket_close($socket);

	return $result;
}

function logit($text){
 $msecparts = explode(" ",microtime());
 $msec = substr($msecparts[0],2,6);
 file_put_contents("/ssd/log/rcarputer.log", date("Y-m-d H:i:s") . "." . $msec .  " gpsuploader: " . $text . "\n", FILE_APPEND | LOCK_EX );
}

function post($action, $data){
 $service_url = 'https://projects.runnane.no/gps/rest.php?username=' . REST_USERNAME . '&password=' . REST_PASSWORD . '&action=' . $action;
 $curl = curl_init($service_url);
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($curl, CURLOPT_POST, true);
 curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
 curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
 $curl_response = curl_exec($curl);
 curl_close($curl);
 return json_decode($curl_response);
}
