<?php 
require_once '../includes/config.php';
requireLogin();

$page_title = 'Мої бронювання - SkyBooking';

// Отримуємо бронювання користувача
$stmt = $pdo->prepare("
    SELECT DISTINCT
        b.*,
        f.flight_number,
        f.departure_time,
        f.arrival_time,
        a.name as airline_name,
        a.iata_code as airline_code,
        da.city as departure_city,
        da.iata_code as departure_code,
        aa.city as arrival_city,
        aa.iata_code as arrival_code,
        COUNT(t.ticket_id) as tickets_count
    FROM bookings b
    JOIN tickets t ON b.booking_id = t.booking_id
    JOIN flights f ON t.flight_id = f.flight_id
    JOIN airlines a ON f.airline_id = a.airline_id
    JOIN airports da ON f.departure_airport_id = da.airport_id
    JOIN airports aa ON f.arrival_airport_id = aa.airport_id
    WHERE b.customer_id = ?
    GROUP BY b.booking_id, f.flight_id, a.airline_id, da.airport_id, aa.airport_id
    ORDER BY b.booking_date DESC
");
$stmt->execute([$_SESSION['customer_id']]);
$bookings = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container">
    <section class="section">
        <h1 class="section-title">Мої бронювання</h1>
        
        <?php if (empty($bookings)): ?>
            <div class="alert alert-info">
                У вас поки немає бронювань. <a href="/public/search.php" style="color: var(--primary-color); font-weight: 600;">Знайти рейс</a>
            </div>
        <?php else: ?>
            <div class="flights-list">
                <?php foreach ($bookings as $booking): ?>
                    <?php
                    $status_colors = [
                        'pending' => 'var(--warning-color)',
                        'confirmed' => 'var(--success-color)',
                        'cancelled' => 'var(--danger-color)'
                    ];
                    $status_texts = [
                        'pending' => 'Очікує оплати',
                        'confirmed' => 'Підтверджено',
                        'cancelled' => 'Скасовано'
                    ];
                    
                    $departure_time = new DateTime($booking['departure_time']);
                    $arrival_time = new DateTime($booking['arrival_time']);
                    ?>
                    
                    <div class="flight-card">
                        <div class="flight-info">
                            <div class="flight-detail">
                                <div style="color: var(--gray-text); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Бронювання #<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?>
                                </div>
                                <div class="flight-time"><?php echo $departure_time->format('H:i'); ?></div>
                                <div class="flight-location"><?php echo htmlspecialchars($booking['departure_city']); ?></div>
                                <div class="flight-location"><?php echo $booking['departure_code']; ?></div>
                                <div style="color: var(--gray-text); font-size: 0.9rem; margin-top: 0.25rem;">
                                    <?php echo $departure_time->format('d.m.Y'); ?>
                                </div>
                            </div>
                            
                            <div class="flight-duration">
                                <div class="flight-arrow">✈️ →</div>
                                <div style="margin-top: 0.5rem; font-weight: bold; color: var(--primary-color);">
                                    <?php echo htmlspecialchars($booking['airline_name']); ?>
                                </div>
                                <div style="color: var(--gray-text); font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($booking['airline_code'] . ' ' . $booking['flight_number']); ?>
                                </div>
                                <div style="margin-top: 0.5rem; font-size: 0.9rem;">
                                    Пасажирів: <?php echo $booking['tickets_count']; ?>
                                </div>
                            </div>
                            
                            <div class="flight-detail">
                                <div style="color: var(--gray-text); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Статус: <strong style="color: <?php echo $status_colors[$booking['status']]; ?>">
                                        <?php echo $status_texts[$booking['status']]; ?>
                                    </strong>
                                </div>
                                <div class="flight-time"><?php echo $arrival_time->format('H:i'); ?></div>
                                <div class="flight-location"><?php echo htmlspecialchars($booking['arrival_city']); ?></div>
                                <div class="flight-location"><?php echo $booking['arrival_code']; ?></div>
                                <div style="color: var(--gray-text); font-size: 0.9rem; margin-top: 0.25rem;">
                                    <?php echo $arrival_time->format('d.m.Y'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flight-price">
                            <div class="price-amount"><?php echo number_format($booking['total_amount'], 2); ?> ₴</div>
                            <div class="price-currency">
                                <?php echo $booking['payment_status'] === 'paid' ? 'Оплачено' : 'До оплати'; ?>
                            </div>
                            
                            <?php if ($booking['status'] === 'confirmed'): ?>
                                <a href="/public/ticket.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                                   class="btn btn-primary" style="margin-top: 1rem;">
                                    Переглянути квиток
                                </a>
                            <?php elseif ($booking['status'] === 'pending'): ?>
                                <a href="/public/payment.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                                   class="btn btn-warning" style="margin-top: 1rem; background: var(--warning-color);">
                                    Оплатити
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>
