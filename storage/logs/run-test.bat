@echo off`r`ncd /d C:\laragon\www\backup-manager`r`n"C:\laragon\bin\php\php-8.2.5-Win32-vs16-x64\php.exe" artisan list > storage\logs\bat-test.log 2> storage\logs\bat-test.err.log`r`n
