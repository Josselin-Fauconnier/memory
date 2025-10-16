<?php
/**
 * Fichier de test et débogage pour le jeu Memory
 * Usage: php debug_test.php ou via navigateur
 */

require_once 'database.php';
require_once 'Card.php';
require_once 'Game.php';

class DebugTester 
{
    private array $tests = [];
    private int $passed = 0;
    private int $failed = 0;

    public function __construct() 
    {
        echo "<h1>🔍 Tests de débogage Memory Game</h1>\n";
        echo "<style>
            .success { color: green; }
            .error { color: red; }
            .info { color: blue; }
            .warning { color: orange; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        </style>\n";
    }

    /**
     * Teste la connexion à la base de données
     */
    public function testDatabaseConnection(): void 
    {
        $this->section("🗄️ Test Connexion Base de Données");
        
        try {
            $connected = Database::testConnection();
            if ($connected) {
                $this->success("✅ Connexion BDD réussie");
                
                $pdo = Database::getInstance();
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $this->info("📋 Tables trouvées: " . implode(', ', $tables));
                
            } else {
                $this->error("❌ Échec connexion BDD");
            }
        } catch (Exception $e) {
            $this->error("💥 Erreur BDD: " . $e->getMessage());
        }
    }

    /**
     * Teste la création et manipulation des cartes
     */
    public function testCardClass(): void 
    {
        $this->section("🃏 Test Classe Card");
        
        try {
            // Test création carte valide
            $card = new Card(1, 'roi-david.svg');
            $this->success("✅ Création carte valide");
            
            // Test getters de base
            $this->assertEquals(1, $card->getId(), "ID carte");
            $this->assertEquals('roi-david.svg', $card->getImage(), "Image carte");
            $this->assertEquals(false, $card->isFlipped(), "État initial carte");
            
            // Test flip
            $result = $card->flip();
            $this->assertTrue($result, "Flip carte");
            $this->assertTrue($card->isFlipped(), "État après flip");
            
            // Test match
            $card2 = new Card(2, 'roi-david.svg');
            $this->assertTrue($card->matches($card2), "Correspondance cartes");
            
            // Test images disponibles
            $images = Card::getAvailableImages();
            $this->assertTrue(count($images) >= 3, "Nombre d'images suffisant");
            $this->info("🖼️ Images disponibles: " . count($images));
            
        } catch (Exception $e) {
            $this->error("💥 Erreur Card: " . $e->getMessage());
        }
    }

    /**
     * Teste la logique du jeu
     */
    public function testGameClass(): void 
    {
        $this->section("🎮 Test Classe Game");
        
        try {
            // Test création jeu facile
            $game = new Game('facile');
            $this->success("✅ Création jeu facile");
            
            $this->assertEquals('facile', $game->getDifficulty(), "Difficulté");
            $this->assertEquals(3, $game->getTotalPairs(), "Nombre de paires");
            $this->assertEquals(0, $game->getMoves(), "Coups initiaux");
            $this->assertFalse($game->isCompleted(), "Jeu non terminé");
            
            // Test cartes générées
            $cards = $game->getCards();
            $this->assertEquals(6, count($cards), "Nombre total de cartes");
            
            // Vérification des paires
            $imageCount = [];
            foreach ($cards as $card) {
                $image = $card->getImage();
                $imageCount[$image] = ($imageCount[$image] ?? 0) + 1;
            }
            
            $validPairs = true;
            foreach ($imageCount as $count) {
                if ($count !== 2) {
                    $validPairs = false;
                    break;
                }
            }
            $this->assertTrue($validPairs, "Validation des paires");
            
            // Test flip invalide
            $result = $game->flipCard(-1);
            $this->assertFalse($result['success'], "Position négative rejetée");
            
            $result = $game->flipCard(99);
            $this->assertFalse($result['success'], "Position invalide rejetée");
            
        } catch (Exception $e) {
            $this->error("💥 Erreur Game: " . $e->getMessage());
        }
    }

    /**
     * Teste la simulation d'une partie complète
     */
    public function testGameSimulation(): void 
    {
        $this->section("🎯 Simulation Partie Complète");
        
        try {
            $game = new Game('facile');
            $cards = $game->getCards();
            
            // Créer un mapping position -> image pour trouver les paires
            $imagePositions = [];
            foreach ($cards as $position => $card) {
                $image = $card->getImage();
                if (!isset($imagePositions[$image])) {
                    $imagePositions[$image] = [];
                }
                $imagePositions[$image][] = $position;
            }
            
            $moveCount = 0;
            $maxMoves = 50; // Protection contre boucle infinie
            
            // Jouer automatiquement
            foreach ($imagePositions as $image => $positions) {
                if ($moveCount >= $maxMoves) break;
                if (count($positions) === 2) {
                    // Flip première carte de la paire
                    $result1 = $game->flipCard($positions[0]);
                    $moveCount++;
                    
                    if ($result1['success']) {
                        // Flip deuxième carte de la paire
                        $result2 = $game->flipCard($positions[1]);
                        $moveCount++;
                        
                        if ($result2['success'] && isset($result2['match']) && $result2['match']) {
                            $this->info("✨ Paire trouvée: $image");
                        }
                    }
                }
            }
            
            if ($game->isCompleted()) {
                $this->success("🎉 Partie terminée automatiquement!");
                $stats = $game->getFinalStats();
                $this->info("📊 Score final: " . $stats['score']);
                $this->info("🔢 Coups joués: " . $stats['moves']);
                $this->info("⏱️ Temps: " . $stats['time_formatted']);
            } else {
                $this->warning("⚠️ Partie non terminée en $moveCount coups");
            }
            
        } catch (Exception $e) {
            $this->error("💥 Erreur simulation: " . $e->getMessage());
        }
    }

    /**
     * Teste la persistance en session
     */
    public function testSessionPersistence(): void 
    {
        $this->section("💾 Test Persistance Session");
        
        try {
            $game = new Game('moyen');
            
            // Test conversion vers array
            $sessionData = $game->toSessionArray();
            $this->assertTrue(is_array($sessionData), "Conversion vers array");
            $this->assertTrue(isset($sessionData['difficulty']), "Difficulté sauvée");
            
            // Test restauration depuis array
            $restoredGame = Game::fromSessionArray($sessionData);
            $this->assertEquals($game->getDifficulty(), $restoredGame->getDifficulty(), "Difficulté restaurée");
            $this->assertEquals($game->getTotalPairs(), $restoredGame->getTotalPairs(), "Paires restaurées");
            
            $this->success("✅ Persistance session OK");
            
        } catch (Exception $e) {
            $this->error("💥 Erreur session: " . $e->getMessage());
        }
    }

    /**
     * Affiche les informations système
     */
    public function showSystemInfo(): void 
    {
        $this->section("💻 Informations Système");
        
        $this->info("🐘 Version PHP: " . PHP_VERSION);
        $this->info("🧠 Mémoire utilisée: " . $this->formatBytes(memory_get_usage(true)));
        $this->info("📈 Pic mémoire: " . $this->formatBytes(memory_get_peak_usage(true)));
        
        $extensions = ['pdo', 'pdo_mysql', 'session'];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $this->success("✅ Extension $ext chargée");
            } else {
                $this->error("❌ Extension $ext manquante");
            }
        }
    }

    /**
     * Lance tous les tests
     */
    public function runAllTests(): void 
    {
        $startTime = microtime(true);
        
        $this->testDatabaseConnection();
        $this->testCardClass();
        $this->testGameClass();
        $this->testGameSimulation();
        $this->testSessionPersistence();
        $this->showSystemInfo();
        
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        
        $this->section("📋 Résumé des Tests");
        echo "<div class='success'>✅ Tests réussis: {$this->passed}</div>\n";
        echo "<div class='error'>❌ Tests échoués: {$this->failed}</div>\n";
        echo "<div class='info'>⏱️ Temps d'exécution: {$executionTime}ms</div>\n";
        
        if ($this->failed === 0) {
            echo "<div class='success'><h2>🎉 Tous les tests sont passés !</h2></div>\n";
        } else {
            echo "<div class='error'><h2>⚠️ Des tests ont échoué, vérifiez votre code</h2></div>\n";
        }
    }

    // Méthodes utilitaires
    private function section(string $title): void 
    {
        echo "\n<h2>$title</h2>\n";
    }

    private function success(string $message): void 
    {
        echo "<div class='success'>$message</div>\n";
        $this->passed++;
    }

    private function error(string $message): void 
    {
        echo "<div class='error'>$message</div>\n";
        $this->failed++;
    }

    private function info(string $message): void 
    {
        echo "<div class='info'>$message</div>\n";
    }

    private function warning(string $message): void 
    {
        echo "<div class='warning'>$message</div>\n";
    }

    private function assertEquals($expected, $actual, string $description): void 
    {
        if ($expected === $actual) {
            $this->success("✅ $description: $actual");
        } else {
            $this->error("❌ $description: attendu '$expected', obtenu '$actual'");
        }
    }

    private function assertTrue($condition, string $description): void 
    {
        if ($condition) {
            $this->success("✅ $description");
        } else {
            $this->error("❌ $description: condition false");
        }
    }

    private function assertFalse($condition, string $description): void 
    {
        if (!$condition) {
            $this->success("✅ $description");
        } else {
            $this->error("❌ $description: condition true");
        }
    }

    private function formatBytes(int $bytes): string 
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Exécution des tests
if (php_sapi_name() !== 'cli') {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Memory Game</title></head><body>";
}

try {
    $tester = new DebugTester();
    $tester->runAllTests();
} catch (Exception $e) {
    echo "<div style='color: red;'>💥 Erreur fatale: " . $e->getMessage() . "</div>";
}

if (php_sapi_name() !== 'cli') {
    echo "</body></html>";
}
?>