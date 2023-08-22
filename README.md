# Along Crond: Standalone Task Management
- Another crontab for executing recurring tasks
- Only one task with the same name can exist at a given time

# Dependencies
- bash
    - sleep usleep bc cut
- php-cli

# Usage
```shell
# See example.ini
vim mytasks.ini
chmod +x crond.sh
crond.sh start mytasks.ini
```

## Background
- Single Machine, Multiple Applications
    - Due to centralized deployment, several projects (php/slim, php/webman, go/somer, go/ginping, python/kingpin X2) are deployed on the same machine. Of course, not all of them are docker containers; the tasks come in various flavors.
    - Why not separate deployments? The machine has 32 cores and 64GB memory, which might not be attainable in a cloud service.
- Insufficiency of crontab
    - Cron does not care if the previous run of the same task has completed when a new instance starts. When a task starts, I want the previous one to have completed and exited.
    - Crontab has another issue. When NTP synchronized time exceeds 1 second, the scheduled tasks will no longer run.
- Solution from a Decade Ago
    - Perhaps supervisor is more suitable, but the configuration is relatively cumbersome.
    - Digging out a solution from ten years ago, utilizing bash while loop to control the loop, with some modifications it works fine. Let's go with it.

