@echo off
setlocal
cd /d "%~dp0"

if not exist config.json (
    copy config.example.json config.json >nul
    echo Foi criado config.json. Preenche api_url e token antes de instalar.
    pause
    exit /b 1
)

where python >nul 2>nul
if errorlevel 1 (
    echo Python nao encontrado. Instala Python 3.10+ e volta a executar.
    pause
    exit /b 1
)

schtasks /Create /TN "GestaoAteneyaProductivityMonitor" /SC ONLOGON /DELAY 0000:30 /TR "\"%CD%\run-minimized.bat\"" /F

echo Monitor instalado. Vai iniciar minimizado sempre que o utilizador iniciar sessao no Windows.
echo Para iniciar agora minimizado, executa run-minimized.bat.
echo Para ver o estado, executa run-visible.bat.
pause
