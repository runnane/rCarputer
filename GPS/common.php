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

function logit($text, $level=1){
 $msecparts = explode(" ",microtime());
 $msec = substr($msecparts[0],2,6);
 file_put_contents("/ssd/log/rcarputer.log", date("Y-m-d H:i:s") . "." . $msec .  " gpsuploader: " . $text . "\n", FILE_APPEND | LOCK_EX );
}

function post($action, $data){
 $service_url = REST_ENDPOINT . '?username=' . REST_USERNAME . '&password=' . REST_PASSWORD . '&action=' . $action;
 $curl = curl_init($service_url);
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($curl, CURLOPT_POST, true);
 curl_setopt($curl, CURLOPT_POSTFIELDS, gzcompress($data));
 curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
 curl_setopt($curl, CURLOPT_ENCODING, 'Accept-Encoding: gzip,deflate');
 $curl_response = curl_exec($curl);
 curl_close($curl);
 return json_decode($curl_response);
}

function easyCurl($service_url){
 $curl = curl_init($service_url);
 curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($curl, CURLOPT_ENCODING, 'Accept-Encoding: gzip,deflate');
 $curl_response = curl_exec($curl);
 curl_close($curl);
 return $curl_response;
}

function getPublicIP(){
 $service4_url = "http://46.246.108.248/ip.php";
 $service6_url = "http://[2a00:1a28:1101:81c:1337::bd8]/ip.php";
 $ip4 = easyCurl($service4_url);
 $ip6 = easyCurl($service6_url);
 return array("ipv4" => json_decode($ip4), "ipv6" => json_decode($ip6));
}

function getIPfromInterface($interface){
	$ip4 = $ip6 = array();
	$last4 = exec("/sbin/ip addr show {$interface} | grep 'inet ' |  sed 's/^ *//' | cut -d ' ' -f 2", $ip4);
	$last6 = exec("/sbin/ip addr show {$interface} | grep 'inet6 ' |  sed 's/^ *//' | cut -d ' ' -f 2", $ip6);
	return array_merge($ip4, $ip6);

}

function scanWlan(){
	$current_cell = -1;
	$raw = array();
	$iwlist = exec("/sbin/iwlist wlan0 scanning 2>/dev/null", $raw);
	foreach($raw as $line){
		if(trim($line) === ""){
			continue;
		}
		if(trim(substr($line, 0, 24)) === ""){
			$line = trim($line);
			list($i, $v) = explode(":", $line, 2);
			$i = trim($i);
			$v = trim($v);
			$data[$current_cell]["IE"][$current_ie][$i][] = $v;
		}else if(!trim(substr($line, 0, 20))){
			$line = trim($line);
			if(substr($line, 0, 8) == "Quality="){
				preg_match("/^Quality=(\d+)\/100\s+Signal\slevel=(\d+)\/100$/i", $line, $m);
				$data[$current_cell]["Quality"] = intval($m[1]);
				$data[$current_cell]["Signal Level"] = intval($m[2]);
			}else{
				list($i, $v) = explode(":", $line, 2);
				$i = trim($i);
				$v = trim($v);
				if($i == "ESSID"){
					$v = str_replace("\"", "", $v);
				}
				if($i == "IE"){
					$current_ie = $v;
				}else{
					$data[$current_cell][$i][] = $v;
				}
			}
		}else if(!trim(substr($line, 0, 10))){
			$line = trim($line);
			preg_match("/^Cell\s(\d+)\s\-\sAddress\:\s(.+)$/i", $line, $m);
			$current_cell++;
			$data[$current_cell]["MAC"] = strtolower($m[2]);
			$data[$current_cell]["CellId"] = intval($m[1]);
			$current_ie=false;
		}
	}
	return $data;
}

// Hack to collapse xml-style multileveled arrays from single item arrays to associative arrays
function collapseArray($a){
 if(!is_array($a))
  return $a;
 switch(count($a)){
  case 0: 
   return; 
   break;
  case 1:	
   foreach($a as $v)
    if(is_array($v))
     return collapseArray($v);
    else
     return $v;
   break;
  default:
   foreach($a as $i => $v)
    $a[$i] = collapseArray($v);
   return $a;
   break;
 }
}
?>
