<?php
// === VERSION SIMPLE POUR TEST ===
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure les fichiers nécessaires
require_once 'database.php';
require_once 'Card.php';
require_once 'Game.php';
require_once 'GameControllers.php';  

try {
    $controller = new GameController();
    $controller->handleRequest();
} catch (Exception $e) {
    echo "<h1>Erreur : " . $e->getMessage() . "</h1>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>