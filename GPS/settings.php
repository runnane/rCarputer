<?php

// Limit internetaccess on specified ssids
$accepted_ssids = array("linksys");

// Nics to monitor
$nics = array("eth0","wlan0");

// "REST" interface for ReportIn() methods
define("REST_ENDPOINT", "");
define("REST_USERNAME","");
define("REST_PASSWORD", "");

// IP/Hostname to ping (icmp) target
define("LATENCY_TARGET", "");

// How often to ReportIn() in seconds
define("REPORTIN_INTERVAL", 1800); // every 30 min

// Include wlan scans in report payload
define("INCLUDE_WLAN_SCAN", 1);

// Databasescan
define("SQLITE_FILE", "/ssd/db/rcarputer.db");


?>
