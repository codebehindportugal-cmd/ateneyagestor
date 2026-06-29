@echo off
cd /d "%~dp0"

where pythonw >nul 2>nul
if errorlevel 1 (
    start "" /min python productivity_agent.py --config config.json --minimized
    exit /b
)

start "" /min pythonw productivity_agent.py --config config.json --minimized
