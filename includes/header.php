<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'SkyBooking - Бронювання авіаквитків'; ?></title>
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="/public/index.php" class="logo">✈️ SkyBooking</a>
                <ul class="nav-menu">
                    <li><a href="/public/index.php">Головна</a></li>
                    <li><a href="/public/search.php">Пошук рейсів</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="/public/my-bookings.php">Мої бронювання</a></li>
                        <li><a href="/public/logout.php">Вихід</a></li>
                    <?php else: ?>
                        <li><a href="/public/login.php">Вхід</a></li>
                        <li><a href="/public/register.php">Реєстрація</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="main">
