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
		$result = number_format((microtime(true) - $ts) * 1000,3);
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
 $service_url = REST_ENDPOINT . '?username=' . REST_USERNAME . '&password=' . REST_PASSWORD . '&action=' . $action;
 $curl = curl_init($service_url);
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($curl, CURLOPT_POST, true);
 curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
 curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
 $curl_response = curl_exec($curl);
 curl_close($curl);
 return json_decode($curl_response);
}

function scanWlan(){
	$raw = array();
	$iwlist = exec("/sbin/iwlist wlan0 scanning 2>/dev/null", $raw);
	foreach($raw as $line){
		if(trim($line) === ""){
			continue;
		}
		if(trim(substr($line, 0,strlen("                        "))) === ""){
			$line = trim($line);
			list($i,$v) = explode(":", $line, 2);
			$i = trim($i);
			$v = trim($v);
			$data[$current_cell]["IE"][$current_ie][$i][] = $v;
		}else if(!trim(substr($line, 0,strlen("                    ")))){
			$line = trim($line);
			if(substr($line, 0, 8) == "Quality="){
				preg_match("/^Quality=(\d+)\/100\s+Signal\slevel=(\d+)\/100$/i",$line, $m);
				$data[$current_cell]["Quality"] = $m[1];
				$data[$current_cell]["Signal Level"] = $m[2];
			}else{
				list($i,$v) = explode(":", $line, 2);
				$i = trim($i);
				$v = trim($v);
				if($i == "IE"){
					$current_ie = $v;
				}else{
					$data[$current_cell][$i][] = $v;
				}
			}
		}else if(!trim(substr($line, 0,strlen("          ")))){
			$line = trim($line);
			preg_match("/^Cell\s(\d+)\s\-\sAddress\:\s(.+)$/i",$line, $m);
			$current_cell = intval($m[1]);
			$data[$current_cell]["MAC"] = strtolower($m[2]);
			$current_ie=false;
		}
	}
	return $data;
}

function collapseArray($a){
	if(!is_array($a)){
		return $a;
	}
	if(count($a) == 0){
		return;
	}
	if(count($a) == 1){
		foreach($a as $v){
			if(is_array($v)){
				return collapseArray($v);
			}
		}
	}
	if(count($a) == 1){
		return $a[0];
	}
	if(count($a) > 1){
		foreach($a as $i => $v){
			$a[$i] = collapseArray($v);
		}
		return $a;
	}
}
?>
