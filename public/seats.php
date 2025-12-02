<?php 
require_once '../includes/config.php';
requireLogin();

if (!isset($_SESSION['selected_flight_id']) || !isset($_SESSION['search'])) {
    header('Location: ' . BASE_URL . '/search.php');
    exit;
}

$page_title = 'Вибір місць - SkyBooking';
$flight_id = $_SESSION['selected_flight_id'];
$passengers_count = $_SESSION['search']['passengers'];

// Отримуємо інформацію про рейс
$stmt = $pdo->prepare("
    SELECT 
        f.*,
        a.name as airline_name,
        da.city as departure_city,
        da.iata_code as departure_code,
        aa.city as arrival_city,
        aa.iata_code as arrival_code
    FROM flights f
    JOIN airlines a ON f.airline_id = a.airline_id
    JOIN airports da ON f.departure_airport_id = da.airport_id
    JOIN airports aa ON f.arrival_airport_id = aa.airport_id
    WHERE f.flight_id = ?
");
$stmt->execute([$flight_id]);
$flight = $stmt->fetch();

if (!$flight) {
    header('Location: ' . BASE_URL . '/search.php');
    exit;
}

// Отримуємо зайняті місця
$stmt = $pdo->prepare("
    SELECT seat_number 
    FROM tickets 
    WHERE flight_id = ? AND ticket_status IN ('active', 'used')
");
$stmt->execute([$flight_id]);
$occupied_seats = array_column($stmt->fetchAll(), 'seat_number');

// Обробка вибору місць
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_seats'])) {
    $selected_seats = $_POST['selected_seats'];
    
    if (count($selected_seats) != $passengers_count) {
        $error = "Будь ласка, оберіть рівно $passengers_count місць(я).";
    } else {
        $_SESSION['selected_seats'] = $selected_seats;
        header('Location: ' . BASE_URL . '/passengers.php');
        exit;
    }
}

require_once '../includes/header.php';
?>

<div class="container">
    <section class="section">
        <h1 class="section-title">Вибір місць</h1>
        
        <div style="background: var(--light-bg); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
            <p style="font-size: 1.1rem;">
                <strong><?php echo htmlspecialchars($flight['departure_city']); ?></strong> 
                (<?php echo $flight['departure_code']; ?>)
                → 
                <strong><?php echo htmlspecialchars($flight['arrival_city']); ?></strong>
                (<?php echo $flight['arrival_code']; ?>)
            </p>
            <p style="color: var(--gray-text); margin-top: 0.5rem;">
                <?php echo htmlspecialchars($flight['airline_name']); ?> | 
                <?php echo date('d.m.Y H:i', strtotime($flight['departure_time'])); ?>
            </p>
            <p style="color: var(--primary-color); font-weight: bold; margin-top: 0.5rem;">
                Оберіть <?php echo $passengers_count; ?> місць(я)
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="seatsForm">
            <div class="seat-map">
                <div class="plane-header">✈️ Схема літака</div>
                
                <!-- Business Class -->
                <div class="seat-class-section">
                    <div class="class-title">Business Class</div>
                    <div class="seat-rows">
                        <?php for ($row = 1; $row <= 3; $row++): ?>
                            <div class="seat-row">
                                <div class="seat-row-number"><?php echo $row; ?></div>
                                <?php 
                                $seats = ['A', 'B', 'C', 'D'];
                                foreach ($seats as $seat):
                                    $seat_number = $row . $seat;
                                    $is_occupied = in_array($seat_number, $occupied_seats);
                                    $seat_class = $is_occupied ? 'seat occupied' : 'seat';
                                ?>
                                    <?php if ($seat === 'C'): ?>
                                        <div class="seat-aisle"></div>
                                    <?php endif; ?>
                                    
                                    <label>
                                        <input type="checkbox" name="selected_seats[]" 
                                               value="<?php echo $seat_number; ?>"
                                               class="seat-checkbox"
                                               <?php echo $is_occupied ? 'disabled' : ''; ?>
                                               style="display: none;">
                                        <div class="<?php echo $seat_class; ?>" 
                                             data-seat="<?php echo $seat_number; ?>">
                                            <?php echo $seat; ?>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Economy Class -->
                <div class="seat-class-section">
                    <div class="class-title">Economy Class</div>
                    <div class="seat-rows">
                        <?php for ($row = 4; $row <= 20; $row++): ?>
                            <div class="seat-row">
                                <div class="seat-row-number"><?php echo $row; ?></div>
                                <?php 
                                $seats = ['A', 'B', 'C', 'D', 'E', 'F'];
                                foreach ($seats as $seat):
                                    $seat_number = $row . $seat;
                                    $is_occupied = in_array($seat_number, $occupied_seats);
                                    $seat_class = $is_occupied ? 'seat occupied' : 'seat';
                                ?>
                                    <?php if ($seat === 'D'): ?>
                                        <div class="seat-aisle"></div>
                                    <?php endif; ?>
                                    
                                    <label>
                                        <input type="checkbox" name="selected_seats[]" 
                                               value="<?php echo $seat_number; ?>"
                                               class="seat-checkbox"
                                               <?php echo $is_occupied ? 'disabled' : ''; ?>
                                               style="display: none;">
                                        <div class="<?php echo $seat_class; ?>" 
                                             data-seat="<?php echo $seat_number; ?>">
                                            <?php echo $seat; ?>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="seat-legend">
                    <div class="legend-item">
                        <div class="legend-box available"></div>
                        <span>Вільне</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box selected"></div>
                        <span>Обране</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box occupied"></div>
                        <span>Зайняте</span>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <p id="selectedInfo" style="font-size: 1.1rem; margin-bottom: 1rem; color: var(--gray-text);">
                    Обрано місць: <strong id="selectedCount">0</strong> з <?php echo $passengers_count; ?>
                </p>
                <button type="submit" class="btn btn-primary" id="continueBtn" disabled>
                    Продовжити
                </button>
            </div>
        </form>
    </section>
</div>

<form method="POST" style="display: none;">
    <input type="hidden" name="handle_seat_selection">
    <noscript>
        <style>
            .seat-checkbox { display: inline-block !important; margin: 5px; }
            .seat { pointer-events: none; }
        </style>
    </noscript>
</form>

<style>
/* Додаткова стилізація для інтерактивності без JS */
.seat-checkbox:checked + .seat {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.seat-checkbox:disabled + .seat {
    cursor: not-allowed;
}

/* Простий CSS counter для підрахунку */
#seatsForm {
    counter-reset: selected-seats;
}

.seat-checkbox:checked {
    counter-increment: selected-seats;
}
</style>

<?php require_once '../includes/footer.php'; ?>

<noscript>
    <style>
        #selectedInfo, #continueBtn[disabled] { display: none !important; }
        #continueBtn { display: inline-block !important; }
    </style>
</noscript>

<!-- JavaScript для вибору місць -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const maxSeats = <?php echo $passengers_count; ?>;
    const continueBtn = document.getElementById('continueBtn');
    const selectedCount = document.getElementById('selectedCount');
    const seats = document.querySelectorAll('.seat:not(.occupied)');
    
    // Обробка кліку на місце
    seats.forEach(seat => {
        seat.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Знаходимо чекбокс (попередній елемент)
            const label = this.parentElement;
            const checkbox = label.querySelector('.seat-checkbox');
            
            if (!checkbox || checkbox.disabled) {
                return;
            }
            
            const currentCount = document.querySelectorAll('.seat-checkbox:checked').length;
            
            if (checkbox.checked) {
                // Зняти вибір
                checkbox.checked = false;
                this.classList.remove('selected');
            } else {
                // Перевірка ліміту перед вибором
                if (currentCount >= maxSeats) {
                    alert('Ви вже обрали максимальну кількість місць (' + maxSeats + '). Спочатку зніміть вибір з іншого місця.');
                    return;
                }
                // Вибрати місце
                checkbox.checked = true;
                this.classList.add('selected');
            }
            
            updateSelectedCount();
        });
        
        // Додаємо курсор pointer для візуальної вказівки
        seat.style.cursor = 'pointer';
    });
    
    // Оновлення лічильника та кнопки
    function updateSelectedCount() {
        const count = document.querySelectorAll('.seat-checkbox:checked').length;
        selectedCount.textContent = count;
        
        if (count === maxSeats) {
            continueBtn.disabled = false;
            continueBtn.style.opacity = '1';
            continueBtn.style.cursor = 'pointer';
        } else {
            continueBtn.disabled = true;
            continueBtn.style.opacity = '0.5';
            continueBtn.style.cursor = 'not-allowed';
        }
    }
    
    // Ініціалізація при завантаженні
    updateSelectedCount();
    
    // Синхронізуємо візуальний стан з чекбоксами (на випадок попереднього вибору)
    document.querySelectorAll('.seat-checkbox:checked').forEach(function(checkbox) {
        const seat = checkbox.nextElementSibling;
        if (seat) {
            seat.classList.add('selected');
        }
    });
});
</script>

<!-- Мінімальна функціональність через CSS -->
<style>
    /* Автоматичне підсвічування обраних місць */
    input[type="checkbox"]:checked + .seat:not(.occupied) {
        background: var(--primary-color) !important;
        color: white !important;
        border-color: var(--primary-color) !important;
    }
</style>
