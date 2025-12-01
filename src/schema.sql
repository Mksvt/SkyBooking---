-- (опціонально) створюємо схему
-- CREATE SCHEMA aviation_booking;
-- SET search_path TO aviation_booking;

------------------------------------------------------------
-- 1. КЛІЄНТИ СИСТЕМИ (акаунти)
------------------------------------------------------------
CREATE TABLE customers (
    customer_id      INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    first_name       VARCHAR(50)  NOT NULL,
    last_name        VARCHAR(50)  NOT NULL,
    email            VARCHAR(100) NOT NULL UNIQUE,
    phone            VARCHAR(20),
    date_of_birth    DATE,
    nationality      VARCHAR(50)
);

------------------------------------------------------------
-- 2. ПАСАЖИРИ (можуть відрізнятися від власника акаунта)
------------------------------------------------------------
CREATE TABLE passengers (
    passenger_id     INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    customer_id      INT NOT NULL,
    first_name       VARCHAR(50)  NOT NULL,
    last_name        VARCHAR(50)  NOT NULL,
    date_of_birth    DATE,
    passport_number  VARCHAR(20),
    nationality      VARCHAR(50),
    
    CONSTRAINT fk_passengers_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(customer_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

------------------------------------------------------------
-- 3. АВІАКОМПАНІЇ
------------------------------------------------------------
CREATE TABLE airlines (
    airline_id   INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    iata_code    CHAR(2)      NOT NULL,
    icao_code    CHAR(3),
    country      VARCHAR(50),
    
    CONSTRAINT uq_airlines_iata UNIQUE (iata_code),
    CONSTRAINT uq_airlines_icao UNIQUE (icao_code)
);

------------------------------------------------------------
-- 4. АЕРОПОРТИ
------------------------------------------------------------
CREATE TABLE airports (
    airport_id   INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    iata_code    CHAR(3)      NOT NULL,
    city         VARCHAR(100) NOT NULL,
    country      VARCHAR(50)  NOT NULL,
    time_zone    VARCHAR(50)  NOT NULL,
    
    CONSTRAINT uq_airports_iata UNIQUE (iata_code)
);

------------------------------------------------------------
-- 5. РЕЙСИ
------------------------------------------------------------
CREATE TABLE flights (
    flight_id            INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    airline_id           INT NOT NULL,
    flight_number        VARCHAR(10) NOT NULL,
    departure_airport_id INT NOT NULL,
    arrival_airport_id   INT NOT NULL,
    departure_time       TIMESTAMP NOT NULL,
    arrival_time         TIMESTAMP NOT NULL,
    base_price           NUMERIC(10,2) NOT NULL,
    status               VARCHAR(20)   NOT NULL,
    
    CONSTRAINT fk_flights_airline
        FOREIGN KEY (airline_id)
        REFERENCES airlines(airline_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    
    CONSTRAINT fk_flights_departure_airport
        FOREIGN KEY (departure_airport_id)
        REFERENCES airports(airport_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_flights_arrival_airport
        FOREIGN KEY (arrival_airport_id)
        REFERENCES airports(airport_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    -- Заборона рейсу з однакового аеропорту вильоту/прильоту
    CONSTRAINT chk_flights_airports_different
        CHECK (departure_airport_id <> arrival_airport_id),

    -- Один і той самий рейс авіакомпанії в один і той же час унікальний
    CONSTRAINT uq_flights_unique_departure
        UNIQUE (airline_id, flight_number, departure_time)
);

------------------------------------------------------------
-- 6. БРОНЮВАННЯ (ЗАМОВЛЕННЯ)
------------------------------------------------------------
CREATE TABLE bookings (
    booking_id      INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    customer_id     INT NOT NULL,
    booking_date    TIMESTAMP NOT NULL,
    status          VARCHAR(20) NOT NULL,  -- 'pending','confirmed','cancelled'
    total_amount    NUMERIC(10,2) NOT NULL,
    payment_status  VARCHAR(20) NOT NULL,  -- 'unpaid','partially_paid','paid'
    currency        CHAR(3) NOT NULL,      -- 'USD','EUR','UAH',...
    
    CONSTRAINT fk_bookings_customer
        FOREIGN KEY (customer_id)
        REFERENCES customers(customer_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_bookings_total_amount
        CHECK (total_amount >= 0),

    CONSTRAINT chk_bookings_currency
        CHECK (currency IN ('USD','EUR','UAH'))
);

------------------------------------------------------------
-- 7. КВИТКИ
------------------------------------------------------------
CREATE TABLE tickets (
    ticket_id        INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    booking_id       INT NOT NULL,
    flight_id        INT NOT NULL,
    passenger_id     INT NOT NULL,
    seat_number      VARCHAR(5),    -- наприклад '12A'
    travel_class     VARCHAR(20) NOT NULL, -- 'economy','business','first',...
    ticket_price     NUMERIC(10,2) NOT NULL,
    ticket_status    VARCHAR(20) NOT NULL, -- 'active','used','refunded','cancelled'
    
    CONSTRAINT fk_tickets_booking
        FOREIGN KEY (booking_id)
        REFERENCES bookings(booking_id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_tickets_flight
        FOREIGN KEY (flight_id)
        REFERENCES flights(flight_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_tickets_passenger
        FOREIGN KEY (passenger_id)
        REFERENCES passengers(passenger_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_tickets_price
        CHECK (ticket_price >= 0),

    -- Одне місце на рейсі для одного пасажира тільки один раз
    CONSTRAINT uq_tickets_unique_seat
        UNIQUE (flight_id, seat_number)
);

------------------------------------------------------------
-- 8. ПЛАТЕЖІ
------------------------------------------------------------
CREATE TABLE payments (
    payment_id      INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    booking_id      INT NOT NULL,
    payment_date    TIMESTAMP NOT NULL,
    amount          NUMERIC(10,2) NOT NULL,
    payment_method  VARCHAR(20) NOT NULL,  -- 'card','paypal','bank_transfer',...
    transaction_id  VARCHAR(50),
    payment_status  VARCHAR(20) NOT NULL,  -- 'success','failed','pending','refunded'
    currency        CHAR(3) NOT NULL,

    CONSTRAINT fk_payments_booking
        FOREIGN KEY (booking_id)
        REFERENCES bookings(booking_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT uq_payments_transaction
        UNIQUE (transaction_id),

    CONSTRAINT chk_payments_amount
        CHECK (amount > 0),

    CONSTRAINT chk_payments_currency
        CHECK (currency IN ('USD','EUR','UAH'))
);

------------------------------------------------------------
-- ІНДЕКСИ ДЛЯ ШВИДКИХ ЗАПИТІВ
------------------------------------------------------------

-- Часто будемо шукати бронювання по клієнту та даті
CREATE INDEX idx_bookings_customer_date
    ON bookings (customer_id, booking_date);

-- Часто шукатимемо рейси по аеропорту та часу вильоту
CREATE INDEX idx_flights_departure
    ON flights (departure_airport_id, departure_time);

-- Швидко діставати всі квитки по рейсу
CREATE INDEX idx_tickets_flight
    ON tickets (flight_id);

-- Швидко діставати платежі по бронюванню
CREATE INDEX idx_payments_booking
    ON payments (booking_id);

-- Швидко діставати пасажирів клієнта
CREATE INDEX idx_passengers_customer
    ON passengers (customer_id);
