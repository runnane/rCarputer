#!/bin/bash

process="GPSUploader.php"
makerun="php /ssd/rCarputer/GPS/GPSUploader.php"

if ps ax | grep -v grep | grep $process > /dev/null
        then
                exit
        else
        $makerun &
        fi
exit
