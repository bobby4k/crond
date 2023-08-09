#!/bin/bash
CURRENT_DIR=$(cd $(dirname $0); pwd)
cd $CURRENT_DIR

case $1 in
    start|reload|restart)
        php crond.php $2
        ;;
    stop|STOP)
        pkill -f "sh run.sh "
        ;;
    pkill|PKILL)
        pkill -f "sh run.sh "
        php crond.php $2 "pkill"
        ;;
    *)
        echo -e "\n\tERR action: $1\n"
esac
