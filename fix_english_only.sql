-- Fix encoding and use English names only
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Clear all data
DELETE FROM payments;
DELETE FROM tickets;
DELETE FROM bookings;
DELETE FROM passengers;
DELETE FROM customers;
DELETE FROM flights;
DELETE FROM airlines;
DELETE FROM airports;

-- Reset AUTO_INCREMENT
ALTER TABLE airports AUTO_INCREMENT = 1;
ALTER TABLE airlines AUTO_INCREMENT = 1;
ALTER TABLE flights AUTO_INCREMENT = 1;
ALTER TABLE customers AUTO_INCREMENT = 1;
ALTER TABLE passengers AUTO_INCREMENT = 1;
ALTER TABLE bookings AUTO_INCREMENT = 1;
ALTER TABLE tickets AUTO_INCREMENT = 1;
ALTER TABLE payments AUTO_INCREMENT = 1;

------------------------------------------------------------
-- AIRPORTS (English names only)
------------------------------------------------------------
INSERT INTO airports (name, iata_code, city, country, timezone) VALUES
('Boryspil International Airport', 'KBP', 'Kyiv', 'Ukraine', 'Europe/Kiev'),
('Lviv International Airport', 'LWO', 'Lviv', 'Ukraine', 'Europe/Kiev'),
('Odesa International Airport', 'ODS', 'Odesa', 'Ukraine', 'Europe/Kiev'),
('Warsaw Chopin Airport', 'WAW', 'Warsaw', 'Poland', 'Europe/Warsaw'),
('Berlin Brandenburg Airport', 'BER', 'Berlin', 'Germany', 'Europe/Berlin'),
('Charles de Gaulle Airport', 'CDG', 'Paris', 'France', 'Europe/Paris'),
('Heathrow Airport', 'LHR', 'London', 'United Kingdom', 'Europe/London'),
('Schiphol Airport', 'AMS', 'Amsterdam', 'Netherlands', 'Europe/Amsterdam'),
('Fiumicino Airport', 'FCO', 'Rome', 'Italy', 'Europe/Rome'),
('Barajas Airport', 'MAD', 'Madrid', 'Spain', 'Europe/Madrid'),
('Ataturk Airport', 'IST', 'Istanbul', 'Turkey', 'Europe/Istanbul'),
('Dubai International Airport', 'DXB', 'Dubai', 'UAE', 'Asia/Dubai'),
('Vienna International Airport', 'VIE', 'Vienna', 'Austria', 'Europe/Vienna'),
('Munich Airport', 'MUC', 'Munich', 'Germany', 'Europe/Berlin'),
('Prague Airport', 'PRG', 'Prague', 'Czech Republic', 'Europe/Prague');

------------------------------------------------------------
-- AIRLINES (English names only)
------------------------------------------------------------
INSERT INTO airlines (name, iata_code, country) VALUES
('Ukraine International Airlines', 'PS', 'Ukraine'),
('LOT Polish Airlines', 'LO', 'Poland'),
('Lufthansa', 'LH', 'Germany'),
('Air France', 'AF', 'France'),
('British Airways', 'BA', 'United Kingdom'),
('KLM Royal Dutch Airlines', 'KL', 'Netherlands'),
('Turkish Airlines', 'TK', 'Turkey'),
('Emirates', 'EK', 'UAE'),
('Wizz Air', 'W6', 'Hungary'),
('Ryanair', 'FR', 'Ireland');

------------------------------------------------------------
-- FLIGHTS
------------------------------------------------------------
-- Kyiv → Warsaw
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS101', 1, 4, '2025-12-10 08:00:00', '2025-12-10 09:30:00', 2500.00, 180, 'scheduled'),
(2, 'LO755', 1, 4, '2025-12-10 14:30:00', '2025-12-10 16:00:00', 2800.00, 150, 'scheduled'),
(10, 'FR201', 1, 4, '2025-12-10 18:00:00', '2025-12-10 19:30:00', 1900.00, 180, 'scheduled');

-- Kyiv → Berlin
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS103', 1, 5, '2025-12-10 09:00:00', '2025-12-10 11:00:00', 3200.00, 180, 'scheduled'),
(3, 'LH1490', 1, 5, '2025-12-10 13:00:00', '2025-12-10 15:00:00', 3500.00, 200, 'scheduled');

-- Kyiv → Paris
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS115', 1, 6, '2025-12-10 10:00:00', '2025-12-10 13:00:00', 4500.00, 180, 'scheduled'),
(4, 'AF1653', 1, 6, '2025-12-10 16:00:00', '2025-12-10 19:00:00', 4800.00, 220, 'scheduled');

-- Kyiv → London
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS107', 1, 7, '2025-12-10 11:00:00', '2025-12-10 13:30:00', 5200.00, 180, 'scheduled'),
(5, 'BA892', 1, 7, '2025-12-10 15:00:00', '2025-12-10 17:30:00', 5500.00, 250, 'scheduled');

-- Kyiv → Amsterdam
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS309', 1, 8, '2025-12-10 12:00:00', '2025-12-10 14:30:00', 4200.00, 180, 'scheduled'),
(6, 'KL1389', 1, 8, '2025-12-10 17:00:00', '2025-12-10 19:30:00', 4400.00, 200, 'scheduled');

-- Kyiv → Rome
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS113', 1, 9, '2025-12-10 08:30:00', '2025-12-10 11:30:00', 4800.00, 180, 'scheduled'),
(9, 'W6301', 1, 9, '2025-12-10 19:00:00', '2025-12-10 22:00:00', 3500.00, 180, 'scheduled');

-- Kyiv → Madrid
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS119', 1, 10, '2025-12-10 09:30:00', '2025-12-10 13:30:00', 5500.00, 180, 'scheduled');

-- Kyiv → Istanbul
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS711', 1, 11, '2025-12-10 06:00:00', '2025-12-10 08:30:00', 3800.00, 180, 'scheduled'),
(7, 'TK463', 1, 11, '2025-12-10 10:30:00', '2025-12-10 13:00:00', 4000.00, 300, 'scheduled'),
(7, 'TK465', 1, 11, '2025-12-10 18:00:00', '2025-12-10 20:30:00', 3900.00, 300, 'scheduled');

-- Kyiv → Dubai
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS371', 1, 12, '2025-12-10 23:00:00', '2025-12-11 05:30:00', 8500.00, 180, 'scheduled'),
(8, 'EK171', 1, 12, '2025-12-10 20:00:00', '2025-12-11 02:30:00', 9200.00, 350, 'scheduled');

-- Kyiv → Prague
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS125', 1, 15, '2025-12-10 07:30:00', '2025-12-10 09:00:00', 2800.00, 180, 'scheduled'),
(9, 'W6203', 1, 15, '2025-12-10 16:00:00', '2025-12-10 17:30:00', 2200.00, 180, 'scheduled');

-- Kyiv → Vienna
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(1, 'PS127', 1, 13, '2025-12-10 08:15:00', '2025-12-10 10:00:00', 3000.00, 180, 'scheduled');

-- Kyiv → Munich
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(3, 'LH2538', 1, 14, '2025-12-10 11:30:00', '2025-12-10 13:30:00', 3800.00, 200, 'scheduled');

-- Lviv → Warsaw
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(2, 'LO757', 2, 4, '2025-12-10 12:00:00', '2025-12-10 13:00:00', 2200.00, 150, 'scheduled'),
(9, 'W6205', 2, 4, '2025-12-10 19:30:00', '2025-12-10 20:30:00', 1700.00, 180, 'scheduled');

-- Odesa → Istanbul
INSERT INTO flights (airline_id, flight_number, departure_airport_id, arrival_airport_id, 
                    departure_time, arrival_time, base_price, available_seats, status) VALUES
(7, 'TK467', 3, 11, '2025-12-10 14:00:00', '2025-12-10 16:30:00', 3500.00, 180, 'scheduled');

------------------------------------------------------------
-- TEST CUSTOMERS
------------------------------------------------------------
INSERT INTO customers (first_name, last_name, email, phone, password_hash) VALUES
('Ivan', 'Petrenko', 'ivan@example.com', '+380501234567', '$2y$10$YourHashedPasswordHere'),
('Olena', 'Kovalenko', 'olena@example.com', '+380677654321', '$2y$10$YourHashedPasswordHere'),
('Petro', 'Shevchenko', 'petro@example.com', '+380931112233', '$2y$10$YourHashedPasswordHere');
