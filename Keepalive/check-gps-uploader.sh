#!/bin/bash

process="rMobileGPSUploader.php"
makerun="php /root/rCarputer/GPS/rMobileGPSUploader.php"

if ps ax | grep -v grep | grep $process > /dev/null
        then
                exit
        else
        $makerun &
        fi
exit
