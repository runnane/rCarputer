<?php

$accepted_ssids = array("tungland", "fwg", "Telenor5944fus", "supah");
$nics = array("eth0","wlan0");

define("REST_USERNAME","rpi2");
define("REST_PASSWORD", "oiuhnoiuabsigonygosgndoiagy");
define("REST_ENDPOINT", "https://projects.runnane.no/gps/rest.php");

define("LATENCY_TARGET", "81.29.32.130");

define("REPORTIN_INTERVAL", 900); // every 15 min
define("SQLITE_FILE", "/ssd/db/rcarputer.db");

?>
