# Інструкція по налаштуванню бази даних PostgreSQL

## Крок 1: Встановлення PostgreSQL

Завантажте та встановіть PostgreSQL з офіційного сайту:
https://www.postgresql.org/download/

Під час встановлення запам'ятайте пароль для користувача `postgres`.

## Крок 2: Створення бази даних

### Варіант А: Через командний рядок (psql)

```bash
# Підключитися до PostgreSQL
psql -U postgres

# Створити базу даних
CREATE DATABASE aviation_booking;

# Підключитися до створеної бази
\c aviation_booking

# Виконати SQL скрипт зі схемою
\i 'шлях/до/проекту/src/schema.sql'

# Виконати SQL скрипт з тестовими даними
\i 'шлях/до/проекту/src/test_data.sql'

# Вийти
\q
```

### Варіант Б: Через pgAdmin (GUI)

1. Відкрийте pgAdmin
2. Підключіться до сервера PostgreSQL
3. Правою кнопкою на "Databases" → "Create" → "Database"
4. Введіть ім'я: `aviation_booking`
5. Натисніть "Save"
6. Відкрийте Query Tool (правою кнопкою на БД → "Query Tool")
7. Відкрийте файл `src/schema.sql` та виконайте (F5)
8. Відкрийте файл `src/test_data.sql` та виконайте (F5)

### Варіант В: Через командний рядок Windows

```cmd
# Перейти до директорії проекту
cd C:\Users\musia\OneDrive\Робочий стіл\Course

# Створити базу даних
createdb -U postgres aviation_booking

# Виконати схему
psql -U postgres -d aviation_booking -f src\schema.sql

# Додати тестові дані
psql -U postgres -d aviation_booking -f src\test_data.sql
```

## Крок 3: Налаштування підключення

Відредагуйте файл `includes/config.php`:

```php
define('DB_HOST', 'localhost');     // Хост БД
define('DB_NAME', 'aviation_booking'); // Ім'я бази даних
define('DB_USER', 'postgres');      // Ваш користувач PostgreSQL
define('DB_PASS', 'your_password'); // Ваш пароль
```

## Крок 4: Перевірка підключення

Запустіть веб-сервер та перейдіть на головну сторінку:

```bash
cd public
php -S localhost:8000
```

Відкрийте: http://localhost:8000

Якщо сторінка завантажилася без помилок підключення до БД - все налаштовано правильно!

## Тестовий акаунт

Після виконання `test_data.sql` буде створено тестовий акаунт:

- **Email:** test@skybooking.com
- **Пароль:** password123

Можете використовувати його для тестування або створити свій через реєстрацію.

## Корисні команди PostgreSQL

### Перегляд всіх баз даних
```sql
\l
```

### Перегляд таблиць
```sql
\dt
```

### Перегляд структури таблиці
```sql
\d table_name
```

### Перегляд даних
```sql
SELECT * FROM airports;
SELECT * FROM airlines;
SELECT * FROM flights;
```

### Видалення всіх даних (УВАГА!)
```sql
TRUNCATE TABLE payments, tickets, bookings, passengers, customers, flights, airlines, airports RESTART IDENTITY CASCADE;
```

### Резервне копіювання БД
```bash
pg_dump -U postgres aviation_booking > backup.sql
```

### Відновлення з резервної копії
```bash
psql -U postgres aviation_booking < backup.sql
```

## Можливі проблеми та рішення

### Помилка: "could not connect to server"

**Рішення:**
- Переконайтеся, що PostgreSQL запущено
- Перевірте, чи правильний порт (за замовчуванням 5432)
- Перевірте налаштування `pg_hba.conf`

### Помилка: "password authentication failed"

**Рішення:**
- Перевірте правильність пароля в `config.php`
- Спробуйте скинути пароль через:
```bash
psql -U postgres
ALTER USER postgres PASSWORD 'new_password';
```

### Помилка: "FATAL: database does not exist"

**Рішення:**
- Створіть базу даних за допомогою `CREATE DATABASE aviation_booking;`

### Помилка: "column does not exist"

**Рішення:**
- Переконайтеся, що виконали `schema.sql`
- Можливо, потрібно видалити та створити таблиці заново

## Структура бази даних

Після виконання скриптів у вас будуть такі таблиці:

1. **customers** - Клієнти системи
2. **passengers** - Пасажири
3. **airlines** - Авіакомпанії
4. **airports** - Аеропорти
5. **flights** - Рейси
6. **bookings** - Бронювання
7. **tickets** - Квитки
8. **payments** - Платежі

Детальний опис схеми дивіться у файлі `src/desc.md`.

## Підтримка

Якщо виникли проблеми з налаштуванням:
1. Перевірте версію PostgreSQL (потрібна 12+)
2. Перевірте версію PHP (потрібна 7.4+)
3. Перевірте, чи встановлено розширення `pdo_pgsql` для PHP
4. Перегляньте логи помилок PostgreSQL
