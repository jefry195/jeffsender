@echo off
set BACKUP_DIR=C:\db_backups
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set YYYY=%datetime:~0,4%
set MM=%datetime:~4,2%
set DD=%datetime:~6,2%
set HH=%datetime:~8,2%
set Min=%datetime:~10,2%
set Sec=%datetime:~12,2%

set FILE_PATH=%BACKUP_DIR%\whatsml_backup_%YYYY%-%MM%-%DD%_%HH%-%Min%-%Sec%.sql

echo [%date% %time%] Starting backup for database 'whatsml'... >> "%BACKUP_DIR%\backup_log.txt"
"C:\xampp\mysql\bin\mysqldump.exe" -u root whatsml > "%FILE_PATH%"

if %ERRORLEVEL% equ 0 (
    echo [%date% %time%] Backup successful: %FILE_PATH% >> "%BACKUP_DIR%\backup_log.txt"
    echo Backup completed successfully.
    
    :: Keep only the 30 most recent backup files to save disk space
    for /f "skip=30 delims=" %%A in ('dir "%BACKUP_DIR%\whatsml_backup_*.sql" /b /o-d') do (
        del "%BACKUP_DIR%\%%A"
        echo [%date% %time%] Deleted old backup: %%A >> "%BACKUP_DIR%\backup_log.txt"
    )
) else (
    echo [%date% %time%] Backup FAILED with exit code %ERRORLEVEL% >> "%BACKUP_DIR%\backup_log.txt"
    echo Backup failed with error code %ERRORLEVEL%.
)
