@echo off
cd /d C:\laragon\www\backup-manager

echo A verificar configuracao dos sync projects no servidor... > check-sync-log.txt
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "cd /var/www/vhosts/gestao.ateneya.com/httpdocs && /opt/plesk/php/8.3/bin/php artisan tinker --execute=\"SyncProject::all(['id','name','slug','is_active','runner_mode','runner_script_path','runner_schedule','status','last_run_at'])->toArray();\" 2>&1" >> check-sync-log.txt 2>&1

echo. >> check-sync-log.txt
echo === SCHEDULE LIST === >> check-sync-log.txt
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "cd /var/www/vhosts/gestao.ateneya.com/httpdocs && /opt/plesk/php/8.3/bin/php artisan schedule:list 2>&1" >> check-sync-log.txt 2>&1

echo. >> check-sync-log.txt
type check-sync-log.txt
pause
