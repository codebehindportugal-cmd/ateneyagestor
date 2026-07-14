@echo off
chcp 65001 >nul
echo ==========================================
echo   Backup Manager - Commit e Push para GitHub
echo ==========================================
echo.

cd /d C:\laragon\www\backup-manager

rem Remover index.lock se existir (pode sobrar de sessão anterior)
if exist .git\index.lock del /f .git\index.lock
if exist .git\index.lock echo AVISO: Nao foi possivel remover index.lock - fechar todos os editores e tentar novamente

rem Desactivar conversão CRLF para evitar ruido no diff
git config core.autocrlf false

rem Remover do índice ficheiros que nao devem ser committed
git rm --cached bootstrap/cache/packages.php bootstrap/cache/services.php 2>nul
git rm --cached storage/framework/cache/facade-*.php 2>nul
git rm --cached deploy-log.txt 2>nul
git rm --cached .claude/launch.json .claude/settings.local.json 2>nul

rem Ignorar ficheiros de bootstrap cache
echo /bootstrap/cache/packages.php >> .gitignore
echo /bootstrap/cache/services.php >> .gitignore

rem Adicionar tudo
git add -A

rem Ver o que vai ser committed
echo.
echo Ficheiros a commitar:
git status --short
echo.

rem Commit
git commit -m "fix: CRLF normalizado, .gitignore actualizado, cache excluida"

echo.
echo A fazer push para GitHub...
git push origin master

echo.
echo ==========================================
echo   Push concluido! Agora faz git pull no servidor.
echo ==========================================
echo.
pause
