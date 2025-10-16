<?php


class DebugUtils 
{
    /**
     * Affiche un tableau de manière structurée
     */
    public static function prettyPrint(array $data, string $title = ""): void 
    {
        if ($title) {
            echo "\n=== $title ===\n";
        }
        
        self::printArrayRecursive($data, 0);
        echo "\n";
    }

    /**
     * Affichage récursif d'un tableau
     */
    private static function printArrayRecursive(array $data, int $indent = 0): void 
    {
        $spaces = str_repeat('  ', $indent);
        
        foreach ($data as $key => $value) {
            echo $spaces . $key . ': ';
            
            if (is_array($value)) {
                echo "\n";
                self::printArrayRecursive($value, $indent + 1);
            } elseif (is_bool($value)) {
                echo ($value ? 'true' : 'false') . "\n";
            } elseif (is_null($value)) {
                echo "null\n";
            } elseif (is_string($value)) {
                echo htmlspecialchars($value) . "\n";
            } else {
                echo $value . "\n";
            }
        }
    }

    /**
     * Formate la taille en octets
     */
    public static function formatBytes(int $bytes): string 
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Mesure le temps d'exécution d'une fonction
     */
    public static function measureExecutionTime(callable $callback): array 
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $result = $callback();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        return [
            'result' => $result,
            'execution_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_used' => self::formatBytes($endMemory - $startMemory),
            'peak_memory' => self::formatBytes(memory_get_peak_usage(true))
        ];
    }

    /**
     * Log de debug simple
     */
    public static function debugLog(string $message, string $level = 'INFO'): void 
    {
        $timestamp = date('Y-m-d H:i:s');
        echo "[$timestamp] [$level] $message\n";
    }

    /**
     * Affiche les informations système de base
     */
    public static function displaySystemInfo(): void 
    {
        echo "\n=== Informations Système ===\n";
        
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "Memory Limit: " . ini_get('memory_limit') . "\n";
        echo "Max Execution Time: " . ini_get('max_execution_time') . "s\n";
        echo "Upload Max: " . ini_get('upload_max_filesize') . "\n";
        echo "Post Max: " . ini_get('post_max_size') . "\n";
        
        echo "\n--- Mémoire ---\n";
        echo "Utilisée: " . self::formatBytes(memory_get_usage(true)) . "\n";
        echo "Pic: " . self::formatBytes(memory_get_peak_usage(true)) . "\n";
        
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit !== '-1') {
            $limitBytes = self::parseMemoryLimit($memoryLimit);
            $usagePercent = round((memory_get_usage(true) / $limitBytes) * 100, 1);
            echo "Utilisation: {$usagePercent}%\n";
        }
        
        echo "\n--- Extensions ---\n";
        $requiredExtensions = ['pdo', 'pdo_mysql', 'session', 'filter', 'mbstring'];
        foreach ($requiredExtensions as $ext) {
            $status = extension_loaded($ext) ? 'OK' : 'MANQUANTE';
            echo "$ext: $status\n";
        }
        
        echo "\n--- Configuration ---\n";
        echo "Timezone: " . date_default_timezone_get() . "\n";
        echo "Charset: " . ini_get('default_charset') . "\n";
        echo "Error Reporting: " . error_reporting() . "\n";
        echo "Display Errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "\n";
    }

    /**
     * Parse une limite mémoire PHP
     */
    private static function parseMemoryLimit(string $limit): int 
    {
        $limit = trim($limit);
        $lastChar = strtoupper(substr($limit, -1));
        $number = (int)substr($limit, 0, -1);
        
        switch ($lastChar) {
            case 'G':
                return $number * 1024 * 1024 * 1024;
            case 'M':
                return $number * 1024 * 1024;
            case 'K':
                return $number * 1024;
            default:
                return (int)$limit;
        }
    }

   

    /**
     * Génère un rapport de debug
     */
    public static function generateDebugReport(): string 
    {
        ob_start();
        
        echo "=== RAPPORT DE DEBUG ===\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        
        
        self::displaySystemInfo();
        
        echo "\n=== Variables Globales ===\n";
        echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "\n";
        echo "HTTP_USER_AGENT: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n";
        echo "Session Status: " . session_status() . "\n";
        echo "Session ID: " . session_id() . "\n";
        
        return ob_get_clean();
    }

    /**
     * Sauvegarde un rapport
     */
    public static function saveDebugReport(string $filename = ''): string 
    {
        if (empty($filename)) {
            $filename = 'debug_report_' . date('Y-m-d_H-i-s') . '.txt';
        }
        
        $report = self::generateDebugReport();
        
        if (file_put_contents($filename, $report)) {
            return $filename;
        } else {
            throw new Exception("Impossible de sauvegarder le rapport: $filename");
        }
    }

    /**
     * Benchmark d'une fonction
     */
    public static function benchmark(callable $function, int $iterations = 100): array 
    {
        $times = [];
        $memories = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            $function();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $times[] = ($endTime - $startTime) * 1000;
            $memories[] = $endMemory - $startMemory;
        }
        
        return [
            'iterations' => $iterations,
            'avg_time_ms' => round(array_sum($times) / count($times), 3),
            'min_time_ms' => round(min($times), 3),
            'max_time_ms' => round(max($times), 3),
            'avg_memory' => self::formatBytes(array_sum($memories) / count($memories)),
            'total_time_ms' => round(array_sum($times), 2)
        ];
    }

    /**
     * Log silencieux dans fichier
     */
    public static function silentLog(string $message, string $level = 'INFO'): void 
    {
        $logFile = 'debug_silent_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Nettoie les anciens logs
     */
    public static function cleanOldLogs(int $daysToKeep = 7): int 
    {
        $pattern = 'debug_*.log';
        $files = glob($pattern);
        $deletedCount = 0;
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }
        
        return $deletedCount;
    }
}
?>