<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>osu! Web Clone</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="/home.php">osu! Web Clone</a></li>

                <li><a href="/leaderboard.php">Leaderboard</a></li>
                <li><a href="/profile.php?user=<?php echo urlencode($_SESSION['username']); ?>">My Profile</a></li>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="/admin.php">Admin Panel</a></li>
                <?php endif; ?>

                <li><a href="/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>