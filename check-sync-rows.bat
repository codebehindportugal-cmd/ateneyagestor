@echo off
cd /d C:\laragon\www\backup-manager

echo A verificar registos sync_projects... > check-sync-rows-log.txt
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "cd /var/www/vhosts/gestao.ateneya.com/httpdocs && /opt/plesk/php/8.3/bin/php -r \"require 'vendor/autoload.php'; \$app=require 'bootstrap/app.php'; \$app->boot(); \$rows=DB::table('sync_projects')->get(['id','name','slug','is_active','runner_mode','runner_script_path','runner_schedule','status','last_run_at']); echo json_encode(\$rows, JSON_PRETTY_PRINT);\"" >> check-sync-rows-log.txt 2>&1

echo. >> check-sync-rows-log.txt
type check-sync-rows-log.txt
pause
