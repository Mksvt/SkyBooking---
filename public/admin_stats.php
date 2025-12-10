<?php
require_once '../includes/config.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$page_title = 'Статистика - Адмін-панель';

// Статистика найпопулярніших рейсів
$popularFlights = $pdo->query("
    SELECT 
        f.flight_id,
        f.flight_number,
        al.name as airline_name,
        dep.city as departure_city,
        dep.iata_code as departure_code,
        arr.city as arrival_city,
        arr.iata_code as arrival_code,
        COUNT(DISTINCT t.booking_id) as booking_count,
        COUNT(DISTINCT t.ticket_id) as ticket_count,
        COALESCE(SUM(t.ticket_price), 0) as total_revenue
    FROM flights f
    LEFT JOIN tickets t ON f.flight_id = t.flight_id
    LEFT JOIN airlines al ON f.airline_id = al.airline_id
    LEFT JOIN airports dep ON f.departure_airport_id = dep.airport_id
    LEFT JOIN airports arr ON f.arrival_airport_id = arr.airport_id
    GROUP BY f.flight_id
    ORDER BY booking_count DESC, total_revenue DESC
    LIMIT 10
")->fetchAll();

// Статистика авіакомпаній
$airlineStats = $pdo->query("
    SELECT 
        al.airline_id,
        al.name as airline_name,
        al.iata_code,
        COUNT(DISTINCT f.flight_id) as flights_count,
        COUNT(DISTINCT t.booking_id) as bookings_count,
        COALESCE(SUM(t.ticket_price), 0) as total_revenue
    FROM airlines al
    LEFT JOIN flights f ON al.airline_id = f.airline_id
    LEFT JOIN tickets t ON f.flight_id = t.flight_id
    GROUP BY al.airline_id
    ORDER BY total_revenue DESC
    LIMIT 10
")->fetchAll();

// Статистика оплат
$paymentStats = $pdo->query("
    SELECT 
        payment_status,
        payment_method,
        COUNT(*) as count,
        COALESCE(SUM(amount), 0) as total_amount,
        COALESCE(AVG(amount), 0) as avg_amount
    FROM payments
    GROUP BY payment_status, payment_method
    ORDER BY total_amount DESC
")->fetchAll();

// Загальна статистика оплат
$totalPayments = $pdo->query("
    SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(CASE WHEN payment_status = 'completed' THEN amount ELSE 0 END), 0) as completed_amount,
        COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount,
        COALESCE(SUM(CASE WHEN payment_status = 'failed' THEN amount ELSE 0 END), 0) as failed_amount
    FROM payments
")->fetch();

// Статистика по місяцях (якщо є дані за останні 6 місяців)
$monthlyStats = $pdo->query("
    SELECT 
        DATE_FORMAT(b.booking_date, '%Y-%m') as month,
        COUNT(DISTINCT b.booking_id) as bookings,
        COUNT(DISTINCT t.ticket_id) as tickets,
        COALESCE(SUM(t.ticket_price), 0) as revenue
    FROM bookings b
    LEFT JOIN tickets t ON b.booking_id = t.booking_id
    WHERE b.booking_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
")->fetchAll();

// Топ маршрути
$topRoutes = $pdo->query("
    SELECT 
        CONCAT(dep.city, ' (', dep.iata_code, ') → ', arr.city, ' (', arr.iata_code, ')') as route,
        COUNT(DISTINCT t.booking_id) as bookings,
        COUNT(DISTINCT t.ticket_id) as tickets,
        COALESCE(SUM(t.ticket_price), 0) as revenue
    FROM flights f
    LEFT JOIN tickets t ON f.flight_id = t.flight_id
    JOIN airports dep ON f.departure_airport_id = dep.airport_id
    JOIN airports arr ON f.arrival_airport_id = arr.airport_id
    GROUP BY route
    ORDER BY bookings DESC
    LIMIT 10
")->fetchAll();

// Найактивніші клієнти
$topCustomers = $pdo->query("
    SELECT 
        c.customer_id,
        CONCAT(c.first_name, ' ', c.last_name) as full_name,
        c.email,
        COUNT(DISTINCT b.booking_id) as bookings_count,
        COUNT(DISTINCT t.ticket_id) as tickets_count,
        COALESCE(SUM(t.ticket_price), 0) as total_spent
    FROM customers c
    LEFT JOIN bookings b ON c.customer_id = b.customer_id
    LEFT JOIN tickets t ON b.booking_id = t.booking_id
    WHERE c.is_admin = 0
    GROUP BY c.customer_id
    HAVING bookings_count > 0
    ORDER BY total_spent DESC
    LIMIT 10
")->fetchAll();

require_once '../includes/header.php';
?>

<style>
.stats-container {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}
.stats-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
}
.stats-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.stats-nav {
    margin-top: 1rem;
    display: flex;
    gap: 1rem;
}
.stats-nav a {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.3s;
}
.stats-nav a:hover {
    background: rgba(255, 255, 255, 0.3);
}
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.summary-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-left: 4px solid;
}
.summary-card.success { border-left-color: #10b981; }
.summary-card.warning { border-left-color: #f59e0b; }
.summary-card.danger { border-left-color: #ef4444; }
.summary-card.info { border-left-color: #3b82f6; }
.summary-card h3 {
    font-size: 0.9rem;
    color: #64748b;
    margin-bottom: 0.5rem;
}
.summary-card .value {
    font-size: 2rem;
    font-weight: bold;
    color: #1e293b;
}
.summary-card .subtext {
    font-size: 0.85rem;
    color: #94a3b8;
    margin-top: 0.5rem;
}
.stats-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}
.stats-section h2 {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.stats-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}
.stats-table th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
}
.stats-table th:first-child {
    border-top-left-radius: 8px;
}
.stats-table th:last-child {
    border-top-right-radius: 8px;
}
.stats-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
}
.stats-table tr:hover {
    background: #f8fafc;
}
.stats-table .rank {
    width: 40px;
    text-align: center;
    font-weight: bold;
    color: #667eea;
}
.stats-table .medal {
    font-size: 1.3rem;
}
.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}
.badge.completed { background: #d1fae5; color: #065f46; }
.badge.pending { background: #fef3c7; color: #92400e; }
.badge.failed { background: #fee2e2; color: #991b1b; }
.badge.card { background: #dbeafe; color: #1e40af; }
.badge.cash { background: #e0e7ff; color: #3730a3; }
.money {
    font-weight: 600;
    color: #059669;
}
.grid-2 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 2rem;
}
@media (max-width: 1100px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
.no-data {
    text-align: center;
    padding: 3rem;
    color: #94a3b8;
    font-size: 1.1rem;
}
</style>

<div class="stats-container">
    <div class="stats-header">
        <h1>📊 Статистика та аналітика</h1>
        <p>Детальна інформація про бронювання, рейси, оплати та клієнтів</p>
        <div class="stats-nav">
            <a href="<?php echo BASE_URL; ?>/admin.php">← Назад до панелі</a>
        </div>
    </div>

    <!-- Загальна статистика оплат -->
    <div class="summary-cards">
        <div class="summary-card success">
            <h3>✅ Успішні оплати</h3>
            <div class="value"><?php echo number_format($totalPayments['completed_amount'], 2); ?> ₴</div>
            <div class="subtext">Підтверджені платежі</div>
        </div>
        <div class="summary-card warning">
            <h3>⏳ Очікують оплати</h3>
            <div class="value"><?php echo number_format($totalPayments['pending_amount'], 2); ?> ₴</div>
            <div class="subtext">В процесі обробки</div>
        </div>
        <div class="summary-card danger">
            <h3>❌ Відхилені оплати</h3>
            <div class="value"><?php echo number_format($totalPayments['failed_amount'], 2); ?> ₴</div>
            <div class="subtext">Неуспішні транзакції</div>
        </div>
        <div class="summary-card info">
            <h3>💳 Всього транзакцій</h3>
            <div class="value"><?php echo number_format($totalPayments['total_count']); ?></div>
            <div class="subtext">Загальна кількість</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Топ 10 найпопулярніших рейсів -->
        <div class="stats-section">
            <h2>✈️ Топ 10 найпопулярніших рейсів</h2>
            <?php if ($popularFlights): ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Рейс</th>
                        <th>Маршрут</th>
                        <th>Бронювань</th>
                        <th>Квитків</th>
                        <th>Дохід</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($popularFlights as $i => $flight): ?>
                    <tr>
                        <td class="rank">
                            <?php 
                            if ($i == 0) echo '<span class="medal">🥇</span>';
                            elseif ($i == 1) echo '<span class="medal">🥈</span>';
                            elseif ($i == 2) echo '<span class="medal">🥉</span>';
                            else echo $i + 1;
                            ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($flight['flight_number']); ?></strong><br>
                            <small style="color: #64748b;"><?php echo htmlspecialchars($flight['airline_name']); ?></small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($flight['departure_city'] . ' (' . $flight['departure_code'] . ')'); ?><br>
                            <small style="color: #64748b;">→ <?php echo htmlspecialchars($flight['arrival_city'] . ' (' . $flight['arrival_code'] . ')'); ?></small>
                        </td>
                        <td><?php echo $flight['booking_count']; ?></td>
                        <td><?php echo $flight['ticket_count']; ?></td>
                        <td class="money"><?php echo number_format($flight['total_revenue'], 2); ?> ₴</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">📭 Немає даних про бронювання</div>
            <?php endif; ?>
        </div>

        <!-- Топ авіакомпанії -->
        <div class="stats-section">
            <h2>🛫 Топ авіакомпанії за доходом</h2>
            <?php if ($airlineStats): ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Авіакомпанія</th>
                        <th>Рейсів</th>
                        <th>Бронювань</th>
                        <th>Дохід</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($airlineStats as $i => $airline): ?>
                    <tr>
                        <td class="rank">
                            <?php 
                            if ($i == 0) echo '<span class="medal">🥇</span>';
                            elseif ($i == 1) echo '<span class="medal">🥈</span>';
                            elseif ($i == 2) echo '<span class="medal">🥉</span>';
                            else echo $i + 1;
                            ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($airline['airline_name']); ?></strong><br>
                            <small style="color: #64748b;"><?php echo htmlspecialchars($airline['iata_code']); ?></small>
                        </td>
                        <td><?php echo $airline['flights_count']; ?></td>
                        <td><?php echo $airline['bookings_count']; ?></td>
                        <td class="money"><?php echo number_format($airline['total_revenue'], 2); ?> ₴</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">📭 Немає даних про авіакомпанії</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Топ маршрути -->
    <div class="stats-section">
        <h2>🗺️ Найпопулярніші маршрути</h2>
        <?php if ($topRoutes): ?>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Маршрут</th>
                    <th>Бронювань</th>
                    <th>Квитків продано</th>
                    <th>Загальний дохід</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topRoutes as $i => $route): ?>
                <tr>
                    <td class="rank">
                        <?php 
                        if ($i == 0) echo '<span class="medal">🥇</span>';
                        elseif ($i == 1) echo '<span class="medal">🥈</span>';
                        elseif ($i == 2) echo '<span class="medal">🥉</span>';
                        else echo $i + 1;
                        ?>
                    </td>
                    <td><strong><?php echo htmlspecialchars($route['route']); ?></strong></td>
                    <td><?php echo $route['bookings']; ?></td>
                    <td><?php echo $route['tickets']; ?></td>
                    <td class="money"><?php echo number_format($route['revenue'], 2); ?> ₴</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-data">📭 Немає даних про маршрути</div>
        <?php endif; ?>
    </div>

    <div class="grid-2">
        <!-- Статистика оплат -->
        <div class="stats-section">
            <h2>💳 Статистика оплат</h2>
            <?php if ($paymentStats): ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Статус</th>
                        <th>Метод</th>
                        <th>Кількість</th>
                        <th>Сума</th>
                        <th>Середня</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paymentStats as $stat): ?>
                    <tr>
                        <td>
                            <?php 
                            $statuses = [
                                'completed' => '<span class="badge completed">✓ Оплачено</span>',
                                'pending' => '<span class="badge pending">⏳ Очікує</span>',
                                'failed' => '<span class="badge failed">✗ Відхилено</span>'
                            ];
                            echo $statuses[$stat['payment_status']] ?? $stat['payment_status'];
                            ?>
                        </td>
                        <td>
                            <?php 
                            $methods = [
                                'card' => '<span class="badge card">💳 Картка</span>',
                                'cash' => '<span class="badge cash">💵 Готівка</span>'
                            ];
                            echo $methods[$stat['payment_method']] ?? $stat['payment_method'];
                            ?>
                        </td>
                        <td><?php echo $stat['count']; ?></td>
                        <td class="money"><?php echo number_format($stat['total_amount'], 2); ?> ₴</td>
                        <td><?php echo number_format($stat['avg_amount'], 2); ?> ₴</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">📭 Немає даних про оплати</div>
            <?php endif; ?>
        </div>

        <!-- Найактивніші клієнти -->
        <div class="stats-section">
            <h2>👥 Топ 10 найактивніших клієнтів</h2>
            <?php if ($topCustomers): ?>
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Клієнт</th>
                        <th>Бронювань</th>
                        <th>Квитків</th>
                        <th>Витрачено</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topCustomers as $i => $customer): ?>
                    <tr>
                        <td class="rank">
                            <?php 
                            if ($i == 0) echo '<span class="medal">🥇</span>';
                            elseif ($i == 1) echo '<span class="medal">🥈</span>';
                            elseif ($i == 2) echo '<span class="medal">🥉</span>';
                            else echo $i + 1;
                            ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong><br>
                            <small style="color: #64748b;"><?php echo htmlspecialchars($customer['email']); ?></small>
                        </td>
                        <td><?php echo $customer['bookings_count']; ?></td>
                        <td><?php echo $customer['tickets_count']; ?></td>
                        <td class="money"><?php echo number_format($customer['total_spent'], 2); ?> ₴</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">📭 Немає даних про клієнтів</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Статистика по місяцях -->
    <?php if ($monthlyStats): ?>
    <div class="stats-section">
        <h2>📈 Статистика за останні місяці</h2>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Місяць</th>
                    <th>Бронювань</th>
                    <th>Квитків продано</th>
                    <th>Дохід</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthlyStats as $stat): ?>
                <tr>
                    <td><strong><?php echo date('F Y', strtotime($stat['month'] . '-01')); ?></strong></td>
                    <td><?php echo $stat['bookings']; ?></td>
                    <td><?php echo $stat['tickets']; ?></td>
                    <td class="money"><?php echo number_format($stat['revenue'], 2); ?> ₴</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
