##!/bin/bash
# 轮询执行PHP
# DIR=`dirname $0`
DIR=$(cd $(dirname $0); pwd)
#####################
## 运行后台程序
#
# 1. 通过lock文件, 如果进程存在且不匹配, kill原PID
# 2. 如果PID不存在, 生成自己的PID
# 3. 如果PID匹配,结束自己
# 4. 循环Command进程
#
# run.sh {base64} {time} {kill}
#
#####################
if [ -z $1 ];then
  # 运行内容为空
  exit 0;
fi

# decode command lock
crond_decode() {
    local str="$1"
    local decoded_str
    decoded_str=$(echo "$str" | sed 's/-/+/g; s/_/\//g; s/~/=/g' | base64 -d)
    echo "$decoded_str"
}

CLI_RUN="$1"
CLI_TIME="$2"
CLI_ON="$3"
CLI_DIR=$DIR
if [ -n "$4" ] && [ -d "$4" ]; then
  CLI_DIR="$4"
fi
if [[ "$CLI_TIME" =~ ^[0-9\.]+$ ]]; then
  SHELL_TIME="$CLI_TIME"
else
  #默认1分钟
  CLI_TIME=60
  SHELL_TIME=60
fi


SHELL_LOCK="${DIR}/lock/${CLI_RUN}.lock"
SHELL_RUN=''
#当前执行的shell pid
SHELL_PID=0
#保持原shell的运行
SHELL_HOLD=0

if [ -f $SHELL_LOCK ];then
  #存在lock文件, 判断是否匹配现有参数
  line=1
  while read row
  do
    if [ "$line" -eq 1 ];then
      if [[ "$row" =~ ^[0-9]+$ ]];then
        SHELL_PID=$row
      fi
    fi
    if [ "$line" -eq 2 ];then
      SHELL_RUN=$row
    fi
    if [ "$line" -eq 3 ];then
      if [[ "$row" =~ ^[0-9]+$ ]];then
        SHELL_TIME=$row
      fi
    fi

    line=$((${line}+1))
  done < $SHELL_LOCK

  ## 判断SHELL_PID是否存在
  if [ "`expr $SHELL_PID + 0`" != "" -a -d "/proc/$SHELL_PID" ];then
    ## 关闭命令
    if [ "$CLI_ON" = "off" ];then
      kill -9 "$SHELL_PID"
      rm $SHELL_LOCK
      echo "close $SHELL_PID !"
      exit 0;
    fi

    ## 判断轮询时间 和 CLI_RUN、SHELL_RUN
    if [ "$SHELL_TIME" -eq "$CLI_TIME" ];then
      if [ "$SHELL_RUN" = "$CLI_RUN" ];then
        SHELL_HOLD=1
      fi
    fi
    ## 如果完全匹配结束自己, 反之结束原有进程
    if [ "$SHELL_HOLD" -eq 1 ];then
      exit 0;
    else
      kill -9 "$SHELL_PID"
      rm $SHELL_LOCK
      echo "remove old shell $SHELL_PID ..."
    fi

  else
    ## PID不存在, 删除lock文件
    rm $SHELL_LOCK
    echo "remove $SHELL_LOCK ."
  fi

fi;
#END lock

#是该结束鸟
if [ "$CLI_ON" = "off" ];then
  exit 0;
fi;



#写入SHELL_LOCK
#####################
## lock文件
# 第一行: 当前shell PID
# 第二行: base64编码的函数
# 第三行: php轮询间隔
#####################
echo "$$" > "$SHELL_LOCK"
echo "$CLI_RUN" >> "$SHELL_LOCK"
echo "$CLI_TIME" >> "$SHELL_LOCK"

decoded_str=$(crond_decode "$CLI_RUN")
COMMADN="cd ${CLI_DIR} ; ${decoded_str}"
echo $COMMADN

SLEEPSTR=""
if command -v usleep &> /dev/null; then
  if [[ $SHELL_TIME =~ \. ]]; then
    MICROSECONDS=$(echo "$SHELL_TIME * 1000000" | bc -l | cut -d '.' -f 1)
    SLEEPSTR="usleep ${MICROSECONDS}"
  fi
fi
if [[ "$SLEEPSTR" == "" ]]; then
    $SLEEPSTR="sleep ${SHELL_TIME}"
fi
#echo $SLEEPSTR

while true; do
  eval $COMMADN
  eval $SLEEPSTR
done
#END FILE