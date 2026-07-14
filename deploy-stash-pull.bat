@echo off
cd /d C:\laragon\www\backup-manager

echo A fazer stash e git pull no servidor... > deploy-stash-log.txt
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "cd /var/www/vhosts/gestao.ateneya.com/httpdocs && git stash && git pull origin master 2>&1 && echo PULL_OK && git log --oneline -3" >> deploy-stash-log.txt 2>&1

echo. >> deploy-stash-log.txt
type deploy-stash-log.txt
pause
