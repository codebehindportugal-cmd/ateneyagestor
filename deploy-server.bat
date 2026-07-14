@echo off
cd /d C:\laragon\www\backup-manager

echo A fazer git pull no servidor... > deploy-server-log.txt
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "cd /var/www/vhosts/gestao.ateneya.com/httpdocs && git pull origin master 2>&1 && echo PULL_OK && git log --oneline -3" >> deploy-server-log.txt 2>&1

echo. >> deploy-server-log.txt
type deploy-server-log.txt
pause
