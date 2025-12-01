@echo off
REM Швидкий запуск SkyBooking на PHP Built-in Server

echo ========================================
echo   SkyBooking - Запуск веб-сервера
echo ========================================
echo.

REM Перевірка наявності PHP
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ПОМИЛКА] PHP не знайдено в системі!
    echo Будь ласка, встановіть PHP або додайте його до PATH
    pause
    exit /b 1
)

echo PHP знайдено!
echo.

REM Перевірка наявності PostgreSQL
psql --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [УВАГА] PostgreSQL не знайдено в PATH
    echo Переконайтеся, що PostgreSQL встановлено та база даних налаштована
    echo.
)

echo Запуск веб-сервера на http://localhost:8000
echo.
echo Натисніть Ctrl+C для зупинки сервера
echo ========================================
echo.

cd public
php -S localhost:8000

pause
