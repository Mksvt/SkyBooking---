@echo off
echo ========================================
echo  SkyBooking Database Restore Script
echo ========================================
echo.

REM Зупинка на випадок помилки
setlocal enabledelayedexpansion

echo [1/3] Видалення старої бази даних...
c:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS skybooking_db;"
if errorlevel 1 (
    echo ПОМИЛКА: Не вдалося видалити стару базу даних!
    pause
    exit /b 1
)
echo OK - Стара база видалена

echo.
echo [2/3] Імпорт бази даних з файлу...
cmd /c "c:\xampp\mysql\bin\mysql.exe -u root < c:\xampp\htdocs\skybooking\skybooking_db_backup.sql"
if errorlevel 1 (
    echo ПОМИЛКА: Не вдалося імпортувати базу даних!
    pause
    exit /b 1
)
echo OK - База даних імпортована

echo.
echo [3/3] Перевірка...
c:\xampp\mysql\bin\mysql.exe -u root -e "USE skybooking_db; SHOW TABLES;"
if errorlevel 1 (
    echo ПОМИЛКА: База даних не працює!
    pause
    exit /b 1
)

echo.
echo ========================================
echo  УСПІШНО ВІДНОВЛЕНО!
echo ========================================
echo.
echo База даних: skybooking_db
echo Таблиць: 8
echo Аеропортів: 23
echo Рейсів: ~74
echo.
echo Адмін-панель: admin@admin.com / SkyB0oking@Adm1n2025!
echo.
echo ========================================
pause
