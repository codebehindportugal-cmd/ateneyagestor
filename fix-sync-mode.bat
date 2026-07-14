@echo off
cd /d C:\laragon\www\backup-manager

echo A corrigir runner_mode e status do sync... > fix-sync-mode-log.txt
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "cd /var/www/vhosts/gestao.ateneya.com/httpdocs && DB_USER=$(grep '^DB_USERNAME' .env | cut -d= -f2) && DB_PASS=$(grep '^DB_PASSWORD' .env | cut -d= -f2) && DB_NAME=$(grep '^DB_DATABASE' .env | cut -d= -f2) && mysql -u$DB_USER -p$DB_PASS $DB_NAME -e \"UPDATE sync_projects SET runner_mode='local', status='ok' WHERE id=1; UPDATE sync_runs SET status='failed', error='Run marcada como falhada por estar presa', finished_at=NOW() WHERE sync_project_id=1 AND status='running'; SELECT id, name, runner_mode, status, runner_schedule FROM sync_projects WHERE id=1;\" 2>&1" >> fix-sync-mode-log.txt 2>&1

echo. >> fix-sync-mode-log.txt
echo === VERIFICAR SCHEDULE LIST === >> fix-sync-mode-log.txt
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "cd /var/www/vhosts/gestao.ateneya.com/httpdocs && /opt/plesk/php/8.3/bin/php artisan schedule:list 2>&1" >> fix-sync-mode-log.txt 2>&1

echo. >> fix-sync-mode-log.txt
type fix-sync-mode-log.txt
pause
