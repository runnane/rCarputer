rCarputer
=========

Some scripts and tools for my carputer

FILES
----
* GPS/* - files for capturing, storing and syncing GPS signals
* Keepalive/* - scripts for 
* server.sql - sql layout for offsite main storage

CRON
----
```*/1 * * * * /bin/bash /ssd/rCarputer/Keepalive/check-gps-uploader.sh
*/1 * * * * /bin/bash /ssd/rCarputer/Keepalive/check-gps-poller.sh
```

TODO
----
* GPS/rMobileGPSUploader.php - create table in sqlite db if not exists during loop

