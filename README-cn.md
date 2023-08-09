# Along Crond 单机版任务管理
- 另一个执行循环任务的crontab
- 同名任务同一时间仅存在一个

# 依赖
- bash
- php-cli

# 使用方法
```shell
# see example.ini
vim mytasks.ini
chmod +x crond.sh
crond.sh start mytasks.ini
```


## 缘起
- 单机多应用
    - 由于集中部署, 好几个项目(php/slim、php/webman、go/somer、go/ginping、python/kingpin X2) 都部署在同一个机器上。 当然，其中必然不全是docker container, 任务花样繁多。
    - 为啥不分开部署？机器是32核64G内存, 放云服务不一定能有这个性能。
- crontab无法满足
    - cron不关心相同任务上一次是否已经执行完毕,  当一个任务启动时，我希望上一个任务必须执行完并退出。
- 十年前的方案
    - 也许supervisor更合适，配置也相对繁琐。
    - 翻出十年的方案，利用bash while控制循环， 改改也能凑合，就他吧。


