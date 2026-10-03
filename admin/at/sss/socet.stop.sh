#! /bin/bash

pkill -f "socat.*TCP-LISTEN:3306" || [[ $? -eq 1 ]]
