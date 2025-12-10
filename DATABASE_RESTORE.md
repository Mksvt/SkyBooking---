# 🗄️ SkyBooking Database Backup

**Дата створення:** 10 грудня 2025  
**Версія:** 1.0  
**База даних:** skybooking_db

---

## 📋 Зміст бази даних

### Таблиці (8):

1. **customers** - Клієнти (користувачі системи)
2. **passengers** - Пасажири (дані для квитків)
3. **airlines** - Авіакомпанії
4. **airports** - Аеропорти (23 аеропорти по всьому світу з GPS координатами)
5. **flights** - Рейси (~74 рейси)
6. **bookings** - Бронювання
7. **tickets** - Квитки
8. **payments** - Платежі

### Дані:

- **23 аеропорти** в 17 країнах на 6 континентах
- **10 авіакомпаній** (Ukraine International, LOT Polish, Lufthansa, Air France, British Airways, KLM, Turkish Airlines, Iberia, Austrian Airlines, Emirates)
- **~74 активних рейси** (міжнародні, міжконтинентальні, внутрішні)
- **Координати GPS** для всіх аеропортів (latitude, longitude)
- **Адміністратор:** admin@admin.com / SkyB0oking@Adm1n2025!

---

## 🚀 Швидке встановлення

### Варіант 1: Через командний рядок

```bash
# Windows (PowerShell)
cmd /c "c:\xampp\mysql\bin\mysql.exe -u root < c:\xampp\htdocs\skybooking\skybooking_db_backup.sql"

# Linux/Mac
mysql -u root -p < skybooking_db_backup.sql
```

### Варіант 2: Через phpMyAdmin

1. Відкрийте http://localhost/phpmyadmin
2. Натисніть "Імпорт"
3. Оберіть файл `skybooking_db_backup.sql`
4. Натисніть "Вперед"

### Варіант 3: Покрокове відновлення

```sql
-- Крок 1: Видалити стару базу (якщо є)
DROP DATABASE IF EXISTS skybooking_db;

-- Крок 2: Імпортувати дамп
mysql -u root < skybooking_db_backup.sql
```

---

## 📊 Структура таблиць

### customers

```sql
- customer_id (PK, AUTO_INCREMENT)
- first_name, last_name
- email (UNIQUE)
- phone
- date_of_birth
- nationality
- password_hash
- is_admin (NEW! для доступу до адмін-панелі)
- created_at, updated_at
```

### airports (з координатами!)

```sql
- airport_id (PK)
- name, city, country
- iata_code (3 символи, UNIQUE)
- latitude (DECIMAL 10,6) ← НОВОЕ
- longitude (DECIMAL 10,6) ← НОВОЕ
- timezone
- created_at
```

### flights

```sql
- flight_id (PK)
- flight_number
- airline_id → airlines
- departure_airport_id → airports
- arrival_airport_id → airports
- departure_time, arrival_time
- available_seats
- base_price
- created_at, updated_at
```

### tickets

```sql
- ticket_id (PK)
- booking_id → bookings
- flight_id → flights
- passenger_id → passengers
- seat_number
- travel_class (economy/business/first)
- ticket_price
- ticket_status (active/used/cancelled)
- issued_at
```

---

## 🌍 Географічне покриття

### Європа (13 аеропортів):

- 🇺🇦 Київ (KBP), Львів (LWO), Одеса (ODS)
- 🇵🇱 Варшава (WAW)
- 🇩🇪 Берлін (BER), Мюнхен (MUC)
- 🇫🇷 Париж (CDG)
- 🇬🇧 Лондон (LHR)
- 🇳🇱 Амстердам (AMS)
- 🇮🇹 Рим (FCO)
- 🇪🇸 Мадрід (MAD)
- 🇦🇹 Відень (VIE)
- 🇨🇿 Прага (PRG)

### Азія (3 аеропорти):

- 🇹🇷 Стамбул (IST)
- 🇦🇪 Дубай (DXB)
- 🇯🇵 Токіо (HND)
- 🇸🇬 Сінгапур (SIN)
- 🇭🇰 Гонконг (HKG)
- 🇨🇳 Пекін (PEK)

### Америка (2 аеропорти):

- 🇺🇸 Нью-Йорк (JFK), Лос-Анджелес (LAX)

### Океанія (1 аеропорт):

- 🇦🇺 Сідней (SYD)

### Південна Америка (1 аеропорт):

- 🇧🇷 Сан-Паулу (GRU)

---

## 🔐 Облікові записи

### Адміністратор:

- **Email:** admin@admin.com
- **Пароль:** SkyB0oking@Adm1n2025!
- **Права:** Повний доступ до адмін-панелі

### Тестові користувачі:

Створіть нові через форму реєстрації

---

## ⚙️ Технічні деталі

### Кодування:

- **Charset:** utf8mb4
- **Collation:** utf8mb4_general_ci

### Двигун:

- **Engine:** InnoDB
- **Foreign Keys:** Увімкнено CASCADE

### Безпека:

- Паролі хешовані через `PASSWORD_DEFAULT` (bcrypt)
- CSRF токени для форм
- Prepared statements для SQL

---

## 🔄 Оновлення з попередніх версій

Якщо у вас стара версія без координат:

```sql
-- Додати поля координат
ALTER TABLE airports
ADD COLUMN latitude DECIMAL(10, 6) NULL AFTER iata_code,
ADD COLUMN longitude DECIMAL(10, 6) NULL AFTER latitude;

-- Додати поле is_admin
ALTER TABLE customers
ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;
```

---

## 📝 Примітки

1. **Координати аеропортів** додані для роботи інтерактивної карти рейсів
2. **is_admin** додано для розмежування прав доступу
3. Всі рейси мають дату >= поточної (автоматично фільтруються)
4. Тестові дані включають міжконтинентальні маршрути

---

## 🆘 Відновлення у разі помилок

```bash
# Якщо щось пішло не так:
mysql -u root -e "DROP DATABASE skybooking_db;"
mysql -u root < skybooking_db_backup.sql

# Або через XAMPP:
c:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS skybooking_db;"
cmd /c "c:\xampp\mysql\bin\mysql.exe -u root < c:\xampp\htdocs\skybooking\skybooking_db_backup.sql"
```

---

## 📞 Контакти

**Проект:** SkyBooking - Система бронювання авіаквитків  
**GitHub:** SkyBooking---  
**Розробник:** Mksvt

---

**Створено:** 10.12.2025 23:23  
**Файл:** `skybooking_db_backup.sql` (53 KB)
