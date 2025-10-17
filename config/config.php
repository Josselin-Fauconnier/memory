<?php
/**
 * Memory Game - Configuration basée sur le code existant
 * Configuration cohérente avec Game.php, Score.php et Card.php
 */

// === PROTECTION ACCÈS DIRECT ===
if (!defined('ROOT_PATH')) {
    die('Accès direct non autorisé');
}

// === INFORMATIONS APPLICATION ===
define('APP_NAME', 'Memory Game');
define('APP_VERSION', '1.0.0');

// === CONFIGURATION CHEMINS ===
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('IMAGES_PATH', ASSETS_PATH . '/images');
define('CSS_PATH', ASSETS_PATH . '/css');
define('LOGS_PATH', ROOT_PATH . '/logs');

// === CONFIGURATION JEU - BASÉE SUR Game.php ===

// Difficultés exactement comme dans votre Game.php
define('GAME_DIFFICULTIES', [
    'facile' => [
        'pairs_count' => 3,
        'cards_count' => 6,
        'base_score' => 300,
        'description' => 'Facile (3 paires)'
    ],
    'moyen' => [
        'pairs_count' => 6,
        'cards_count' => 12,
        'base_score' => 600,
        'description' => 'Moyen (6 paires)'
    ]
]);

// Image de dos de carte (d'après Card.php)
define('CARD_BACK_IMAGE', 'images/joker.svg');

// === ALGORITHME DE SCORE - BASÉ SUR votre calculateScore() ===
define('SCORE_CONFIG', [
    'min_score' => 50,              // Comme dans votre Game.php
    'move_penalty_rate' => 10,      // Pénalité par coup supplémentaire
    'time_bonus_threshold' => 120   // Bonus si < 120 secondes
]);

// === CONFIGURATION UTILISATEURS - BASÉE SUR Score.php ===
define('USER_CONFIG', [
    'username_max_length' => 50,    // Comme dans Score.php
    'password_hash_algo' => PASSWORD_DEFAULT
]);

// === CONFIGURATION BASE DE DONNÉES - BASÉE SUR Database.php ===
define('DB_CONFIG', [
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]
]);

// Tables de votre base
define('DB_TABLES', [
    'players' => 'players',
    'games' => 'games', 
    'leaderboard' => 'leaderboard'
]);

// Vues de votre base
define('DB_VIEWS', [
    'player_stats' => 'player_stats',
    'top_players' => 'top_10_players'
]);

// === VALIDATION BASÉE SUR Score.php ===
define('SCORE_VALIDATION', [
    'MIN_SCORE' => 50.0,
    'MAX_SCORE' => 600.0,
    'MIN_PAIRS' => 3,
    'MAX_PAIRS' => 6,
    'MIN_MOVES' => 6,
    'MAX_TIME_SECONDS' => 120,
    'TIME_LIMITS' => [
        3 => 60,   // 1 minute pour facile
        6 => 120   // 2 minutes pour moyen
    ]
]);

// === CONFIGURATION ANIMATIONS - BASÉE SUR CSS ===
define('UI_CONFIG', [
    'card_flip_duration' => 600,    // millisecondes (cardFlip animation)
    'match_animation_duration' => 800, // matchFound animation
    'hide_delay' => 1000            // Comme dans processMatchAttempt()
]);

// === MESSAGES - BASÉS SUR votre code ===
define('GAME_MESSAGES', [
    'pair_found' => 'Paire trouvée !',
    'no_match' => 'Pas de correspondance. Les cartes vont se retourner.',
    'game_completed' => 'Félicitations ! Partie terminée !',
    'card_not_flippable' => 'Cette carte ne peut pas être retournée',
    'too_many_flipped' => 'Deux cartes déjà retournées. Attendez le résultat.',
    'invalid_position' => 'Position invalide',
    'game_already_completed' => 'Partie déjà terminée'
]);

// === CODES D'ERREUR - BASÉS SUR Game.php ===
define('ERROR_CODES', [
    'TOO_MANY_FLIPPED' => 'TOO_MANY_FLIPPED',
    'INVALID_POSITION' => 'INVALID_POSITION', 
    'NEGATIVE_POSITION' => 'NEGATIVE_POSITION',
    'CARD_NOT_FLIPPABLE' => 'CARD_NOT_FLIPPABLE',
    'GAME_COMPLETED' => 'GAME_COMPLETED'
]);

// === FONCTIONS UTILITAIRES COHÉRENTES ===

/**
 * Récupère une configuration de difficulté
 * Cohérent avec Game.php validateAndSetDifficulty()
 */
function getGameDifficulty(string $difficulty): ?array 
{
    $difficulties = GAME_DIFFICULTIES;
    $normalized = strtolower(trim($difficulty));
    return $difficulties[$normalized] ?? null;
}

/**
 * Valide un niveau de difficulté
 * Comme dans Game.php
 */
function isValidDifficulty(string $difficulty): bool 
{
    $normalized = strtolower(trim($difficulty));
    return array_key_exists($normalized, GAME_DIFFICULTIES);
}

/**
 * Calcule le score minimum de coups parfaits
 * Basé sur la logique de Game.php
 */
function getPerfectMoves(string $difficulty): int 
{
    $config = getGameDifficulty($difficulty);
    return $config ? $config['pairs_count'] * 2 : 6;
}

/**
 * Valide une position de carte
 * Cohérent avec Game.php isValidPosition()
 */
function isValidCardPosition(int $position, string $difficulty): bool 
{
    if ($position < 0) {
        return false;
    }
    
    $config = getGameDifficulty($difficulty);
    return $config && $position < $config['cards_count'];
}

/**
 * Formate le temps comme dans Game.php
 */
function formatGameTime(int $seconds): string 
{
    $minutes = intval($seconds / 60);
    $seconds = $seconds % 60;
    
    if ($minutes > 0) {
        return sprintf("%d min %02d sec", $minutes, $seconds);
    } else {
        return sprintf("%d sec", $seconds);
    }
}

/**
 * Validation environnement minimal
 */
function validateBasicEnvironment(): array 
{
    $errors = [];
    
    // Vérification dossiers essentiels
    $dirs = [IMAGES_PATH, LOGS_PATH];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            $errors[] = "Dossier manquant: $dir";
        }
    }
    
    // Vérification extensions critiques
    $extensions = ['pdo', 'pdo_mysql'];
    foreach ($extensions as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = "Extension manquante: $ext";
        }
    }
    
    return $errors;
}

// === CONSTANTES CALCULÉES ===
define('DEFAULT_DIFFICULTY', array_keys(GAME_DIFFICULTIES)[0]); // 'facile'
define('MAX_CARDS_COUNT', max(array_column(GAME_DIFFICULTIES, 'cards_count'))); // 12

// === TIMEZONE ===
date_default_timezone_set('Europe/Paris');

?>