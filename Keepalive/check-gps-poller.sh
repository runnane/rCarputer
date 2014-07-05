#!/bin/bash

process="GPSPoller.py"
makerun="python /ssd/rCarputer/GPS/GPSPoller.py"

if ps ax | grep -v grep | grep $process > /dev/null
        then
                exit
        else
        $makerun &
        fi
exit
