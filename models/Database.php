<?php

class Database {
    
    private static ?PDO $instance = null;
    
   
    private static ?array $config = null;

    /**
     * @return PDO Instance de connexion à la base de données
     * @throws PDOException Si la connexion échoue
     */

    public static function getInstance(): PDO 
    {
        if (self::$instance === null) {
            self::createConnection();
        }
        
        return self::$instance;
    }

    /**
     * @throws PDOException Si la connexion échoue
     */
    private static function createConnection(): void 
    {
        try {
            
            if (self::$config === null) {
                self::loadConfiguration();
            }
            
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                self::$config['host'],
                self::$config['dbname'],
                self::$config['charset']
            );
            
            self::$instance = new PDO(
                $dsn,
                self::$config['username'],
                self::$config['password'],
                self::$config['options']
            );
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new PDOException("Database connection unavailable");
        }
    }

    /**
     * @throws RuntimeException Si le fichier de config n'existe pas
     */
    private static function loadConfiguration(): void 
    {
        $configPath = __DIR__ . '/../config/database.php';
        
        if (!file_exists($configPath)) {
            throw new RuntimeException("Configuration file not found: {$configPath}");
        }
        
        $loadedConfig = require $configPath;
        
        if (!is_array($loadedConfig)) {
            throw new RuntimeException("Configuration file must return an array");
        }
        
        self::$config = $loadedConfig;
        
        self::validateConfiguration();
    }

    /**
     * @throws InvalidArgumentException Si la configuration est invalide
     */
    private static function validateConfiguration(): void 
    {
        $requiredKeys = ['host', 'dbname', 'username', 'password', 'charset', 'options'];
        
        foreach ($requiredKeys as $key) {
            if (!isset(self::$config[$key])) {
                throw new InvalidArgumentException("Missing required configuration key: {$key}");
            }
        }
        if (!is_array(self::$config['options'])) {
            throw new InvalidArgumentException("Configuration 'options' must be an array");
        }
    }

    public static function testConnection(): bool 
    {
        try {
            $pdo = self::getInstance();
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            error_log("Connection test failed: " . $e->getMessage());
            return false;
        }
    }

    
  
    private function __construct() {}

    
    private function __clone() {}

    
    public function __wakeup(): void 
    {
        throw new Exception("Impossible de désérialiser l'instance singleton Database");
    }
}