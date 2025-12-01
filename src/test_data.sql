-- Тестові дані для системи бронювання авіаквитків

-- Очищення таблиць (опціонально)
-- TRUNCATE TABLE payments, tickets, bookings, passengers, customers, flights, airlines, airports RESTART IDENTITY CASCADE;

------------------------------------------------------------
-- АЕРОПОРТИ
------------------------------------------------------------
INSERT INTO airports (name, iata_code, city, country, time_zone) VALUES
('Міжнародний аеропорт "Бориспіль"', 'KBP', 'Київ', 'Україна', 'Europe/Kiev'),
('Міжнародний аеропорт "Львів"', 'LWO', 'Львів', 'Україна', 'Europe/Kiev'),
('Міжнародний аеропорт "Одеса"', 'ODS', 'Одеса', 'Україна', 'Europe/Kiev'),
('Варшава Шопен', 'WAW', 'Варшава', 'Польща', 'Europe/Warsaw'),
('Берлін Бранденбург', 'BER', 'Берлін', 'Німеччина', 'Europe/Berlin'),
('Шарль де Голль', 'CDG', 'Париж', 'Франція', 'Europe/Paris'),
('Хітроу', 'LHR', 'Лондон', 'Великобританія', 'Europe/London'),
('Схіпхол', 'AMS', 'Амстердам', 'Нідерланди', 'Europe/Amsterdam'),
('Фьюміччино', 'FCO', 'Рим', 'Італія', 'Europe/Rome'),
('Барахас', 'MAD', 'Мадрид', 'Іспанія', 'Europe/Madrid'),
('Ататюрк', 'IST', 'Стамбул', 'Туреччина', 'Europe/Istanbul'),
('Дубай', 'DXB', 'Дубай', 'ОАЕ', 'Asia/Dubai');

------------------------------------------------------------
-- АВІАКОМПАНІЇ
------------------------------------------------------------
INSERT INTO airlines (name, iata_code, icao_code, country) VALUES
('Ukraine International Airlines', 'PS', 'UIA', 'Україна'),
('LOT Polish Airlines', 'LO', 'LOT', 'Польща'),
('Lufthansa', 'LH', 'DLH', 'Німеччина'),
('Air France', 'AF', 'AFR', 'Франція'),
('British Airways', 'BA', 'BAW', 'Великобританія'),
('KLM Royal Dutch Airlines', 'KL', 'KLM', 'Нідерланди'),
('Turkish Airlines', 'TK', 'THY', 'Туреччина'),
('Emirates', 'EK', 'UAE', 'ОАЕ'),
('Wizz Air', 'W6', 'WZZ', 'Угорщина'),
('Ryanair', 'FR', 'RYR', 'Ірландія');

------------------------------------------------------------
-- РЕЙСИ (Київ - різні напрямки)
------------------------------------------------------------
-- Київ → Варшава
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(1, 'PS101', 1, 4, '2025-12-10 08:00:00', '2025-12-10 09:30:00', 2500.00, 'scheduled'),
(2, 'LO755', 1, 4, '2025-12-10 14:30:00', '2025-12-10 16:00:00', 2800.00, 'scheduled'),
(9, 'W6201', 1, 4, '2025-12-10 18:00:00', '2025-12-10 19:30:00', 1900.00, 'scheduled');

-- Київ → Берлін
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(1, 'PS103', 1, 5, '2025-12-10 09:00:00', '2025-12-10 11:00:00', 3200.00, 'scheduled'),
(3, 'LH1490', 1, 5, '2025-12-10 13:00:00', '2025-12-10 15:00:00', 3500.00, 'scheduled');

-- Київ → Париж
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(1, 'PS115', 1, 6, '2025-12-10 10:00:00', '2025-12-10 13:00:00', 4500.00, 'scheduled'),
(4, 'AF1653', 1, 6, '2025-12-10 16:00:00', '2025-12-10 19:00:00', 4800.00, 'scheduled');

-- Київ → Лондон
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(1, 'PS107', 1, 7, '2025-12-10 11:00:00', '2025-12-10 13:30:00', 5200.00, 'scheduled'),
(5, 'BA892', 1, 7, '2025-12-10 15:00:00', '2025-12-10 17:30:00', 5500.00, 'scheduled');

-- Київ → Амстердам
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(1, 'PS309', 1, 8, '2025-12-10 12:00:00', '2025-12-10 14:30:00', 4200.00, 'scheduled'),
(6, 'KL1389', 1, 8, '2025-12-10 17:00:00', '2025-12-10 19:30:00', 4400.00, 'scheduled');

-- Київ → Рим
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(1, 'PS113', 1, 9, '2025-12-10 08:30:00', '2025-12-10 11:30:00', 4800.00, 'scheduled'),
(9, 'W6301', 1, 9, '2025-12-10 19:00:00', '2025-12-10 22:00:00', 3500.00, 'scheduled');

-- Київ → Мадрид
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(1, 'PS119', 1, 10, '2025-12-10 09:30:00', '2025-12-10 13:30:00', 5500.00, 'scheduled');

-- Київ → Стамбул
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(1, 'PS711', 1, 11, '2025-12-10 06:00:00', '2025-12-10 08:30:00', 3800.00, 'scheduled'),
(7, 'TK463', 1, 11, '2025-12-10 10:30:00', '2025-12-10 13:00:00', 4000.00, 'scheduled'),
(7, 'TK465', 1, 11, '2025-12-10 18:00:00', '2025-12-10 20:30:00', 3900.00, 'scheduled');

-- Київ → Дубай
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(1, 'PS371', 1, 12, '2025-12-10 23:00:00', '2025-12-11 05:30:00', 8500.00, 'scheduled'),
(8, 'EK171', 1, 12, '2025-12-10 20:00:00', '2025-12-11 02:30:00', 9200.00, 'scheduled');

-- Львів → Варшава
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(2, 'LO757', 2, 4, '2025-12-10 12:00:00', '2025-12-10 13:00:00', 2200.00, 'scheduled');

-- Львів → Берлін
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(9, 'W6203', 2, 5, '2025-12-10 15:00:00', '2025-12-10 16:30:00', 2500.00, 'scheduled');

-- Одеса → Стамбул
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(7, 'TK473', 3, 11, '2025-12-10 11:00:00', '2025-12-10 13:00:00', 3200.00, 'scheduled');

------------------------------------------------------------
-- ДОДАТКОВІ РЕЙСИ НА ІНШІ ДАТИ
------------------------------------------------------------
-- Київ → Варшава (наступні дні)
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(1, 'PS101', 1, 4, '2025-12-11 08:00:00', '2025-12-11 09:30:00', 2500.00, 'scheduled'),
(1, 'PS101', 1, 4, '2025-12-12 08:00:00', '2025-12-12 09:30:00', 2500.00, 'scheduled'),
(1, 'PS101', 1, 4, '2025-12-13 08:00:00', '2025-12-13 09:30:00', 2600.00, 'scheduled'),
(1, 'PS101', 1, 4, '2025-12-14 08:00:00', '2025-12-14 09:30:00', 2700.00, 'scheduled'),
(1, 'PS101', 1, 4, '2025-12-15 08:00:00', '2025-12-15 09:30:00', 3200.00, 'scheduled');

-- Київ → Париж (тиждень)
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, status) VALUES
(1, 'PS115', 1, 6, '2025-12-11 10:00:00', '2025-12-11 13:00:00', 4500.00, 'scheduled'),
(1, 'PS115', 1, 6, '2025-12-12 10:00:00', '2025-12-12 13:00:00', 4500.00, 'scheduled'),
(1, 'PS115', 1, 6, '2025-12-13 10:00:00', '2025-12-13 13:00:00', 4600.00, 'scheduled'),
(1, 'PS115', 1, 6, '2025-12-14 10:00:00', '2025-12-14 13:00:00', 4800.00, 'scheduled'),
(1, 'PS115', 1, 6, '2025-12-15 10:00:00', '2025-12-15 13:00:00', 5500.00, 'scheduled');

------------------------------------------------------------
-- ТЕСТОВИЙ КЛІЄНТ (пароль: password123)
------------------------------------------------------------
INSERT INTO customers (first_name, last_name, email, phone, date_of_birth, nationality, password_hash) VALUES
('Тарас', 'Шевченко', 'test@skybooking.com', '+380501234567', '1990-01-15', 'Україна', 
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- Пароль: password123

------------------------------------------------------------
-- СТАТИСТИКА
------------------------------------------------------------
SELECT 
    'Аеропортів' as entity, 
    COUNT(*) as count 
FROM airports
UNION ALL
SELECT 
    'Авіакомпаній', 
    COUNT(*) 
FROM airlines
UNION ALL
SELECT 
    'Рейсів', 
    COUNT(*) 
FROM flights
UNION ALL
SELECT 
    'Клієнтів', 
    COUNT(*) 
FROM customers;
