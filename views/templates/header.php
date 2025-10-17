<?php

$page_title = isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') : 'Memory Game';
$current_page = $_SERVER['REQUEST_URI'] ?? '';
$is_game_page = strpos($current_page, 'action=game') !== false;
$is_leaderboard_page = strpos($current_page, 'action=leaderboard') !== false;

$user_logged_in = isset($_SESSION['user_id']) ?? false;
$current_user = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= $page_title ?></title>
    
    <!-- Meta tags de sécurité -->
    <meta name="description" content="Jeu de mémoire en ligne - Entraînez votre mémoire">
    <link rel="stylesheet" href="assets/css/memoryStyle.css">
  
    <?php if ($is_game_page): ?>
        <link rel="preload" href="assets/images/cards/" as="image">
    <?php endif; ?>
</head>

<body>
    <header role="banner" class="main-header">
        <nav role="navigation" aria-label="Navigation principale">
            <h1><a href="index.php">Memory Game Royal </a></h1>
            
            <ul class="nav-menu">
                <li><a href="index.php" class="button <?= empty($_GET['action']) ? 'active' : '' ?>">🏠 Menu</a></li>
                
                <?php if (isset($_SESSION['game']) && !empty($_SESSION['game'])): ?>
                <li><a href="?action=game" class="button <?= $is_game_page ? 'active' : '' ?>"> Partie</a></li>
                <?php endif; ?>
                
                <li><a href="?action=leaderboard" class="button <?= $is_leaderboard_page ? 'active' : '' ?>"> Classement</a></li>
                
                <?php if ($user_logged_in): ?>
                <li>
                    <span>👋 <?= $current_user ?></span>
                    <a href="?action=logout" class="button"> Déconnexion</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <?php if (isset($flash_message) && is_array($flash_message)): ?>
    <div class="message <?= htmlspecialchars($flash_message['type'], ENT_QUOTES, 'UTF-8') ?>" role="alert">
        <?= htmlspecialchars($flash_message['text'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>
    <main role="main">