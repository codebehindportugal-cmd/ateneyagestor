@echo off
cd /d C:\laragon\www\backup-manager

echo A verificar cron jobs no servidor... > check-cron-log.txt
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "echo === CRONTAB ROOT === && crontab -l 2>&1 && echo. && echo === PLESK CRONS === && ls /var/spool/cron/crontabs/ 2>&1 && echo. && echo === CRONTAB www-data === && crontab -u www-data -l 2>&1 && echo. && echo === SCHEDULE:WORK STATUS === && systemctl status laravel-schedule 2>/dev/null || echo 'No laravel-schedule service' && echo. && echo === PYTHON SYNC PROCESS === && ps aux | grep wintouch | grep -v grep || echo 'Python sync nao esta a correr'" >> check-cron-log.txt 2>&1

echo. >> check-cron-log.txt
type check-cron-log.txt
pause
