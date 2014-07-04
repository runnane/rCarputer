#!/bin/bash

process="rMobileGPSLog.py"
makerun="python /root/rCarputer/GPS/rMobileGPSLog.py"

if ps ax | grep -v grep | grep $process > /dev/null
        then
                exit
        else
        $makerun &
        fi
exit
