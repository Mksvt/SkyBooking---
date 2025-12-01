# Довідка для розробників

## 🏗️ Архітектура проекту

### Технологічний стек
- **Backend:** PHP 7.4+
- **Database:** PostgreSQL 12+
- **Frontend:** HTML5, CSS3
- **No JavaScript:** Вся логіка на стороні сервера

### Паттерни проектування
- **MVC-подібна структура:** Розділення логіки, представлення та даних
- **Include pattern:** Переиспользование header/footer
- **Session management:** Збереження стану користувача
- **Prepared Statements:** Безпека від SQL-ін'єкцій

---

## 📂 Структура файлів

```
Course/
├── public/                    # Веб-корінь (DocumentRoot)
│   ├── css/
│   │   └── style.css         # Всі стилі
│   ├── images/               # Зображення
│   ├── index.php             # Лендінг
│   ├── search.php            # Форма пошуку
│   ├── flights.php           # Список рейсів
│   ├── select-flight.php     # Обробка вибору рейсу
│   ├── seats.php             # Вибір місць
│   ├── passengers.php        # Форма пасажирів
│   ├── booking.php           # Підтвердження
│   ├── payment.php           # Оплата
│   ├── ticket.php            # Квиток з QR
│   ├── my-bookings.php       # Історія
│   ├── register.php          # Реєстрація
│   ├── login.php             # Вхід
│   ├── logout.php            # Вихід
│   └── .htaccess             # Apache config
├── includes/
│   ├── config.php            # БД + сесії + функції
│   ├── header.php            # Шапка
│   └── footer.php            # Футер
├── src/
│   ├── schema.sql            # Схема БД
│   ├── test_data.sql         # Тестові дані
│   └── desc.md               # Опис БД
├── README.md                 # Головна документація
├── DATABASE_SETUP.md         # Налаштування БД
├── USER_GUIDE.md             # Інструкція користувача
└── start.bat                 # Швидкий запуск
```

---

## 🔄 Потік даних (User Flow)

```
1. index.php (лендінг)
   ↓
2. search.php (форма пошуку)
   ↓ GET параметри
3. flights.php (список рейсів)
   ↓ POST flight_id
4. select-flight.php (перевірка авторизації)
   ↓
5. login.php або register.php (якщо не авторизований)
   ↓
6. seats.php (вибір місць)
   ↓ POST selected_seats[]
7. passengers.php (дані пасажирів)
   ↓ POST passengers_data
8. booking.php (створення бронювання в БД)
   ↓
9. payment.php (створення платежу в БД)
   ↓
10. ticket.php (відображення квитка)
```

---

## 🗃️ Структура сесії

```php
$_SESSION = [
    'customer_id' => 1,                    // ID користувача
    'customer_name' => 'Тарас Шевченко',   // Ім'я
    'customer_email' => 'test@...',        // Email
    
    'search' => [                          // Параметри пошуку
        'departure_id' => 1,
        'arrival_id' => 4,
        'date' => '2025-12-10',
        'passengers' => 2
    ],
    
    'selected_flight_id' => 5,             // Обраний рейс
    
    'selected_seats' => ['12A', '12B'],    // Обрані місця
    
    'passengers_data' => [                 // Дані пасажирів
        [
            'first_name' => 'Тарас',
            'last_name' => 'Шевченко',
            'date_of_birth' => '1990-01-15',
            'passport_number' => 'АА123456',
            'nationality' => 'Україна'
        ],
        // ...
    ],
    
    'current_booking_id' => 10,            // Поточне бронювання
    
    'redirect_after_login' => '/public/seats.php'  // Куди повернутись
];
```

---

## 🔐 Безпека

### Реалізовані заходи:

1. **SQL Injection Protection**
   ```php
   $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
   $stmt->execute([$email]);
   ```

2. **Password Hashing**
   ```php
   $hash = password_hash($password, PASSWORD_DEFAULT);
   password_verify($password, $hash);
   ```

3. **XSS Protection**
   ```php
   echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
   ```

4. **CSRF Protection**
   - Перевірка `$_SERVER['REQUEST_METHOD']`
   - Валідація на стороні сервера

5. **Session Security**
   ```php
   session_start();
   session_regenerate_id(true);
   ```

### Рекомендації для production:

- [ ] Використовувати HTTPS
- [ ] Додати CSRF tokens
- [ ] Встановити rate limiting
- [ ] Логування спроб входу
- [ ] Налаштувати secure cookies
- [ ] Валідація file uploads
- [ ] Content Security Policy headers

---

## 🎨 CSS Структура

### CSS Variables (Custom Properties)
```css
:root {
    --primary-color: #2563eb;
    --secondary-color: #1e40af;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
    --light-bg: #f8fafc;
    --dark-text: #1e293b;
    --gray-text: #64748b;
    --border-color: #e2e8f0;
}
```

### Основні класи:

- `.container` - обмежена ширина контенту
- `.section` - секція сторінки
- `.btn` - кнопки (primary, secondary, success, danger)
- `.form-control` - поля форм
- `.flight-card` - картка рейсу
- `.seat` - місце в літаку
- `.alert` - повідомлення (success, error, info, warning)

---

## 🗄️ Схема бази даних

### Основні таблиці:

```sql
customers       -- Клієнти
  ↓
passengers      -- Пасажири (FK: customer_id)
  ↓
bookings        -- Бронювання (FK: customer_id)
  ↓
tickets         -- Квитки (FK: booking_id, passenger_id, flight_id)
  ↓
payments        -- Платежі (FK: booking_id)

airports        -- Аеропорти
  ↓
flights         -- Рейси (FK: airline_id, departure/arrival_airport_id)
  ↓
airlines        -- Авіакомпанії
```

### Важливі зв'язки:

- Один клієнт → багато пасажирів (1:N)
- Один клієнт → багато бронювань (1:N)
- Одне бронювання → багато квитків (1:N)
- Один рейс → багато квитків (1:N)
- Один пасажир → багато квитків (1:N)

---

## 🔧 Функції допоміжні (config.php)

```php
// Перевірка авторизації
isLoggedIn(): bool

// Вимагати авторизацію (redirect)
requireLogin(): void

// Вихід з системи
logout(): void
```

---

## 📝 Додавання нового функціоналу

### Приклад: Додати скасування бронювання

1. **Додати кнопку в my-bookings.php:**
   ```php
   <form method="POST" action="/public/cancel-booking.php">
       <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
       <button type="submit" class="btn btn-danger">Скасувати</button>
   </form>
   ```

2. **Створити cancel-booking.php:**
   ```php
   <?php
   require_once '../includes/config.php';
   requireLogin();
   
   $booking_id = $_POST['booking_id'] ?? null;
   
   // Перевірка власності
   $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ? AND customer_id = ?");
   $stmt->execute([$booking_id, $_SESSION['customer_id']]);
   
   if ($stmt->fetch()) {
       // Оновити статус
       $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?")
           ->execute([$booking_id]);
   }
   
   header('Location: /public/my-bookings.php');
   ?>
   ```

---

## 🧪 Тестування

### Тестові сценарії:

1. **Позитивний флоу:**
   - Пошук → Вибір → Авторизація → Місця → Пасажири → Бронювання → Оплата → Квиток

2. **Негативні тести:**
   - Спроба доступу без авторизації
   - Вибір зайнятого місця
   - Помилкові дані пасажирів
   - Невалідна оплата

3. **Перевірка безпеки:**
   - SQL injection спроби
   - XSS атаки
   - CSRF атаки
   - Маніпуляція сесією

---

## 🚀 Оптимізація

### Performance:

1. **Database:**
   - Індекси на частозапитувані поля
   - EXPLAIN для аналізу запитів
   - Connection pooling

2. **PHP:**
   - OpCache для кешування
   - Мінімізація DB запитів
   - Lazy loading даних

3. **Frontend:**
   - CSS compression
   - Image optimization
   - Browser caching

---

## 📊 Логування та моніторинг

### Рекомендації:

```php
// Логування помилок
error_log("Error in payment: " . $e->getMessage());

// Логування дій користувача
file_put_contents('logs/user_actions.log', 
    date('Y-m-d H:i:s') . " - User {$_SESSION['customer_id']} booked flight {$flight_id}\n",
    FILE_APPEND
);

// Моніторинг продуктивності
$start = microtime(true);
// ... код ...
$time = microtime(true) - $start;
error_log("Execution time: {$time}s");
```

---

## 🔄 API для майбутнього розширення

Можна додати REST API:

```php
// api/flights.php
header('Content-Type: application/json');

$flights = $pdo->query("SELECT * FROM flights WHERE status = 'scheduled'")->fetchAll();
echo json_encode($flights);
```

---

## 📚 Корисні ресурси

- [PHP Documentation](https://www.php.net/docs.php)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [MDN Web Docs](https://developer.mozilla.org/)
- [OWASP Security](https://owasp.org/)

---

## 🤝 Contribution Guidelines

При внесенні змін:

1. Дотримуйтесь PSR-12 coding standards
2. Коментуйте складну логіку
3. Використовуйте prepared statements
4. Валідуйте всі user inputs
5. Тестуйте зміни локально

---

**Happy Coding! 💻**
