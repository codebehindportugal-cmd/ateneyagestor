@echo off
cd /d C:\laragon\www\backup-manager

echo A verificar sync projects na base de dados... > check-sync-db-log.txt
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "cd /var/www/vhosts/gestao.ateneya.com/httpdocs && /opt/plesk/php/8.3/bin/php artisan db:table sync_projects 2>&1 || /opt/plesk/php/8.3/bin/php artisan tinker --no-interaction << 'EOF'
echo json_encode(DB::table('sync_projects')->get()->toArray());
EOF
" >> check-sync-db-log.txt 2>&1

echo. >> check-sync-db-log.txt
echo === MAIN.PY EXISTE? === >> check-sync-db-log.txt
ssh -i "%USERPROFILE%\.ssh\ateneya_vps_key" -o StrictHostKeyChecking=no root@gestao.ateneya.com "ls -la /var/www/vhosts/gestao.ateneya.com/httpdocs/syncer/wintouch_woo/main.py 2>&1 && echo 'Script encontrado' || echo 'Script NAO encontrado'" >> check-sync-db-log.txt 2>&1

echo. >> check-sync-db-log.txt
type check-sync-db-log.txt
pause
