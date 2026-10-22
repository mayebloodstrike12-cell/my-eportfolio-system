<?php
require_once __DIR__ . '/functions.php';
if (!isset($page_title)) {
    $page_title = SITE_NAME;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="Personal E-Portfolio for academic reflections, course work, and learning notes.">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
</head>
<body>

<header class="site-header">
    <!-- Hero Banner with Background Image -->
    <div class="hero-banner">
        <div class="hero-overlay-box">
            <img src="assets/images/profile.jpg" alt="Anthony Mayer Guanzon" class="hero-avatar">
            <h1 class="hero-title"><?php echo SITE_NAME; ?></h1>
            <div class="hero-divider"></div>
            <p class="hero-quote"><?php echo SITE_QUOTE; ?></p>
        </div>
    </div>

    <!-- Navigation Menu Bar -->
    <nav class="main-nav">
        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item <?php echo (is_active('index.php') || is_active('about.php')) ? 'active' : ''; ?>">
                    <a href="index.php" class="nav-link">ABOUT ME</a>
                </li>
                
                <span class="nav-separator">/</span>

                <li class="nav-item <?php echo is_active('activities.php'); ?>">
                    <a href="activities.php" class="nav-link">ACTIVITIES</a>
                </li>
            </ul>
        </div>
    </nav>
</header>
