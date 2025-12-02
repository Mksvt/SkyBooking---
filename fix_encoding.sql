-- Виправлення кодування бази даних
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Видалення старих даних
DELETE FROM payments;
DELETE FROM tickets;
DELETE FROM bookings;
DELETE FROM passengers;
DELETE FROM customers;
DELETE FROM flights;
DELETE FROM airlines;
DELETE FROM airports;

-- Скидання AUTO_INCREMENT
ALTER TABLE airports AUTO_INCREMENT = 1;
ALTER TABLE airlines AUTO_INCREMENT = 1;
ALTER TABLE flights AUTO_INCREMENT = 1;
ALTER TABLE customers AUTO_INCREMENT = 1;
ALTER TABLE passengers AUTO_INCREMENT = 1;
ALTER TABLE bookings AUTO_INCREMENT = 1;
ALTER TABLE tickets AUTO_INCREMENT = 1;
ALTER TABLE payments AUTO_INCREMENT = 1;

------------------------------------------------------------
-- АЕРОПОРТИ
------------------------------------------------------------
INSERT INTO airports (name, iata_code, city, country, timezone) VALUES
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
('Дубай', 'DXB', 'Дубай', 'ОАЕ', 'Asia/Dubai'),
('Відень', 'VIE', 'Відень', 'Австрія', 'Europe/Vienna'),
('Мюнхен', 'MUC', 'Мюнхен', 'Німеччина', 'Europe/Berlin'),
('Прага', 'PRG', 'Прага', 'Чехія', 'Europe/Prague');

------------------------------------------------------------
-- АВІАКОМПАНІЇ
------------------------------------------------------------
INSERT INTO airlines (name, iata_code, country) VALUES
('Ukraine International Airlines', 'PS', 'Україна'),
('LOT Polish Airlines', 'LO', 'Польща'),
('Lufthansa', 'LH', 'Німеччина'),
('Air France', 'AF', 'Франція'),
('British Airways', 'BA', 'Великобританія'),
('KLM Royal Dutch Airlines', 'KL', 'Нідерланди'),
('Turkish Airlines', 'TK', 'Туреччина'),
('Emirates', 'EK', 'ОАЕ'),
('Wizz Air', 'W6', 'Угорщина'),
('Ryanair', 'FR', 'Ірландія');

------------------------------------------------------------
-- РЕЙСИ
------------------------------------------------------------
-- Київ → Варшава
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS101', 1, 4, '2025-12-10 08:00:00', '2025-12-10 09:30:00', 2500.00, 180, 'scheduled'),
(2, 'LO755', 1, 4, '2025-12-10 14:30:00', '2025-12-10 16:00:00', 2800.00, 150, 'scheduled'),
(9, 'W6201', 1, 4, '2025-12-10 18:00:00', '2025-12-10 19:30:00', 1900.00, 180, 'scheduled');

-- Київ → Берлін
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS103', 1, 5, '2025-12-10 09:00:00', '2025-12-10 11:00:00', 3200.00, 180, 'scheduled'),
(3, 'LH1490', 1, 5, '2025-12-10 13:00:00', '2025-12-10 15:00:00', 3500.00, 200, 'scheduled');

-- Київ → Париж
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS115', 1, 6, '2025-12-10 10:00:00', '2025-12-10 13:00:00', 4500.00, 180, 'scheduled'),
(4, 'AF1653', 1, 6, '2025-12-10 16:00:00', '2025-12-10 19:00:00', 4800.00, 220, 'scheduled');

-- Київ → Лондон
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS107', 1, 7, '2025-12-10 11:00:00', '2025-12-10 13:30:00', 5200.00, 180, 'scheduled'),
(5, 'BA892', 1, 7, '2025-12-10 15:00:00', '2025-12-10 17:30:00', 5500.00, 250, 'scheduled');

-- Київ → Амстердам
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS309', 1, 8, '2025-12-10 12:00:00', '2025-12-10 14:30:00', 4200.00, 180, 'scheduled'),
(6, 'KL1389', 1, 8, '2025-12-10 17:00:00', '2025-12-10 19:30:00', 4400.00, 200, 'scheduled');

-- Київ → Рим
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS113', 1, 9, '2025-12-10 08:30:00', '2025-12-10 11:30:00', 4800.00, 180, 'scheduled'),
(9, 'W6301', 1, 9, '2025-12-10 19:00:00', '2025-12-10 22:00:00', 3500.00, 180, 'scheduled');

-- Київ → Мадрид
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS119', 1, 10, '2025-12-10 09:30:00', '2025-12-10 13:30:00', 5500.00, 180, 'scheduled');

-- Київ → Стамбул
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS711', 1, 11, '2025-12-10 06:00:00', '2025-12-10 08:30:00', 3800.00, 180, 'scheduled'),
(7, 'TK463', 1, 11, '2025-12-10 10:30:00', '2025-12-10 13:00:00', 4000.00, 300, 'scheduled'),
(7, 'TK465', 1, 11, '2025-12-10 18:00:00', '2025-12-10 20:30:00', 3900.00, 300, 'scheduled');

-- Київ → Дубай
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS371', 1, 12, '2025-12-10 23:00:00', '2025-12-11 05:30:00', 8500.00, 180, 'scheduled'),
(8, 'EK171', 1, 12, '2025-12-10 20:00:00', '2025-12-11 02:30:00', 9200.00, 350, 'scheduled');

-- Київ → Прага
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS125', 1, 15, '2025-12-10 07:30:00', '2025-12-10 09:00:00', 2800.00, 180, 'scheduled'),
(9, 'W6203', 1, 15, '2025-12-10 16:00:00', '2025-12-10 17:30:00', 2200.00, 180, 'scheduled');

-- Київ → Відень
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS127', 1, 13, '2025-12-10 08:15:00', '2025-12-10 10:00:00', 3000.00, 180, 'scheduled');

-- Київ → Мюнхен
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(3, 'LH2538', 1, 14, '2025-12-10 11:30:00', '2025-12-10 13:30:00', 3800.00, 200, 'scheduled');

-- Львів → Варшава
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(2, 'LO757', 2, 4, '2025-12-10 12:00:00', '2025-12-10 13:00:00', 2200.00, 150, 'scheduled'),
(9, 'W6205', 2, 4, '2025-12-10 19:30:00', '2025-12-10 20:30:00', 1700.00, 180, 'scheduled');

-- Одеса → Стамбул
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(7, 'TK467', 3, 11, '2025-12-10 14:00:00', '2025-12-10 16:30:00', 3500.00, 180, 'scheduled');

------------------------------------------------------------
-- ТЕСТОВІ КЛІЄНТИ
------------------------------------------------------------
INSERT INTO customers (first_name, last_name, email, phone, password_hash) VALUES
('Іван', 'Петренко', 'ivan@example.com', '+380501234567', '$2y$10$YourHashedPasswordHere'),
('Олена', 'Коваленко', 'olena@example.com', '+380677654321', '$2y$10$YourHashedPasswordHere'),
('Петро', 'Шевченко', 'petro@example.com', '+380931112233', '$2y$10$YourHashedPasswordHere');
