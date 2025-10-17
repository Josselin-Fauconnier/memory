<?php

$is_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$current_user = $_SESSION['username'] ?? null;
$current_page = $_GET['action'] ?? 'menu';


function getActiveClass(string $page, string $current): string {
    return $page === $current ? 'active' : '';
}


$navigation_links = [
    'public' => [
        ['url' => '?action=menu', 'label' => ' Accueil', 'page' => 'menu'],
        ['url' => '?action=leaderboard', 'label' => ' Classement', 'page' => 'leaderboard']
    ],
    'authenticated' => [
        ['url' => '?action=menu', 'label' => 'Nouvelle Partie', 'page' => 'menu'],
        ['url' => '?action=profile', 'label' => 'Mon Profil', 'page' => 'profile'],
        ['url' => '?action=personal_scores', 'label' => ' Mes Scores', 'page' => 'personal_scores'],
        ['url' => '?action=leaderboard', 'label' => ' Classement', 'page' => 'leaderboard']
    ]
];

?>

<nav class="main-navigation" role="navigation" aria-label="Navigation principale">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="?action=menu" class="brand-link" aria-label="Retour à l'accueil">
                <h1 class="game-title"> Memory </h1>
            </a>
        </div>

        <!-- Menu principal -->
        <ul class="nav-menu" role="menubar">
            <?php 
            $links = $is_logged_in ? $navigation_links['authenticated'] : $navigation_links['public'];
            foreach ($links as $link): 
            ?>
                <li class="nav-item" role="none">
                    <a href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>" 
                       class="nav-link <?= getActiveClass($link['page'], $current_page) ?>"
                       role="menuitem"
                       <?= $link['page'] === $current_page ? 'aria-current="page"' : '' ?>>
                        <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

       
        <div class="nav-user">
            <?php if ($is_logged_in): ?>
                <span class="user-greeting" aria-label="Utilisateur connecté">
                    👤 <?= htmlspecialchars($current_user, ENT_QUOTES, 'UTF-8') ?>
                </span>
                
                <a href="?action=logout" 
                   class="nav-link logout-link"
                   onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');"
                   aria-label="Se déconnecter">
                    🚪 Déconnexion
                </a>
            <?php else: ?>
                <a href="?action=login" 
                   class="nav-link login-link <?= getActiveClass('login', $current_page) ?>"
                   aria-label="Se connecter">
                     Connexion
                </a>
                <a href="?action=register" 
                   class="nav-link register-link <?= getActiveClass('register', $current_page) ?>"
                   aria-label="Créer un compte">
                    Inscription
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>


