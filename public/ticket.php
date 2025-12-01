<?php 
require_once '../includes/config.php';
requireLogin();

$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    header('Location: /public/index.php');
    exit;
}

$page_title = 'Ваш квиток - SkyBooking';

// Отримуємо інформацію про бронювання та квитки
$stmt = $pdo->prepare("
    SELECT 
        b.*,
        f.flight_number,
        f.departure_time,
        f.arrival_time,
        a.name as airline_name,
        a.iata_code as airline_code,
        da.city as departure_city,
        da.name as departure_airport,
        da.iata_code as departure_code,
        aa.city as arrival_city,
        aa.name as arrival_airport,
        aa.iata_code as arrival_code
    FROM bookings b
    JOIN tickets t ON b.booking_id = t.booking_id
    JOIN flights f ON t.flight_id = f.flight_id
    JOIN airlines a ON f.airline_id = a.airline_id
    JOIN airports da ON f.departure_airport_id = da.airport_id
    JOIN airports aa ON f.arrival_airport_id = aa.airport_id
    WHERE b.booking_id = ? AND b.customer_id = ?
    LIMIT 1
");
$stmt->execute([$booking_id, $_SESSION['customer_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: /public/index.php');
    exit;
}

// Отримуємо всі квитки
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        p.first_name,
        p.last_name,
        p.passport_number
    FROM tickets t
    JOIN passengers p ON t.passenger_id = p.passenger_id
    WHERE t.booking_id = ?
    ORDER BY t.ticket_id
");
$stmt->execute([$booking_id]);
$tickets = $stmt->fetchAll();

// Генеруємо дані для QR-коду
$qr_data = "BOOKING:" . str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT) . "|" .
           "FLIGHT:" . $booking['airline_code'] . $booking['flight_number'] . "|" .
           "DATE:" . date('Y-m-d', strtotime($booking['departure_time']));

require_once '../includes/header.php';

// Очищаємо ID бронювання з сесії
if (isset($_SESSION['current_booking_id'])) {
    unset($_SESSION['current_booking_id']);
}
?>

<div class="container">
    <section class="section">
        <div class="alert alert-success">
            <strong>✅ Оплата успішна!</strong> Ваше бронювання підтверджено. Квитки надіслано на вашу електронну пошту.
        </div>

        <div class="ticket-container">
            <div class="ticket-header">
                <h1>✈️ Електронний квиток</h1>
                <p>Номер бронювання: <strong>#<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></strong></p>
            </div>

            <div class="ticket-body">
                <h2 style="margin-bottom: 1.5rem; color: var(--dark-text);">Деталі рейсу</h2>
                
                <div class="ticket-info">
                    <div class="info-block">
                        <div class="info-label">Авіакомпанія</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['airline_name']); ?></div>
                    </div>

                    <div class="info-block">
                        <div class="info-label">Номер рейсу</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['airline_code'] . ' ' . $booking['flight_number']); ?></div>
                    </div>

                    <div class="info-block">
                        <div class="info-label">Відправлення</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($booking['departure_city']); ?> (<?php echo $booking['departure_code']; ?>)
                        </div>
                        <div style="font-size: 0.9rem; color: var(--gray-text); margin-top: 0.25rem;">
                            <?php echo date('d.m.Y H:i', strtotime($booking['departure_time'])); ?>
                        </div>
                    </div>

                    <div class="info-block">
                        <div class="info-label">Прибуття</div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($booking['arrival_city']); ?> (<?php echo $booking['arrival_code']; ?>)
                        </div>
                        <div style="font-size: 0.9rem; color: var(--gray-text); margin-top: 0.25rem;">
                            <?php echo date('d.m.Y H:i', strtotime($booking['arrival_time'])); ?>
                        </div>
                    </div>

                    <div class="info-block">
                        <div class="info-label">Статус</div>
                        <div class="info-value" style="color: var(--success-color);">
                            ✓ Підтверджено
                        </div>
                    </div>

                    <div class="info-block">
                        <div class="info-label">Оплачено</div>
                        <div class="info-value"><?php echo number_format($booking['total_amount'], 2); ?> ₴</div>
                    </div>
                </div>

                <h2 style="margin: 2rem 0 1.5rem; color: var(--dark-text);">Пасажири та місця</h2>
                
                <?php foreach ($tickets as $index => $ticket): ?>
                    <div class="info-block" style="margin-bottom: 1rem;">
                        <div class="info-label">Пасажир <?php echo $index + 1; ?></div>
                        <div class="info-value">
                            <?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?>
                        </div>
                        <div style="font-size: 0.9rem; color: var(--gray-text); margin-top: 0.25rem;">
                            Місце: <strong><?php echo $ticket['seat_number']; ?></strong> | 
                            Клас: <strong><?php echo ucfirst($ticket['travel_class']); ?></strong> |
                            Паспорт: <?php echo htmlspecialchars($ticket['passport_number']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="ticket-qr">
                    <h3 style="margin-bottom: 1rem;">QR-код для посадки</h3>
                    <div class="qr-code">
                        <?php
                        // Генеруємо простий QR-код через Google Charts API (працює без JS)
                        $qr_url = "https://chart.googleapis.com/chart?chs=250x250&cht=qr&chl=" . urlencode($qr_data);
                        ?>
                        <img src="<?php echo $qr_url; ?>" alt="QR Code" style="width: 100%; height: 100%;">
                    </div>
                    <div class="qr-instructions">
                        Покажіть цей QR-код під час реєстрації на рейс та посадки
                    </div>
                </div>

                <div style="margin-top: 2rem; padding: 1.5rem; background: var(--light-bg); border-radius: 8px;">
                    <h4 style="margin-bottom: 1rem;">📋 Важлива інформація:</h4>
                    <ul style="list-style: none; padding: 0; color: var(--gray-text);">
                        <li style="margin-bottom: 0.5rem;">✓ Прибути в аеропорт за 2 години до вильоту</li>
                        <li style="margin-bottom: 0.5rem;">✓ Мати при собі документ, що посвідчує особу</li>
                        <li style="margin-bottom: 0.5rem;">✓ Пройти онлайн-реєстрацію за 24 години до вильоту</li>
                        <li style="margin-bottom: 0.5rem;">✓ Роздрукувати або зберегти цей квиток на пристрої</li>
                    </ul>
                </div>

                <div style="margin-top: 2rem; text-align: center; display: flex; gap: 1rem; justify-content: center;">
                    <a href="/public/my-bookings.php" class="btn btn-secondary">Мої бронювання</a>
                    <a href="/public/index.php" class="btn btn-primary">На головну</a>
                    <button onclick="window.print()" class="btn btn-success">🖨️ Роздрукувати</button>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
@media print {
    .header, .footer, .btn {
        display: none !important;
    }
    
    .ticket-container {
        box-shadow: none;
        page-break-inside: avoid;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
