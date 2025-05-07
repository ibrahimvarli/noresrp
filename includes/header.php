<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('site_name'); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>assets/images/favicon.ico" type="image/x-icon">
    
    <!-- CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/styles.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="theme-<?php echo isset($_SESSION['theme']) ? $_SESSION['theme'] : DEFAULT_THEME; ?>">
    <div class="page-wrapper">
        <header class="main-header">
            <div class="header-content">
                <div class="logo">
                    <a href="<?php echo SITE_URL; ?>">
                        <img src="<?php echo SITE_URL; ?>assets/images/logo.png" alt="<?php echo __('site_name'); ?>" class="logo-img">
                        <span class="logo-text">NoRestRP</span>
                    </a>
                </div>
                
                <nav class="main-nav">
                    <button class="mobile-menu-toggle" aria-label="Toggle Menu">
                        <span class="bar"></span>
                        <span class="bar"></span>
                        <span class="bar"></span>
                    </button>
                    
                    <ul class="nav-menu">
                        <li><a href="<?php echo SITE_URL; ?>?page=home"><i class="fas fa-home"></i> <?php echo __('home'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>?page=world"><i class="fas fa-globe"></i> <?php echo __('world'); ?></a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?php echo SITE_URL; ?>?page=characters"><i class="fas fa-user-shield"></i> <?php echo __('characters'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>?page=quests"><i class="fas fa-scroll"></i> <?php echo __('quests'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>?page=inventory"><i class="fas fa-backpack"></i> <?php echo __('inventory'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>?page=life"><i class="fas fa-heartbeat"></i> <?php echo __('life'); ?></a></li>
                            <li><a href="<?php echo SITE_URL; ?>?page=forum"><i class="fas fa-comments"></i> <?php echo __('forum'); ?></a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="user-menu">
                    <?php if (isLoggedIn()): ?>
                        <?php $user = getCurrentUser(); ?>
                        <div class="dropdown">
                            <button class="dropdown-toggle">
                                <img src="<?php echo SITE_URL; ?>assets/images/avatars/<?php echo $user['avatar'] ?: 'default.png'; ?>" alt="Profile" class="avatar">
                                <span class="username"><?php echo $user['username']; ?></span>
                            </button>
                            <div class="dropdown-menu">
                                <a href="<?php echo SITE_URL; ?>?page=profile"><i class="fas fa-user-circle"></i> <?php echo __('profile'); ?></a>
                                <a href="<?php echo SITE_URL; ?>?page=settings"><i class="fas fa-cog"></i> <?php echo __('settings'); ?></a>
                                <a href="<?php echo SITE_URL; ?>?page=logout"><i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?></a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="auth-buttons">
                            <a href="<?php echo SITE_URL; ?>?page=login" class="btn btn-login"><i class="fas fa-sign-in-alt"></i> <?php echo __('login'); ?></a>
                            <a href="<?php echo SITE_URL; ?>?page=register" class="btn btn-register"><i class="fas fa-user-plus"></i> <?php echo __('register'); ?></a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="language-switcher dropdown">
                        <button class="language-toggle">
                            <i class="fas fa-globe"></i>
                            <span class="current-language"><?php echo getCurrentLanguage() === 'en' ? 'EN' : 'TR'; ?></span>
                        </button>
                        <div class="dropdown-menu language-menu">
                            <a href="?language=en" class="<?php echo getCurrentLanguage() === 'en' ? 'active' : ''; ?>">
                                <span class="language-icon">🇬🇧</span> <?php echo __('english'); ?>
                            </a>
                            <a href="?language=tr" class="<?php echo getCurrentLanguage() === 'tr' ? 'active' : ''; ?>">
                                <span class="language-icon">🇹🇷</span> <?php echo __('turkish'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <button class="theme-toggle" aria-label="Toggle Theme">
                        <i class="fas fa-moon moon-icon"></i>
                        <i class="fas fa-sun sun-icon"></i>
                    </button>
                </div>
            </div>
        </header>
        
        <main class="main-content">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="flash-message <?php echo $_SESSION['flash_type']; ?>">
                    <?php echo $_SESSION['flash_message']; ?>
                    <button class="close-button" aria-label="Close Message"><i class="fas fa-times"></i></button>
                </div>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?> 