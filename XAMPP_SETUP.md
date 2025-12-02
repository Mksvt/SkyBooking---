# 🚀 Інструкція по запуску проєкту в XAMPP

## 📋 Крок 1: Встановлення XAMPP

1. Завантажте XAMPP з офіційного сайту: https://www.apachefriends.org/
2. Встановіть XAMPP (рекомендовано в `C:\xampp`)
3. Переконайтеся, що встановлено:
   - ✅ Apache
   - ✅ PHP 7.4 або новіше
   - ✅ MySQL (входить в XAMPP)

---

## 📁 Крок 2: Розміщення файлів проєкту

### Варіант А: Копіювання в htdocs (рекомендовано)

1. Відкрийте папку `C:\xampp\htdocs\`
2. Створіть папку `skybooking`
3. Скопіюйте ВСІ файли вашого проєкту в `C:\xampp\htdocs\skybooking\`

Структура має бути така:

```
C:\xampp\htdocs\skybooking\
├── includes/
│   ├── config.php
│   ├── header.php
│   └── footer.php
├── public/
│   ├── index.php
│   ├── search.php
│   ├── login.php
│   ├── register.php
│   └── ...
├── css/
│   └── style.css
├── src/
│   ├── schema.sql
│   └── desc.md
└── README.md
```

### Варіант Б: Використання вашої поточної папки

1. Відкрийте файл `C:\xampp\apache\conf\httpd.conf`
2. Знайдіть рядок `DocumentRoot "C:/xampp/htdocs"`
3. Змініть на: `DocumentRoot "C:/Users/musia/OneDrive/Робочий стіл/Course"`
4. Знайдіть `<Directory "C:/xampp/htdocs">`
5. Змініть на: `<Directory "C:/Users/musia/OneDrive/Робочий стіл/Course">`
6. Збережіть файл і перезапустіть Apache

---

## 🗄️ Крок 3: Налаштування MySQL та створення бази даних

### 3.1. Запуск MySQL в XAMPP

1. Відкрийте **XAMPP Control Panel**
2. Натисніть **Start** біля **MySQL**
3. Дочекайтеся, поки MySQL стане зеленим
4. Натисніть **Admin** біля MySQL (відкриється phpMyAdmin)

### 3.2. Створення бази даних через SQL скрипт

1. В phpMyAdmin натисніть на вкладку **SQL** (зверху)
2. Скопіюйте та виконайте наступний скрипт:

```sql
-- Створення бази даних
CREATE DATABASE IF NOT EXISTS skybooking_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Використання бази даних
USE skybooking_db;

-- Таблиця клієнтів
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    date_of_birth DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблиця пасажирів
CREATE TABLE IF NOT EXISTS passengers (
    passenger_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    passport_number VARCHAR(20) NOT NULL,
    nationality VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_passport (passport_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблиця авіакомпаній
CREATE TABLE IF NOT EXISTS airlines (
    airline_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    iata_code VARCHAR(3) NOT NULL UNIQUE,
    country VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_iata (iata_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблиця аеропортів
CREATE TABLE IF NOT EXISTS airports (
    airport_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(50) NOT NULL,
    country VARCHAR(50) NOT NULL,
    iata_code VARCHAR(3) NOT NULL UNIQUE,
    timezone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_iata (iata_code),
    INDEX idx_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблиця рейсів
CREATE TABLE IF NOT EXISTS flights (
    flight_id INT AUTO_INCREMENT PRIMARY KEY,
    airline_id INT NOT NULL,
    flight_number VARCHAR(10) NOT NULL,
    departure_airport_id INT NOT NULL,
    arrival_airport_id INT NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    available_seats INT NOT NULL DEFAULT 0,
    status ENUM('scheduled', 'boarding', 'departed', 'arrived', 'cancelled', 'delayed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (airline_id) REFERENCES airlines(airline_id) ON DELETE CASCADE,
    FOREIGN KEY (departure_airport_id) REFERENCES airports(airport_id) ON DELETE CASCADE,
    FOREIGN KEY (arrival_airport_id) REFERENCES airports(airport_id) ON DELETE CASCADE,
    INDEX idx_flight_number (flight_number),
    INDEX idx_departure_time (departure_time),
    INDEX idx_departure_airport (departure_airport_id),
    INDEX idx_arrival_airport (arrival_airport_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблиця бронювань
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    currency VARCHAR(3) DEFAULT 'UAH',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_booking_date (booking_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблиця квитків
CREATE TABLE IF NOT EXISTS tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    flight_id INT NOT NULL,
    passenger_id INT NOT NULL,
    seat_number VARCHAR(5) NOT NULL,
    travel_class ENUM('economy', 'business', 'first') DEFAULT 'economy',
    ticket_price DECIMAL(10, 2) NOT NULL,
    ticket_status ENUM('active', 'used', 'cancelled') DEFAULT 'active',
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(flight_id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES passengers(passenger_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_flight (flight_id),
    INDEX idx_passenger (passenger_id),
    INDEX idx_seat (seat_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблиця платежів
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('card', 'paypal', 'bank_transfer') NOT NULL,
    transaction_id VARCHAR(100) UNIQUE,
    payment_status ENUM('pending', 'success', 'failed', 'refunded') DEFAULT 'pending',
    currency VARCHAR(3) DEFAULT 'UAH',
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка тестових даних

-- Авіакомпанії
INSERT INTO airlines (name, iata_code, country) VALUES
('Ukraine International Airlines', 'PS', 'Ukraine'),
('Ryanair', 'FR', 'Ireland'),
('Wizz Air', 'W6', 'Hungary'),
('Lufthansa', 'LH', 'Germany'),
('Air France', 'AF', 'France');

-- Аеропорти
INSERT INTO airports (name, city, country, iata_code, timezone) VALUES
('Бориспіль', 'Київ', 'Ukraine', 'KBP', 'Europe/Kiev'),
('Шарль де Голль', 'Париж', 'France', 'CDG', 'Europe/Paris'),
('Хітроу', 'Лондон', 'UK', 'LHR', 'Europe/London'),
('Мюнхен', 'Мюнхен', 'Germany', 'MUC', 'Europe/Berlin'),
('Схіпхол', 'Амстердам', 'Netherlands', 'AMS', 'Europe/Amsterdam'),
('Відень', 'Відень', 'Austria', 'VIE', 'Europe/Vienna'),
('Варшава', 'Варшава', 'Poland', 'WAW', 'Europe/Warsaw'),
('Прага', 'Прага', 'Czech Republic', 'PRG', 'Europe/Prague');

-- Рейси
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id,
                     departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS101', 1, 2, '2025-12-10 08:00:00', '2025-12-10 11:30:00', 2500.00, 180, 'scheduled'),
(2, 'FR202', 1, 3, '2025-12-10 10:00:00', '2025-12-10 12:45:00', 1800.00, 189, 'scheduled'),
(3, 'W6303', 1, 6, '2025-12-11 06:30:00', '2025-12-11 08:15:00', 1200.00, 180, 'scheduled'),
(4, 'LH404', 1, 4, '2025-12-11 14:00:00', '2025-12-11 16:30:00', 3200.00, 150, 'scheduled'),
(5, 'AF505', 2, 1, '2025-12-12 09:00:00', '2025-12-12 14:30:00', 2800.00, 200, 'scheduled'),
(1, 'PS106', 3, 1, '2025-12-12 16:00:00', '2025-12-12 21:00:00', 2600.00, 180, 'scheduled'),
(2, 'FR207', 5, 1, '2025-12-13 07:00:00', '2025-12-13 11:00:00', 1500.00, 189, 'scheduled'),
(3, 'W6308', 6, 1, '2025-12-13 12:00:00', '2025-12-13 13:45:00', 1100.00, 180, 'scheduled'),
(4, 'LH409', 4, 1, '2025-12-14 18:00:00', '2025-12-14 20:30:00', 3100.00, 150, 'scheduled'),
(1, 'PS110', 1, 7, '2025-12-15 10:00:00', '2025-12-15 11:30:00', 1600.00, 180, 'scheduled');

-- Користувач для бази даних (виконайте окремо після створення таблиць)
-- CREATE USER 'skybooking_user'@'localhost' IDENTIFIED BY 'Sk7B00k!ng2024';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON skybooking_db.* TO 'skybooking_user'@'localhost';
-- FLUSH PRIVILEGES;
```

3. Натисніть **Виконати** (Go)
4. Перевірте, що всі таблиці створилися (ліворуч має з'явитися список з 8 таблиць)

---

## ⚙️ Крок 4: Налаштування config.php для MySQL

Відкрийте файл `includes\config.php` і **ЗМІНІТЬ** підключення на MySQL:

```php
<?php
session_start();

// Налаштування бази даних для MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'skybooking_db');
define('DB_USER', 'root');              // Користувач MySQL (за замовчуванням root)
define('DB_PASS', '');                   // Пароль (за замовчуванням пустий в XAMPP)
define('DB_CHARSET', 'utf8mb4');

// Підключення до MySQL через PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Помилка підключення до бази даних: " . $e->getMessage());
}

// Решта коду залишається без змін...
```

**⚠️ ВАЖЛИВО:**

- Для розробки в XAMPP використовуємо `root` без пароля
- Для продакшену **ОБОВ'ЯЗКОВО** створіть окремого користувача!

---

## 🚀 Крок 5: Запуск XAMPP

1. Запустіть `XAMPP Control Panel`
2. Натисніть `Start` біля `Apache`
3. Дочекайтеся, поки Apache стане зеленим

### Перевірка роботи:

1. Відкрийте браузер
2. Перейдіть на:
   - **Якщо проєкт в htdocs/skybooking:** http://localhost/skybooking/public/index.php
   - **Якщо змінили DocumentRoot:** http://localhost/public/index.php

---

## 🐛 Крок 6: Вирішення можливих проблем

### Проблема 1: Apache не запускається

**Причина:** Порт 80 зайнятий іншою програмою (Skype, IIS, тощо)

**Рішення:**

1. Відкрийте `C:\xampp\apache\conf\httpd.conf`
2. Знайдіть `Listen 80`
3. Змініть на `Listen 8080`
4. Збережіть і перезапустіть Apache
5. Тепер сайт буде на http://localhost:8080/...

### Проблема 2: Не підключається до бази даних

**Перевірте:**

1. MySQL запущено в XAMPP Control Panel (має бути зелений)
2. Логін/пароль в `config.php` правильні (root без пароля)
3. База даних `skybooking_db` існує (перевірте в phpMyAdmin)
4. PHP має розширення `pdo_mysql` (зазвичай увімкнено за замовчуванням):
   - Відкрийте `C:\xampp\php\php.ini`
   - Знайдіть `extension=pdo_mysql`
   - Переконайтеся, що немає `;` на початку
   - Перезапустіть Apache

### Проблема 3: Білий екран (White Screen of Death)

**Увімкніть відображення помилок:**

Створіть файл `.htaccess` в корені проєкту:

```apache
php_flag display_errors on
php_value error_reporting E_ALL
```

Або додайте на початку `config.php`:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Проблема 4: CSS не завантажується

**Перевірте шляхи:**

1. Відкрийте `includes/header.php`
2. Переконайтеся, що шлях до CSS правильний:
   ```php
   <link rel="stylesheet" href="/skybooking/css/style.css">
   ```
   або
   ```php
   <link rel="stylesheet" href="/css/style.css">
   ```

### Проблема 5: Сесії не працюють

**Налаштуйте папку для сесій:**

1. Створіть папку `C:\xampp\tmp`
2. Відкрийте `C:\xampp\php\php.ini`
3. Знайдіть `session.save_path`
4. Встановіть: `session.save_path = "C:\xampp\tmp"`
5. Перезапустіть Apache

---

## ✅ Крок 7: Тестування системи

### 7.1. Перевірка головної сторінки

- Перейдіть на http://localhost/skybooking/public/index.php
- Має відобразитися красива сторінка з градієнтом

### 7.2. Тестування реєстрації

1. Перейдіть на http://localhost/skybooking/public/register.php
2. Заповніть форму:
   - **Ім'я:** Тарас
   - **Прізвище:** Шевченко
   - **Email:** taras@example.com
   - **Телефон:** +380501234567
   - **Пароль:** Test123!@#
3. Натисніть "Зареєструватись"
4. Має з'явитися повідомлення про успіх

### 7.3. Тестування входу

1. Перейдіть на http://localhost/skybooking/public/login.php
2. Введіть email і пароль
3. Після входу маєте побачити ім'я користувача у хедері

### 7.4. Тестування пошуку рейсів

1. Перейдіть на http://localhost/skybooking/public/search.php
2. Оберіть аеропорти відправлення та прибуття
3. Виберіть дату (сьогодні або пізніше)
4. Натисніть "Знайти рейси"

---

## 📊 Крок 8: Перевірка тестових даних

Тестові дані вже додані автоматично при створенні бази! Перевірте:

1. Відкрийте phpMyAdmin
2. Оберіть базу `skybooking_db`
3. Натисніть на таблицю `airlines` - має бути 5 авіакомпаній
4. Натисніть на таблицю `airports` - має бути 8 аеропортів
5. Натисніть на таблицю `flights` - має бути 10 рейсів

**Якщо дані відсутні**, виконайте INSERT запити з Кроку 3 окремо.

---

## 🔐 Безпека для продакшену

Якщо плануєте розміщувати на реальному сервері:

1. ✅ **Змініть паролі** в `config.php`
2. ✅ **Вимкніть display_errors** в PHP
3. ✅ **Використовуйте HTTPS**
4. ✅ **Налаштуйте backup бази даних**
5. ✅ **Обмежте права користувача БД**
6. ✅ **Додайте .env файл** для конфігурації

---

## 📞 Швидкі команди

### Перезапуск Apache:

```powershell
# Через XAMPP Control Panel
Натисніть Stop → Start біля Apache
```

### Перезапуск MySQL:

```powershell
# Через XAMPP Control Panel
Натисніть Stop → Start біля MySQL
```

### Очистка кешу сесій:

```powershell
Remove-Item "C:\xampp\tmp\sess_*"
```

---

## 🎯 Готово!

Якщо все налаштовано правильно:

- ✅ Apache працює (зелений в XAMPP)
- ✅ MySQL працює (зелений в XAMPP)
- ✅ База даних створена через SQL скрипт
- ✅ config.php налаштовано для MySQL
- ✅ Тестові дані додані
- ✅ Сайт відкривається в браузері

**Ваш застосунок доступний за адресою:**

- http://localhost/skybooking/public/index.php
  або
- http://localhost/public/index.php

**Якщо виникли проблеми** - перевірте розділ "Вирішення можливих проблем" вище! 🚀
