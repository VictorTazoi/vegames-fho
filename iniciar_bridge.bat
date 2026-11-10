@echo off
cd /d "%~dp0"
:inicio
C:\xampp\php\php.exe mqtt\bridge.php
echo Bridge parou. Reiniciando em 3 segundos...
timeout /t 3 >nul
goto inicio
