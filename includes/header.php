<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'SkyBooking - Бронювання авіаквитків'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="<?php echo BASE_URL; ?>/index.php" class="logo">✈️ SkyBooking</a>
                <ul class="nav-menu">
                    <li><a href="<?php echo BASE_URL; ?>/index.php">Головна</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/search.php">Пошук рейсів</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?php echo BASE_URL; ?>/my-bookings.php">Мої бронювання</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/logout.php">Вихід</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>/login.php">Вхід</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/register.php">Реєстрація</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="main">
