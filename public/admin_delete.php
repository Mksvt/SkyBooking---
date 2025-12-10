<?php
require_once '../includes/config.php';
requireLogin();
if (!isAdmin()) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$table = getGet('table', '');
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$allowed = ['customers','flights','bookings','tickets','airports','airlines','passengers','payments'];
if (!$table || !in_array($table, $allowed) || !$id) {
    header('Location: admin.php'); exit;
}

// find pk
$stmt = $pdo->prepare("SELECT COLUMN_NAME, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
$stmt->execute([DB_NAME, $table]);
$cols = $stmt->fetchAll();
$pk = null;
foreach ($cols as $c) if ($c['COLUMN_KEY'] === 'PRI') { $pk = $c['COLUMN_NAME']; break; }

if (!$pk) { header('Location: admin.php?table=' . urlencode($table)); exit; }

// Get record data
$record = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE `$pk` = ? LIMIT 1");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
} catch (Exception $e) {
    header('Location: admin.php?table=' . urlencode($table)); exit;
}

if (!$record) {
    header('Location: admin.php?table=' . urlencode($table)); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $error = 'Invalid CSRF'; }
    else {
        $stmt = $pdo->prepare("DELETE FROM `$table` WHERE `$pk` = ? LIMIT 1");
        $stmt->execute([$id]);
        header('Location: admin.php?table=' . urlencode($table)); exit;
    }
}

$tableNames = [
    'customers' => 'Клієнти',
    'passengers' => 'Пасажири',
    'flights' => 'Рейси',
    'airports' => 'Аеропорти',
    'airlines' => 'Авіакомпанії',
    'bookings' => 'Бронювання',
    'tickets' => 'Квитки',
    'payments' => 'Платежі'
];

$page_title = 'Видалення запису - SkyBooking';
require_once '../includes/header.php';
?>

<style>
.admin-container { max-width: 900px; margin: 2rem auto; padding: 0 1.5rem; }
.delete-card {
    background: white;
    padding: 2.5rem;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}
.warning-header {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 12px 12px 0 0;
    margin: -2.5rem -2.5rem 2rem;
    text-align: center;
}
.warning-icon {
    font-size: 4rem;
    margin-bottom: 0.5rem;
    animation: shake 0.5s;
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-10px); }
    75% { transform: translateX(10px); }
}
.record-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}
.record-table {
    width: 100%;
    border-collapse: collapse;
}
.record-table tr {
    border-bottom: 1px solid #e9ecef;
}
.record-table tr:last-child {
    border-bottom: none;
}
.record-table td {
    padding: 0.75rem 0;
}
.record-table td:first-child {
    font-weight: 600;
    color: #495057;
    width: 40%;
}
.record-table td:last-child {
    color: #212529;
    word-break: break-word;
}
.pk-badge {
    background: #dc3545;
    color: white;
    padding: 0.2rem 0.6rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: 0.5rem;
}
</style>

<div class="admin-container">
    <div class="delete-card">
        <div class="warning-header">
            <div class="warning-icon">⚠️</div>
            <h1 style="margin: 0; font-size: 2rem;">Підтвердження видалення</h1>
            <p style="margin: 0.5rem 0 0; opacity: 0.9;">Ви збираєтесь видалити запис назавжди</p>
        </div>

        <div style="text-align: center; margin-bottom: 2rem;">
            <div style="display: inline-block; background: #fff3cd; border: 2px solid #ffc107; padding: 1rem 2rem; border-radius: 8px;">
                <div style="color: #856404; font-size: 1.1rem;">
                    <strong>Таблиця:</strong> <?php echo $tableNames[$table] ?? htmlspecialchars($table); ?>
                </div>
                <div style="color: #dc3545; font-size: 1.3rem; font-weight: bold; margin-top: 0.5rem;">
                    ID: #<?php echo htmlspecialchars($id); ?>
                </div>
            </div>
        </div>

        <div class="record-info">
            <h3 style="margin: 0 0 1rem; color: #495057; font-size: 1.2rem;">📋 Інформація про запис:</h3>
            <table class="record-table">
                <?php foreach ($cols as $col): 
                    $colName = $col['COLUMN_NAME'];
                    $value = $record[$colName] ?? '';
                    $isPK = ($col['COLUMN_KEY'] === 'PRI');
                ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($colName); ?>
                        <?php if ($isPK): ?>
                            <span class="pk-badge">PRIMARY KEY</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($value === '' || $value === null): ?>
                            <span style="color: #adb5bd; font-style: italic;">NULL</span>
                        <?php else: ?>
                            <strong><?php echo htmlspecialchars($value); ?></strong>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div style="background: #f8d7da; border-left: 4px solid #dc3545; padding: 1rem 1.5rem; border-radius: 6px; margin-bottom: 2rem;">
            <div style="color: #721c24; font-weight: 600; margin-bottom: 0.5rem;">⚡ Важливо:</div>
            <ul style="margin: 0; padding-left: 1.5rem; color: #721c24;">
                <li>Ця дія <strong>не може бути скасована</strong></li>
                <li>Всі пов'язані дані можуть бути також видалені (CASCADE)</li>
                <li>Рекомендуємо створити резервну копію перед видаленням</li>
            </ul>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <button type="submit" class="btn btn-danger" style="padding: 1rem 2.5rem; font-size: 1.1rem; font-weight: 600;">
                    🗑️ Підтверджую, видалити назавжди
                </button>
                <a href="admin.php?table=<?php echo urlencode($table); ?>" class="btn btn-secondary" style="padding: 1rem 2.5rem; font-size: 1.1rem; font-weight: 600;">
                    ↩️ Скасувати і повернутись
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
