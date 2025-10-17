<?php
/**
 * Memory Game - Point d'entrée principal
 * Suit l'architecture MVC établie
 */

// Gestion des erreurs pour le développement
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrage de session
session_start();

// Définition du chemin racine
define('ROOT_PATH', __DIR__);

// Chargement de la configuration
require_once 'config/config.php';

// Chargement des modèles
require_once 'models/Database.php';
require_once 'models/Card.php';
require_once 'models/Game.php';
require_once 'models/User.php';
require_once 'models/Score.php';

// Chargement des contrôleurs
require_once 'controllers/GameControllers.php';
require_once 'controllers/UserControllers.php';

try {
    // Validation de l'environnement
    $envErrors = validateBasicEnvironment();
    if (!empty($envErrors)) {
        throw new RuntimeException('Problèmes environnement : ' . implode(', ', $envErrors));
    }
    
    // Test de connexion base de données
    if (!Database::testConnection()) {
        throw new RuntimeException('Impossible de se connecter à la base de données');
    }
    
    // Routage principal selon le contrôleur demandé
    $controller = $_GET['controller'] ?? 'game';
    
    switch ($controller) {
        case 'user':
            $userController = new UserController();
            $userController->handleRequest();
            break;
            
        case 'game':
        default:
            $gameController = new GameController();
            $gameController->handleRequest();
            break;
    }
    
} catch (Exception $e) {
    // Gestion d'erreur simple pour le développement
    error_log('Erreur application : ' . $e->getMessage());
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Memory Game - Erreur</title>
        <link rel="stylesheet" href="assets/css/memoryStyle.css">
    </head>
    <body>
        <div class="error-container">
            <h1>🃏 Memory Game - Erreur</h1>
            <div class="message error">
                <h2>Une erreur est survenue</h2>
                <p><?= htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') ?></p>
                
                <?php if ($_GET['debug'] ?? false): ?>
                    <details>
                        <summary>Détails techniques</summary>
                        <pre><?= htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') ?></pre>
                    </details>
                <?php endif; ?>
                
                <p><a href="?" class="button">↻ Retour au menu</a></p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>