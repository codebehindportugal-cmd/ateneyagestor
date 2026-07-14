@echo off
chcp 65001 >nul
echo ==========================================
echo   Backup Manager - Deploy para Producao
echo ==========================================
echo.

rem Procurar o bash do Git
set BASH=
if exist "C:\Program Files\Git\bin\bash.exe" set BASH=C:\Program Files\Git\bin\bash.exe
if exist "C:\Program Files\Git\usr\bin\bash.exe" set BASH=C:\Program Files\Git\usr\bin\bash.exe

if "%BASH%"=="" (
    echo ERRO: Git Bash nao encontrado!
    pause
    exit /b 1
)

echo Git Bash: %BASH%
echo A iniciar deploy...
echo.

"%BASH%" /c/laragon/www/backup-manager/deploy-claude.sh

echo.
echo Codigo de saida: %ERRORLEVEL%
echo.
echo Deploy terminado. Ver deploy-log.txt para detalhes completos.
echo.
pause
