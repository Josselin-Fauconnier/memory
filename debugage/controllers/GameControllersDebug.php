<?php
require_once 'GameControllers.php';
require_once 'GeneralDebug.php';

/**
 * Debugger pour GameController
 */
class GameControllerDebugger 
{
    /**
     * Debug de l'état des sessions
     */
    public static function debugSessionState(): array 
    {
        $sessionData = [
            'session_status' => session_status(),
            'session_id' => session_id(),
            'session_name' => session_name(),
            'session_data' => $_SESSION ?? [],
            'session_size' => self::calculateSessionSize(),
            'session_file_exists' => self::checkSessionFile()
        ];

        return $sessionData;
    }



    /**
     * Affiche l'état des sessions de manière lisible
     */
    public static function displaySessionState(): void 
    {
        echo "<h3>🗂️ État des sessions</h3>\n";
        
        $sessionData = self::debugSessionState();
        
        $statusNames = [
            PHP_SESSION_DISABLED => 'DÉSACTIVÉE',
            PHP_SESSION_NONE => 'AUCUNE',
            PHP_SESSION_ACTIVE => 'ACTIVE'
        ];
        
        $status = $statusNames[$sessionData['session_status']] ?? 'INCONNUE';
        echo "<div><strong>Status:</strong> $status</div>\n";
        echo "<div><strong>ID:</strong> {$sessionData['session_id']}</div>\n";
        echo "<div><strong>Taille:</strong> {$sessionData['session_size']}</div>\n";
        
        if (isset($_SESSION['game'])) {
            echo "<div style='color: green;'>✅ Jeu en session trouvé</div>\n";
            self::displayGameSessionData($_SESSION['game']);
        } else {
            echo "<div style='color: orange;'>⚠️ Aucun jeu en session</div>\n";
        }
        
        if (isset($_SESSION['message'])) {
            $msg = $_SESSION['message'];
            echo "<div><strong>Message flash:</strong> [{$msg['type']}] {$msg['text']}</div>\n";
        }
    }
    /**
     * Formate un tableau pour affichage 
     */
    private static function formatArrayForDisplay(array $data): string 
    {
        if (empty($data)) {
            return 'Aucune donnée';
        }
        
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = '[' . implode(', ', $value) . ']';
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $value = 'null';
            } else {
                $value = "'" . htmlspecialchars((string)$value) . "'";
            }
            $result[] = "$key: $value";
        }
        
        return '{' . implode(', ', $result) . '}';
    }

    /**
     * Récupère les headers HTTP
     */

    /**
     * Affiche les données de jeu stockées en session
     */
    private static function displayGameSessionData(array $gameData): void 
    {
        echo "<h4>🎮 Données jeu en session:</h4>\n";
        echo "<ul>\n";
        echo "<li>Difficulté: {$gameData['difficulty']}</li>\n";
        echo "<li>Paires trouvées: {$gameData['found_pairs']}/{$gameData['total_pairs']}</li>\n";
        echo "<li>Coups: {$gameData['moves']}</li>\n";
        echo "<li>Terminé: " . ($gameData['is_completed'] ? 'Oui' : 'Non') . "</li>\n";
        echo "<li>Cartes retournées: " . count($gameData['flipped_positions']) . "</li>\n";
        echo "<li>Nombre de cartes: " . count($gameData['cards']) . "</li>\n";
        echo "</ul>\n";
    }

    /**
     * Calcule la taille de la session
     */
    private static function calculateSessionSize(): string 
    {
        if (empty($_SESSION)) {
            return '0 B';
        }
        
        $serialized = serialize($_SESSION);
        return DebugUtils::formatBytes(strlen($serialized));
    }

    /**
     * Vérifie si le fichier de session existe
     */
    private static function checkSessionFile(): bool 
    {
        $sessionPath = session_save_path();
        $sessionId = session_id();
        $sessionFile = $sessionPath . '/sess_' . $sessionId;
        
        return file_exists($sessionFile);
    }

    /**
     * Debug des requêtes HTTP
     */
    public static function debugHttpRequest(): array 
    {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'query_params' => $_GET,
            'post_params' => $_POST,
            'headers' => self::getHttpHeaders(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Affiche les données HTTP de manière lisible
     */
    public static function displayHttpRequest(): void 
    {
        echo "<h4>🌐 Requête HTTP</h4>\n";
        
        $httpData = self::debugHttpRequest();
        
        echo "<div><strong>Méthode:</strong> {$httpData['method']}</div>\n";
        echo "<div><strong>URI:</strong> {$httpData['uri']}</div>\n";
        echo "<div><strong>IP:</strong> {$httpData['remote_addr']}</div>\n";
        echo "<div><strong>Timestamp:</strong> {$httpData['timestamp']}</div>\n";
        
        if (!empty($httpData['query_params'])) {
            echo "<div><strong>GET:</strong> " . self::formatArrayForDisplay($httpData['query_params']) . "</div>\n";
        }
        
        if (!empty($httpData['post_params'])) {
            echo "<div><strong>POST:</strong> " . self::formatArrayForDisplay($httpData['post_params']) . "</div>\n";
        }
        
        if (!empty($httpData['headers'])) {
            echo "<h5>📋 Headers principaux:</h5>\n";
            $importantHeaders = ['Content-Type', 'Accept', 'User-Agent', 'Referer'];
            foreach ($importantHeaders as $header) {
                if (isset($httpData['headers'][$header])) {
                    $value = htmlspecialchars($httpData['headers'][$header]);
                    echo "<div><strong>$header:</strong> $value</div>\n";
                }
            }
        }
    }

    /**
     * Récupère les headers HTTP
     */
    private static function getHttpHeaders(): array 
    {
        $headers = [];
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback pour nginx
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }
        
        return $headers;
    }

    /**
     * Simule différentes actions du contrôleur
     */
    public static function simulateControllerActions(): void 
    {
        echo "<h3>🎭 Simulation actions contrôleur</h3>\n";
        
        // Simulation nouvelle partie
        echo "<h4>🆕 Test nouvelle partie</h4>\n";
        $_POST = ['action' => 'new_game', 'difficulty' => 'facile'];
        self::debugAction('new_game');
        
        // Simulation flip carte
        echo "<h4>🃏 Test flip carte</h4>\n";
        $_POST = ['action' => 'flip_card', 'card_id' => '0'];
        self::debugAction('flip_card');
        
        // Reset POST data
        $_POST = [];
    }

    /**
     * Debug d'une action spécifique
     */
    private static function debugAction(string $action): void 
    {
        echo "<div><strong>Action:</strong> $action</div>\n";
        echo "<div><strong>POST data:</strong> " . self::formatArrayForDisplay($_POST) . "</div>\n";
        
        try {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            // Ici on simulerait l'action sans exécuter réellement
            // (éviter les redirections pendant le debug)
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            $memoryUsed = DebugUtils::formatBytes($endMemory - $startMemory);
            
            echo "<div style='color: green;'>✅ Action simulée en {$executionTime}ms (Mémoire: $memoryUsed)</div>\n";
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>❌ Erreur: {$e->getMessage()}</div>\n";
        }
    }

    /**
     * Test de sécurité basique
     */
    public static function testBasicSecurity(): void 
    {
        echo "<h3>🔒 Tests sécurité basique</h3>\n";
        
        // Test injection dans card_id
        $maliciousInputs = [
            'card_id' => ['', 'abc', '-1', '999', '<script>', 'NULL', 'true'],
            'difficulty' => ['', 'evil', '<script>', 'très_difficile', '1; DROP TABLE games;']
        ];
        
        foreach ($maliciousInputs as $param => $values) {
            echo "<h4>🧪 Test paramètre: $param</h4>\n";
            foreach ($values as $value) {
                echo "<div>Test valeur: '$value' - ";
                
                if ($param === 'card_id') {
                    $filtered = filter_var($value, FILTER_VALIDATE_INT);
                    if ($filtered === false || $filtered < 0) {
                        echo "<span style='color: green;'>✅ Rejeté correctement</span>";
                    } else {
                        echo "<span style='color: orange;'>⚠️ Accepté: $filtered</span>";
                    }
                } else {
                    // Test difficulty
                    $allowed = ['facile', 'moyen'];
                    if (in_array($value, $allowed)) {
                        echo "<span style='color: green;'>✅ Valeur valide</span>";
                    } else {
                        echo "<span style='color: green;'>✅ Rejeté (invalide)</span>";
                    }
                }
                echo "</div>\n";
            }
        }
    }

    /**
     * Debug des performances du contrôleur
     */
    public static function benchmarkController(): void 
    {
        echo "<h3>⚡ Benchmark contrôleur</h3>\n";
        
        $iterations = 100;
        $actions = ['new_game', 'flip_card', 'restart'];
        
        foreach ($actions as $action) {
            $totalTime = 0;
            $totalMemory = 0;
            
            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                $startMemory = memory_get_usage(true);
                
                // Simulation légère de l'action
                switch ($action) {
                    case 'new_game':
                        $game = new Game('facile');
                        break;
                    case 'flip_card':
                        if (isset($game)) {
                            $game->flipCard(0);
                        }
                        break;
                    case 'restart':
                        $game = new Game('facile');
                        break;
                }
                
                $endTime = microtime(true);
                $endMemory = memory_get_usage(true);
                
                $totalTime += ($endTime - $startTime);
                $totalMemory += ($endMemory - $startMemory);
            }
            
            $avgTime = round(($totalTime / $iterations) * 1000, 2);
            $avgMemory = DebugUtils::formatBytes($totalMemory / $iterations);
            
            echo "<div><strong>$action:</strong> {$avgTime}ms/op, {$avgMemory}/op (sur $iterations ops)</div>\n";
        }
    }

    /**
     * Vérifie l'état de la base de données
     */
    public static function checkDatabaseState(): void 
    {
        echo "<h3>🗄️ État base de données</h3>\n";
        
        try {
            $pdo = Database::getInstance();
            
            // Vérifier les tables
            $tables = ['players', 'games', 'leaderboard'];
            foreach ($tables as $table) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "<div>📋 Table $table: $count enregistrements</div>\n";
            }
            
            // Vérifier les vues
            $views = ['player_stats', 'top_10_players'];
            foreach ($views as $view) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM $view");
                    $count = $stmt->fetchColumn();
                    echo "<div>👁️ Vue $view: $count enregistrements</div>\n";
                } catch (Exception $e) {
                    echo "<div style='color: red;'>❌ Vue $view: {$e->getMessage()}</div>\n";
                }
            }
            
        } catch (Exception $e) {
            echo "<div style='color: red;'>💥 Erreur BDD: {$e->getMessage()}</div>\n";
        }
    }

    /**
     * Debug complet du contrôleur
     */
    public static function fullControllerDebug(): void 
    {
        echo "<h2>🎛️ Debug complet GameController</h2>\n";
        
        // État système
        self::displaySessionState();
        
        // Requête HTTP  
        self::displayHttpRequest();
        
        // Base de données
        self::checkDatabaseState();
        
        // Tests sécurité
        self::testBasicSecurity();
        
        // Performance
        self::benchmarkController();
    }
}

/**
 * Interface simple pour le debug du contrôleur
 */
class ControllerTestSuite 
{
    /**
     * Lance tous les tests du contrôleur
     */
    public static function runAllTests(): void 
    {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Controller</title></head><body>";
        echo "<style>
            .success { color: green; }
            .error { color: red; }
            .info { color: blue; }
            .warning { color: orange; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
            h2 { border-bottom: 2px solid #333; }
            h3 { color: #666; }
        </style>";
        
        GameControllerDebugger::fullControllerDebug();
        
        echo "</body></html>";
    }

    /**
     * Test rapide du contrôleur
     */
    public static function quickTest(): void 
    {
        GameControllerDebugger::displaySessionState();
        GameControllerDebugger::checkDatabaseState();
    }
}
?>