#!/bin/bash
CURRENT_DIR=$(cd $(dirname $0); pwd)
cd $CURRENT_DIR

case $1 in
    start|reload|START)
        php crond.php $2
        ;;
    stop|STOP)
        sed -i 's/enable = "on"/enable = "off"/' $2
        php crond.php $2
        ;;
    restart|RESTART)
        pkill -f "sh run.sh "
        php crond.php $2
        ;;
    pkill|PKILL)
        pkill -f "sh run.sh "
        php crond.php $2 "pkill"
        ;;
    *)
        echo -e "\n\tERR action: $1\n"
esac
#END FILE