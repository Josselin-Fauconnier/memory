<?php

class Database {
    private static ?PDO $instance =null;

    private static array $config = [
        'host' => 'localhost',
         'dbname' => 'memory_game',
         'username' => 'root',
         'password' =>'',
         'charset' => 'utf8mb4'
    ];


    private static array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,  
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];




   public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
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
                    self::$options
                );
                
            } catch (PDOException $e) {
               
                error_log("Erreur de connexion BDD: " . $e->getMessage());
                throw new PDOException("Impossible de se connecter à la base de données");
            }
        }
        
        return self::$instance;
    }


    // sécurisation singleton

    private function __construct() {}

    private function __clone() {}



public static function testConnection(): bool {
    try {
        $pdo = self::getInstance();
        $pdo->query("SELECT 1");
        return true;
    }catch (PDOException $e){
        return false;
    }
}

    
}
