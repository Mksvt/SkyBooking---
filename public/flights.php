<?php 
require_once '../includes/config.php';
$page_title = 'Доступні рейси - SkyBooking';
require_once '../includes/header.php';

// Отримуємо параметри пошуку
$departure_id = $_GET['departure'] ?? null;
$arrival_id = $_GET['arrival'] ?? null;
$date = $_GET['date'] ?? null;
$passengers = $_GET['passengers'] ?? 1;

// Перевірка параметрів
if (!$departure_id || !$arrival_id || !$date) {
    header('Location: ' . BASE_URL . '/search.php');
    exit;
}

// Перевірка, що аеропорти різні
if ($departure_id == $arrival_id) {
    echo '<div class="container"><div class="alert alert-error" style="margin-top: 2rem;">Аеропорти відправлення та прибуття повинні бути різними!</div></div>';
    require_once '../includes/footer.php';
    exit;
}

// Зберігаємо параметри пошуку в сесії
$_SESSION['search'] = [
    'departure_id' => $departure_id,
    'arrival_id' => $arrival_id,
    'date' => $date,
    'passengers' => $passengers
];

// Отримуємо інформацію про аеропорти
$stmt = $pdo->prepare("SELECT * FROM airports WHERE airport_id = ?");
$stmt->execute([$departure_id]);
$departure_airport = $stmt->fetch();

$stmt->execute([$arrival_id]);
$arrival_airport = $stmt->fetch();

// Отримуємо рейси
$query = "
    SELECT 
        f.*,
        a.name as airline_name,
        a.iata_code as airline_code,
        da.city as departure_city,
        da.name as departure_airport_name,
        aa.city as arrival_city,
        aa.name as arrival_airport_name
    FROM flights f
    JOIN airlines a ON f.airline_id = a.airline_id
    JOIN airports da ON f.departure_airport_id = da.airport_id
    JOIN airports aa ON f.arrival_airport_id = aa.airport_id
    WHERE 
        f.departure_airport_id = ? 
        AND f.arrival_airport_id = ?
        AND DATE(f.departure_time) = ?
        AND f.status = 'scheduled'
    ORDER BY f.departure_time
";

$stmt = $pdo->prepare($query);
$stmt->execute([$departure_id, $arrival_id, $date]);
$flights = $stmt->fetchAll();
?>

<div class="container">
    <section class="section">
        <h1 class="section-title">Доступні рейси</h1>
        
        <div style="background: var(--light-bg); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
            <p style="font-size: 1.1rem;">
                <strong><?php echo htmlspecialchars($departure_airport['city']); ?></strong> 
                → 
                <strong><?php echo htmlspecialchars($arrival_airport['city']); ?></strong>
            </p>
            <p style="color: var(--gray-text); margin-top: 0.5rem;">
                Дата: <?php echo date('d.m.Y', strtotime($date)); ?> | 
                Пасажирів: <?php echo $passengers; ?>
            </p>
            <a href="<?php echo BASE_URL; ?>/search.php" class="btn btn-secondary" style="margin-top: 1rem;">Змінити параметри пошуку</a>
        </div>

        <?php if (empty($flights)): ?>
            <div class="alert alert-info">
                На жаль, на вибрану дату рейсів не знайдено. Спробуйте інші дати або напрямки.
            </div>
        <?php else: ?>
            <div class="flights-list">
                <?php foreach ($flights as $flight): ?>
                    <?php
                    $departure_time = new DateTime($flight['departure_time']);
                    $arrival_time = new DateTime($flight['arrival_time']);
                    $duration = $departure_time->diff($arrival_time);
                    $total_price = $flight['base_price'] * $passengers;
                    ?>
                    <div class="flight-card">
                        <div class="flight-info">
                            <div class="flight-detail">
                                <div class="flight-time"><?php echo $departure_time->format('H:i'); ?></div>
                                <div class="flight-location"><?php echo htmlspecialchars($flight['departure_city']); ?></div>
                                <div class="flight-location"><?php echo htmlspecialchars($departure_airport['iata_code']); ?></div>
                            </div>
                            
                            <div class="flight-duration">
                                <div style="color: var(--gray-text); margin-bottom: 0.5rem;">
                                    <?php echo $duration->format('%hг %iхв'); ?>
                                </div>
                                <div class="flight-arrow">✈️ →</div>
                                <div style="margin-top: 0.5rem; font-weight: bold; color: var(--primary-color);">
                                    <?php echo htmlspecialchars($flight['airline_name']); ?>
                                </div>
                                <div style="color: var(--gray-text); font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($flight['airline_code'] . ' ' . $flight['flight_number']); ?>
                                </div>
                            </div>
                            
                            <div class="flight-detail">
                                <div class="flight-time"><?php echo $arrival_time->format('H:i'); ?></div>
                                <div class="flight-location"><?php echo htmlspecialchars($flight['arrival_city']); ?></div>
                                <div class="flight-location"><?php echo htmlspecialchars($arrival_airport['iata_code']); ?></div>
                            </div>
                        </div>
                        
                        <div class="flight-price">
                            <div class="price-amount"><?php echo number_format($total_price, 2); ?> ₴</div>
                            <div class="price-currency">за <?php echo $passengers; ?> пас.</div>
                            <form method="POST" action="<?php echo BASE_URL; ?>/select-flight.php" style="margin-top: 1rem;">
                                <input type="hidden" name="flight_id" value="<?php echo $flight['flight_id']; ?>">
                                <button type="submit" class="btn btn-primary">Обрати</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>
