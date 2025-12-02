# 🚀 ЩО ВСТАВЛЯТИ В MYSQL - ПОКРОКОВА ІНСТРУКЦІЯ

## Варіант 1: Через phpMyAdmin (НАЙПРОСТІШИЙ) ⭐

### Крок 1: Відкрий phpMyAdmin

1. Запусти XAMPP Control Panel
2. Натисни **Start** біля Apache
3. Натисни **Start** біля MySQL
4. Натисни **Admin** біля MySQL (відкриється phpMyAdmin в браузері)

### Крок 2: Виконай SQL скрипт

1. В phpMyAdmin зверху натисни вкладку **SQL**
2. Відкрий файл `database_setup.sql` (він в папці проєкту)
3. **СКОПІЮЙ ВЕСЬ ВМІСТ** файлу
4. **ВСТАВЬ** в поле SQL запиту
5. Натисни кнопку **Виконати** (Go)

### Крок 3: Перевір результат

Після виконання побачиш:

- ✅ База даних `skybooking_db` створена
- ✅ 8 таблиць створено
- ✅ Додано 8 авіакомпаній
- ✅ Додано 10 аеропортів
- ✅ Додано 15 рейсів

---

## Варіант 2: Через командний рядок MySQL

### Крок 1: Відкрий термінал MySQL

```powershell
# Перейди в папку MySQL
cd C:\xampp\mysql\bin

# Запусти MySQL клієнт
.\mysql.exe -u root -p
```

Якщо запитає пароль - просто натисни **Enter** (пароль пустий за замовчуванням)

### Крок 2: Виконай команди по черзі

#### 2.1. Створи базу даних

```sql
CREATE DATABASE IF NOT EXISTS skybooking_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

#### 2.2. Вибери базу даних

```sql
USE skybooking_db;
```

#### 2.3. Створи таблиці (копіюй по одній)

**Таблиця customers:**

```sql
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
```

**Таблиця passengers:**

```sql
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
```

**Таблиця airlines:**

```sql
CREATE TABLE IF NOT EXISTS airlines (
    airline_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    iata_code VARCHAR(3) NOT NULL UNIQUE,
    country VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_iata (iata_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Таблиця airports:**

```sql
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
```

**Таблиця flights:**

```sql
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
```

**Таблиця bookings:**

```sql
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
```

**Таблиця tickets:**

```sql
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
```

**Таблиця payments:**

```sql
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
```

#### 2.4. Додай тестові дані

**Авіакомпанії:**

```sql
INSERT INTO airlines (name, iata_code, country) VALUES
('Ukraine International Airlines', 'PS', 'Ukraine'),
('Ryanair', 'FR', 'Ireland'),
('Wizz Air', 'W6', 'Hungary'),
('Lufthansa', 'LH', 'Germany'),
('Air France', 'AF', 'France'),
('Turkish Airlines', 'TK', 'Turkey'),
('Emirates', 'EK', 'UAE'),
('LOT Polish Airlines', 'LO', 'Poland');
```

**Аеропорти:**

```sql
INSERT INTO airports (name, city, country, iata_code, timezone) VALUES
('Бориспіль', 'Київ', 'Ukraine', 'KBP', 'Europe/Kiev'),
('Шарль де Голль', 'Париж', 'France', 'CDG', 'Europe/Paris'),
('Хітроу', 'Лондон', 'UK', 'LHR', 'Europe/London'),
('Мюнхен', 'Мюнхен', 'Germany', 'MUC', 'Europe/Berlin'),
('Схіпхол', 'Амстердам', 'Netherlands', 'AMS', 'Europe/Amsterdam'),
('Відень', 'Відень', 'Austria', 'VIE', 'Europe/Vienna'),
('Варшава', 'Варшава', 'Poland', 'WAW', 'Europe/Warsaw'),
('Прага', 'Прага', 'Czech Republic', 'PRG', 'Europe/Prague'),
('Стамбул', 'Стамбул', 'Turkey', 'IST', 'Europe/Istanbul'),
('Дубай', 'Дубай', 'UAE', 'DXB', 'Asia/Dubai');
```

**Рейси:**

```sql
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id,
                     departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS101', 1, 2, '2025-12-10 08:00:00', '2025-12-10 11:30:00', 2500.00, 180, 'scheduled'),
(2, 'FR202', 1, 3, '2025-12-10 10:00:00', '2025-12-10 12:45:00', 1800.00, 189, 'scheduled'),
(3, 'W6303', 1, 6, '2025-12-11 06:30:00', '2025-12-11 08:15:00', 1200.00, 180, 'scheduled'),
(4, 'LH404', 1, 4, '2025-12-11 14:00:00', '2025-12-11 16:30:00', 3200.00, 150, 'scheduled'),
(6, 'TK505', 1, 9, '2025-12-12 05:00:00', '2025-12-12 07:30:00', 1500.00, 200, 'scheduled'),
(7, 'EK606', 1, 10, '2025-12-13 02:00:00', '2025-12-13 09:00:00', 5500.00, 350, 'scheduled'),
(8, 'LO707', 1, 7, '2025-12-14 12:00:00', '2025-12-14 13:15:00', 1400.00, 180, 'scheduled'),
(1, 'PS108', 1, 8, '2025-12-15 16:00:00', '2025-12-15 17:30:00', 1600.00, 180, 'scheduled'),
(5, 'AF501', 2, 1, '2025-12-12 09:00:00', '2025-12-12 14:30:00', 2800.00, 200, 'scheduled'),
(1, 'PS102', 3, 1, '2025-12-12 16:00:00', '2025-12-12 21:00:00', 2600.00, 180, 'scheduled'),
(2, 'FR203', 5, 1, '2025-12-13 07:00:00', '2025-12-13 11:00:00', 1500.00, 189, 'scheduled'),
(3, 'W6304', 6, 1, '2025-12-13 12:00:00', '2025-12-13 13:45:00', 1100.00, 180, 'scheduled'),
(4, 'LH405', 4, 1, '2025-12-14 18:00:00', '2025-12-14 20:30:00', 3100.00, 150, 'scheduled'),
(6, 'TK506', 9, 1, '2025-12-15 20:00:00', '2025-12-15 22:30:00', 1600.00, 200, 'scheduled'),
(7, 'EK607', 10, 1, '2025-12-16 22:00:00', '2025-12-17 05:00:00', 5800.00, 350, 'scheduled');
```

### Крок 3: Перевір результат

```sql
-- Покажи всі таблиці
SHOW TABLES;

-- Перевір кількість записів
SELECT 'airlines' AS table_name, COUNT(*) AS records FROM airlines
UNION ALL
SELECT 'airports', COUNT(*) FROM airports
UNION ALL
SELECT 'flights', COUNT(*) FROM flights;
```

Має показати:

- airlines: 8 записів
- airports: 10 записів
- flights: 15 записів

---

## ✅ ЩО РОБИТИ ДАЛІ

1. **Файл config.php вже змінено** - тепер він підключається до MySQL
2. **Відкрий сайт:** http://localhost/skybooking/public/index.php
3. **Зареєструйся** на сайті
4. **Протестуй пошук рейсів**

---

## 🔥 ШВИДКИЙ СПОСІБ (через файл)

Якщо не хочеш копіювати команди вручну:

### PowerShell:

```powershell
cd "C:\Users\musia\OneDrive\Робочий стіл\Course"
& "C:\xampp\mysql\bin\mysql.exe" -u root < database_setup.sql
```

### CMD:

```cmd
cd "C:\Users\musia\OneDrive\Робочий стіл\Course"
"C:\xampp\mysql\bin\mysql.exe" -u root < database_setup.sql
```

Це виконає ВЕСЬ скрипт автоматично! 🚀

---

## 🐛 Якщо щось пішло не так

### Помилка: "Database already exists"

```sql
DROP DATABASE IF EXISTS skybooking_db;
```

Потім заново виконай скрипт.

### Помилка: "Access denied"

Перевір, що MySQL запущено в XAMPP Control Panel.

### Таблиці не з'явилися

```sql
USE skybooking_db;
SHOW TABLES;
```

Якщо пусто - заново виконай CREATE TABLE команди.
