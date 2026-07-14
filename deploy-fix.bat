@echo off
cd /d C:\laragon\www\backup-manager

echo Resultado: > deploy-fix-log.txt
echo. >> deploy-fix-log.txt

echo [1] A remover index.lock... >> deploy-fix-log.txt
attrib -r .git\index.lock 2>>deploy-fix-log.txt
del /f /q .git\index.lock 2>>deploy-fix-log.txt
echo   OK >> deploy-fix-log.txt

echo [2] Git add... >> deploy-fix-log.txt
git add .gitignore syncer/wintouch_woo/src/wintouch_woocommerce_sync/discount_sync.py >> deploy-fix-log.txt 2>&1
echo   OK >> deploy-fix-log.txt

echo [3] Git commit... >> deploy-fix-log.txt
git commit -m "fix: falha pa_marca nao bloqueia sync de descontos WinTouch" >> deploy-fix-log.txt 2>&1

echo [4] Git push... >> deploy-fix-log.txt
git push origin master >> deploy-fix-log.txt 2>&1

echo [5] Deploy no servidor... >> deploy-fix-log.txt
ssh -o StrictHostKeyChecking=no root@gestao.ateneya.com "cd /var/www/backup-manager && git pull origin master 2>&1 && echo PULL_OK" >> deploy-fix-log.txt 2>&1

echo [6] Log final: >> deploy-fix-log.txt
git log --oneline -3 >> deploy-fix-log.txt 2>&1

echo Concluido. Ver deploy-fix-log.txt >> deploy-fix-log.txt
type deploy-fix-log.txt
pause
