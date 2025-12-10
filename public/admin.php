<?php
require_once '../includes/config.php';
requireLogin();

if (!isAdmin()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$table = getGet('table', 'customers');
$allowed = ['customers','flights','bookings','tickets','airports','airlines','passengers','payments'];
if (!in_array($table, $allowed)) $table = 'customers';

// Колонки таблиці з information_schema
$cols = [];
try {
    $stmt = $pdo->prepare("SELECT COLUMN_NAME, DATA_TYPE, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION");
    $stmt->execute([DB_NAME, $table]);
    $cols = $stmt->fetchAll();
} catch (Exception $e) {
    $cols = [];
}

// Фільтри та сортування
$where = [];
$params = [];
foreach ($cols as $c) {
    $val = getGet('f_' . $c['COLUMN_NAME'], '');
    if ($val !== '') {
        $where[] = "`" . $c['COLUMN_NAME'] . "` LIKE ?";
        $params[] = '%' . $val . '%';
    }
}

$order_by = getGet('sort_by', '');
$order_dir = strtoupper(getGet('sort_dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

$sql = "SELECT * FROM `$table`";
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
if ($order_by) $sql .= " ORDER BY `" . addslashes($order_by) . "` $order_dir";
$sql .= " LIMIT 1000";

$rows = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    $rows = [];
}

$page_title = 'Адмін-панель - SkyBooking';
require_once '../includes/header.php';

$tableIcons = [
    'customers' => '👥',
    'passengers' => '🧳',
    'flights' => '✈️',
    'airports' => '🏢',
    'airlines' => '🛫',
    'bookings' => '📋',
    'tickets' => '🎫',
    'payments' => '💳'
];
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
?>

<style>
.admin-container {
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}
.admin-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
}
.admin-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}
.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}
.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--primary-color);
}
.stat-label {
    color: var(--gray-text);
    font-size: 0.9rem;
}
.admin-nav {
    background: white;
    padding: 1rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}
.admin-nav-tabs {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.admin-nav-tab {
    padding: 0.75rem 1.5rem;
    background: #f8f9fa;
    color: var(--dark-text);
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.admin-nav-tab:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}
.admin-nav-tab.active {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}
.admin-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-color);
}
.content-title {
    font-size: 1.8rem;
    color: var(--dark-text);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1>🔧 Адмін-панель</h1>
        <p style="opacity: 0.9; margin: 0.5rem 0;">Управління системою бронювання SkyBooking</p>
        <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="admin_stats.php" class="btn" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); transition: all 0.3s;">
                📊 Статистика та аналітика
            </a>
            <a href="flight-map.php" class="btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 600; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); transition: all 0.3s;">
                🌍 Карта рейсів
            </a>
        </div>
    </div>

    <div class="admin-stats">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM customers");
                echo $stmt->fetchColumn();
            ?></div>
            <div class="stat-label">Клієнтів</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✈️</div>
            <div class="stat-value"><?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM flights");
                echo $stmt->fetchColumn();
            ?></div>
            <div class="stat-label">Рейсів</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-value"><?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM bookings");
                echo $stmt->fetchColumn();
            ?></div>
            <div class="stat-label">Бронювань</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value"><?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'success'");
                echo $stmt->fetchColumn();
            ?></div>
            <div class="stat-label">Оплачено</div>
        </div>
    </div>

    <div class="admin-nav">
        <div class="admin-nav-tabs">
            <?php foreach ($allowed as $t): ?>
                <a href="?table=<?php echo $t; ?>" class="admin-nav-tab <?php echo $table===$t ? 'active' : ''; ?>">
                    <span><?php echo $tableIcons[$t] ?? '📊'; ?></span>
                    <span><?php echo $tableNames[$t] ?? ucfirst($t); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="admin-content">
        <div class="content-header">
            <h2 class="content-title">
                <span><?php echo $tableIcons[$table] ?? '📊'; ?></span>
                <span><?php echo $tableNames[$table] ?? ucfirst($table); ?></span>
                <span style="color: var(--gray-text); font-size: 1rem; font-weight: normal;">(<?php echo count($rows); ?> записів)</span>
            </h2>
            <a href="admin_edit.php?table=<?php echo urlencode($table); ?>" class="btn btn-success">➕ Додати</a>
        </div>

        <div class="admin-panel">
            <h2 style="margin-bottom: 1.5rem; color: var(--primary-color);">📊 <?php echo ucfirst(htmlspecialchars($table)); ?></h2>

        <form method="GET" style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
            <input type="hidden" name="table" value="<?php echo htmlspecialchars($table); ?>">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: end;">
                <?php foreach ($cols as $c): ?>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #495057; margin-bottom: 0.25rem;">
                            <?php echo htmlspecialchars($c['COLUMN_NAME']); ?>
                        </label>
                        <input type="text" 
                               name="f_<?php echo htmlspecialchars($c['COLUMN_NAME']); ?>" 
                               value="<?php echo htmlspecialchars(getGet('f_' . $c['COLUMN_NAME'], '')); ?>" 
                               class="form-control"
                               placeholder="Фільтр..."
                               style="width: 100%; padding: 0.6rem; border: 1px solid #dee2e6; border-radius: 6px; font-size: 0.9rem;">
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1rem; align-items: end; flex-wrap: wrap;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #495057; margin-bottom: 0.25rem;">Сортувати</label>
                    <select name="sort_by" style="padding: 0.6rem; border: 1px solid #dee2e6; border-radius: 6px; font-size: 0.9rem;">
                        <option value="">-- Виберіть --</option>
                        <?php foreach ($cols as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['COLUMN_NAME']); ?>" 
                                    <?php if(getGet('sort_by','')===$c['COLUMN_NAME']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($c['COLUMN_NAME']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #495057; margin-bottom: 0.25rem;">Напрямок</label>
                    <select name="sort_dir" style="padding: 0.6rem; border: 1px solid #dee2e6; border-radius: 6px; font-size: 0.9rem;">
                        <option value="ASC">↑ За зростанням</option>
                        <option value="DESC" <?php if(getGet('sort_dir','')==='DESC') echo 'selected'; ?>>↓ За спаданням</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 2rem;">🔍 Застосувати</button>
            </div>
        </form>

        <div style="overflow-x: auto; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <table style="width: 100%; border-collapse: collapse; background: white; font-size: 0.9rem;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <?php foreach ($cols as $c): ?>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; white-space: nowrap;">
                                <?php echo htmlspecialchars($c['COLUMN_NAME']); ?>
                            </th>
                        <?php endforeach; ?>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; width: 150px;">Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="<?php echo count($cols) + 1; ?>" style="padding: 3rem; text-align: center; color: var(--gray-text);">
                                <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                                <div style="font-size: 1.1rem;">Записів не знайдено</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0; transition: background 0.2s;">
                                <?php foreach ($cols as $c): ?>
                                    <td style="padding: 0.875rem 1rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php 
                                        $val = $r[$c['COLUMN_NAME']] ?? '';
                                        $colName = $c['COLUMN_NAME'];
                                        
                                        // Спеціальне форматування для координат
                                        if ($colName === 'latitude' || $colName === 'longitude') {
                                            if ($val) {
                                                echo '<span style="font-family: monospace; background: #e0f2fe; color: #0c4a6e; padding: 0.25rem 0.5rem; border-radius: 4px; font-weight: 600;">';
                                                echo number_format((float)$val, 6);
                                                echo '°</span>';
                                            } else {
                                                echo '<span style="color: #94a3b8;">—</span>';
                                            }
                                        } elseif (strlen($val) > 50) {
                                            echo '<span title="' . htmlspecialchars($val) . '">' . htmlspecialchars(substr($val, 0, 50)) . '...</span>';
                                        } else {
                                            echo htmlspecialchars($val);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php
                                    $pk = null;
                                    foreach ($cols as $c) if ($c['COLUMN_KEY'] === 'PRI') { $pk = $c['COLUMN_NAME']; break; }
                                ?>
                                <td style="padding: 0.875rem 1rem; text-align: center;">
                                    <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                        <?php if ($pk): ?>
                                            <a href="admin_edit.php?table=<?php echo urlencode($table); ?>&id=<?php echo urlencode($r[$pk]); ?>" 
                                               style="padding: 0.5rem 1rem; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; font-size: 0.85rem; transition: all 0.2s;"
                                               onmouseover="this.style.background='#0b5ed7'"
                                               onmouseout="this.style.background='#0d6efd'">
                                                ✏️ Редагувати
                                            </a>
                                            <a href="admin_delete.php?table=<?php echo urlencode($table); ?>&id=<?php echo urlencode($r[$pk]); ?>" 
                                               style="padding: 0.5rem 1rem; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-size: 0.85rem; transition: all 0.2s;"
                                               onmouseover="this.style.background='#bb2d3b'"
                                               onmouseout="this.style.background='#dc3545'">
                                                🗑️ Видалити
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--gray-text);">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Hover effect for table rows
document.querySelectorAll('tbody tr').forEach(row => {
    row.addEventListener('mouseenter', function() {
        this.style.backgroundColor = '#f8f9fa';
    });
    row.addEventListener('mouseleave', function() {
        this.style.backgroundColor = '';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
