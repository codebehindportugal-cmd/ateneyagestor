@echo off
cd /d C:\laragon\www\backup-manager

echo A verificar sync projects... > check-sync-final-log.txt

REM Escrever script PHP no servidor e executar
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "cat > /tmp/check_sync.php << 'PHPEOF'
<?php
chdir('/var/www/vhosts/gestao.ateneya.com/httpdocs');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->boot();
$rows = DB::table('sync_projects')->get(['id','name','slug','is_active','runner_mode','runner_script_path','runner_schedule','status','last_run_at']);
echo json_encode($rows, JSON_PRETTY_PRINT);
PHPEOF
/opt/plesk/php/8.3/bin/php /tmp/check_sync.php
rm /tmp/check_sync.php" >> check-sync-final-log.txt 2>&1

echo. >> check-sync-final-log.txt
type check-sync-final-log.txt
pause
