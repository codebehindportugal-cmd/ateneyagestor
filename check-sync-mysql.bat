@echo off
cd /d C:\laragon\www\backup-manager

echo A verificar sync_projects via MySQL... > check-sync-mysql-log.txt
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "cd /var/www/vhosts/gestao.ateneya.com/httpdocs && DB_USER=$(grep '^DB_USERNAME' .env | cut -d= -f2) && DB_PASS=$(grep '^DB_PASSWORD' .env | cut -d= -f2) && DB_NAME=$(grep '^DB_DATABASE' .env | cut -d= -f2) && mysql -u$DB_USER -p$DB_PASS $DB_NAME -e 'SELECT id,name,slug,is_active,runner_mode,runner_script_path,runner_schedule,status,last_run_at FROM sync_projects' 2>&1" >> check-sync-mysql-log.txt 2>&1

echo. >> check-sync-mysql-log.txt
type check-sync-mysql-log.txt
pause
