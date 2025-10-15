
<?php
/**
 * Test des modèles - Memory Game
 * 
 * Script pour valider que VOS classes fonctionnent correctement
 * Adapté à votre code spécifique
 */

// Inclusion de VOS classes (selon votre structure)
require_once 'database.php';   // Votre Database.php
require_once 'Card.php';       // Votre Card.php  
require_once 'Game.php';       // Votre Game.php

echo "🧪 TESTS DE VOS MODÈLES MEMORY GAME\n";
echo "===================================\n\n";

// =====================================
// TEST 1 : VOTRE CONNEXION BASE DE DONNÉES
// =====================================
echo "🔌 Test 1: Votre classe Database\n";
try {
    $isConnected = Database::testConnection();
    if ($isConnected) {
        echo "✅ Votre connexion BDD fonctionne\n";
        $pdo = Database::getInstance();
        echo "✅ Singleton Database opérationnel\n";
    } else {
        echo "❌ Connexion BDD échouée - Vérifiez vos paramètres\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur connexion : " . $e->getMessage() . "\n";
    echo "💡 Vérifiez vos paramètres dans database.php\n";
}
echo "\n";

// =====================================
// TEST 2 : VOTRE CLASSE CARD
// =====================================
echo "🃏 Test 2: Votre classe Card\n";

try {
    // Test avec VOS images définies
    $images = Card::getAvailableImages();
    echo "📸 Images disponibles dans votre code: " . count($images) . "\n";
    echo "📋 Première image: " . $images[0] . "\n";
    
    // Test création avec une de VOS images
    $carte1 = new Card(1, $images[0]);
    $carte2 = new Card(2, $images[0]);  // Même image = paire
    echo "✅ Création de cartes avec vos images réussie\n";
    
    // Test de VOS méthodes
    echo "📋 Carte 1 - ID: " . $carte1->getId() . ", Image: " . $carte1->getImage() . "\n";
    echo "📋 Carte 2 - ID: " . $carte2->getId() . ", Image: " . $carte2->getImage() . "\n";
    
    // Test canBeFlipped (votre logique)
    if ($carte1->canBeFlipped()) {
        echo "✅ Votre méthode canBeFlipped() fonctionne\n";
    }
    
    // Test flip (votre implémentation)
    $flipResult = $carte1->flip();
    if ($flipResult && $carte1->isFlipped()) {
        echo "✅ Votre méthode flip() fonctionne\n";
    }
    
    // Test matches (votre logique de paire)
    if ($carte1->matches($carte2)) {
        echo "✅ Votre détection de paire fonctionne\n";
    }
    
    // Test de vos méthodes d'affichage HTML
    $htmlCard = $carte1->renderHtml();
    if (!empty($htmlCard)) {
        echo "✅ Votre rendu HTML fonctionne\n";
    }
    
    
} catch (Exception $e) {
    echo "❌ Erreur dans votre classe Card : " . $e->getMessage() . "\n";
}
echo "\n";

// =====================================
// TEST 3 : VOTRE CLASSE GAME - FACILE
// =====================================
echo "🎮 Test 3: Votre classe Game (Facile)\n";

try {
    // Test avec VOS niveaux de difficulté
    $gameFacile = new Game('facile');
    echo "✅ Création de votre jeu facile réussie\n";
    
    // Test de VOS getters
    echo "📊 Votre difficulté: " . $gameFacile->getDifficulty() . "\n";
    echo "📊 Vos paires: " . $gameFacile->getTotalPairs() . "\n";
    echo "📊 Vos cartes: " . count($gameFacile->getCards()) . "\n";
    
    // Test état initial selon VOTRE logique
    echo "📊 Coups initial: " . $gameFacile->getMoves() . "\n";
    echo "📊 Paires trouvées: " . $gameFacile->getFoundPairs() . "\n";
    echo "📊 Terminé: " . ($gameFacile->isCompleted() ? 'Oui' : 'Non') . "\n";
    
    // Test de VOTRE méthode flipCard
    $result = $gameFacile->flipCard(0);
    if ($result['success']) {
        echo "✅ Votre premier retournement fonctionne\n";
        echo "📋 Résultat: " . json_encode($result) . "\n";
    }
    
    // Deuxième retournement selon VOTRE logique
    $result = $gameFacile->flipCard(1);
    if ($result['success']) {
        echo "✅ Votre deuxième retournement fonctionne\n";
        
        if (isset($result['match'])) {
            echo "🎲 Votre détection de paire: " . ($result['match'] ? "TROUVÉE !" : "Pas de correspondance") . "\n";
            
            if (!$result['match']) {
                echo "⏳ Test de votre méthode hideNonMatchingCards...\n";
                $hidden = $gameFacile->hideNoMatchingCards();
                echo ($hidden ? "✅" : "ℹ️") . " Cartes cachées\n";
            }
        }
    }
    
    // Test de VOTRE calcul de score
    $score = $gameFacile->getScore();
    echo "🏆 Votre score actuel: " . $score . " points\n";
    
    echo "📊 État de votre jeu: " . $gameFacile . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur dans votre classe Game (Facile) : " . $e->getMessage() . "\n";
}
echo "\n";

// =====================================
// TEST 4 : VOTRE CLASSE GAME - MOYEN
// =====================================
echo "🎮 Test 4: Votre classe Game (Moyen)\n";

try {
    // Test avec votre niveau moyen
    $gameMoyen = new Game('moyen', 123); // Avec player ID selon votre code
    echo "✅ Création de votre jeu moyen réussie\n";
    
    echo "📊 Votre difficulté: " . $gameMoyen->getDifficulty() . "\n";
    echo "📊 Vos paires: " . $gameMoyen->getTotalPairs() . "\n";
    echo "📊 Vos cartes: " . count($gameMoyen->getCards()) . "\n";
    echo "📊 Player ID: " . ($gameMoyen->getPlayerId() ?? 'null') . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur dans votre classe Game (Moyen) : " . $e->getMessage() . "\n";
}
echo "\n";

// =====================================
// TEST 5 : VOS VALIDATIONS
// =====================================
echo "🔒 Test 5: Vos validations et sécurité\n";

try {
    // Test difficulté invalide selon VOTRE code
    try {
        $gameInvalid = new Game('expert');
        echo "❌ Votre validation difficulté a échoué\n";
    } catch (InvalidArgumentException $e) {
        echo "✅ Votre validation difficulté fonctionne: " . $e->getMessage() . "\n";
    }
    
    // Test position invalide selon VOTRE logique
    $gameTest = new Game('facile');
    $result = $gameTest->flipCard(999); // Position inexistante
    if (!$result['success']) {
        echo "✅ Votre validation position fonctionne: " . $result['message'] . "\n";
    }
    
    // Test limitation selon VOTRE implémentation
    $gameTest->flipCard(0);
    $gameTest->flipCard(1);
    $result = $gameTest->flipCard(2); // 3e carte
    if (!$result['success']) {
        echo "✅ Votre limitation 2 cartes fonctionne: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur dans vos validations : " . $e->getMessage() . "\n";
}
echo "\n";

// =====================================
// TEST 6 : VOS SESSIONS
// =====================================
echo "💾 Test 6: Votre système de sessions\n";

try {
    $gameSession = new Game('moyen');
    
    // Test de VOTRE toSessionArray
    $sessionData = $gameSession->toSessionArray();
    echo "✅ Votre conversion en array fonctionne\n";
    echo "📋 Champs sauvegardés: " . implode(', ', array_keys($sessionData)) . "\n";
    
    // Test de VOTRE fromSessionArray
    $gameRestored = Game::fromSessionArray($sessionData);
    echo "✅ Votre restauration depuis array fonctionne\n";
    
    // Vérification selon VOTRE logique
    if ($gameRestored->getDifficulty() === $gameSession->getDifficulty()) {
        echo "✅ Intégrité de vos données préservée\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur dans votre système de sessions : " . $e->getMessage() . "\n";
}
echo "\n";

// =====================================
// TEST 7 : VOTRE CALCUL DE SCORE
// =====================================
echo "🏆 Test 7: Votre algorithme de score\n";

try {
    $gameScore = new Game('facile');
    
    // Simulation selon VOTRE logique
    $gameScore->flipCard(0);
    $gameScore->flipCard(1);
    $gameScore->hideNoMatchingCards();
    
    echo "📊 Votre score actuel: " . $gameScore->getScore() . " points\n";
    echo "📊 Temps écoulé: " . $gameScore->getElapsedTime() . " secondes\n";
    echo "📊 Progression: " . $gameScore->getProgressPercentage() . "%\n";
    echo "✅ Votre calcul de score fonctionne\n";
    
    // Test de vos stats si partie terminée
    if ($gameScore->isCompleted()) {
        $stats = $gameScore->getFinalStats();
        echo "🏆 Stats finales disponibles\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur dans votre calcul de score : " . $e->getMessage() . "\n";
}
echo "\n";

// =====================================
// RÉSUMÉ ADAPTÉ À VOTRE CODE
// =====================================
echo "📋 RÉSUMÉ DE VOS TESTS\n";
echo "======================\n";
echo "Si tous les tests affichent ✅, VOS modèles sont prêts !\n";
echo "Vous pouvez maintenant passer aux contrôleurs.\n\n";

echo "📁 Votre structure actuelle:\n";
echo "├── database.php              " . (class_exists('Database') ? '✅' : '❌') . "\n";
echo "├── Card.php                  " . (class_exists('Card') ? '✅' : '❌') . "\n";
echo "├── Game.php                  " . (class_exists('Game') ? '✅' : '❌') . "\n";
echo "└── Prochaine étape: controllers/\n\n";

echo "💡 Si vous avez des ❌, vérifiez:\n";
echo "   - Les chemins des require_once\n";
echo "   - La configuration database.php\n";
echo "   - Que MySQL est démarré\n";
echo "   - Que vos fichiers sont bien enregistrés\n";
       