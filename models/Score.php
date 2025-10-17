<?php

class Score 
{
    private ?int $id = null;
    private int $playerId;
    private string $username;
    private int $pairsCount;
    private int $movesCount;
    private int $timeSeconds;
    private float $score;
    private DateTime $achievedAt;
    
   
    private const MIN_SCORE = 50.0;        
    private const MAX_SCORE = 600.0;       
    private const MIN_PAIRS = 3;           // Difficulté facile = 3 paires
    private const MAX_PAIRS = 6;           // Difficulté moyen = 6 paires  
    private const MIN_MOVES = 6;           
    private const MAX_TIME_SECONDS = 120;  

    private const TIME_LIMITS = [
        3 => 60,   // 1 minute pour 3 paires (facile)
        6 => 120   // 2 minutes pour 6 paires (moyen)
    ];
    
    public function __construct(
        int $playerId, 
        string $username, 
        int $pairsCount, 
        int $movesCount, 
        int $timeSeconds, 
        float $score
    ) {
        $this->setPlayerId($playerId);
        $this->setUsername($username);
        $this->setPairsCount($pairsCount);
        $this->setMovesCount($movesCount);
        $this->setTimeSeconds($timeSeconds);
        $this->setScore($score);
        $this->achievedAt = new DateTime();
    }
    
    // Setters
    
    private function setPlayerId(int $playerId): void 
    {
        if ($playerId <= 0) {
            throw new InvalidArgumentException("L'ID joueur doit être un entier positif");
        }
        $this->playerId = $playerId;
    }
    
    private function setUsername(string $username): void 
    {
        $username = trim($username);
        if (empty($username) || strlen($username) > 50) {
            throw new InvalidArgumentException("Le nom d'utilisateur doit faire entre 1 et 50 caractères");
        }
        
        $this->username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    }
    
    private function setPairsCount(int $pairsCount): void 
    {
        if ($pairsCount < self::MIN_PAIRS || $pairsCount > self::MAX_PAIRS) {
            throw new InvalidArgumentException(
                sprintf("Nombre de paires invalide : %d (min: %d, max: %d)", 
                    $pairsCount, self::MIN_PAIRS, self::MAX_PAIRS)
            );
        }
        $this->pairsCount = $pairsCount;
    }
    
    private function setMovesCount(int $movesCount): void 
    {
        if ($movesCount < self::MIN_MOVES) {
            throw new InvalidArgumentException("Le nombre de coups doit être au minimum " . self::MIN_MOVES);
        }
        
        $minimumMoves = $this->pairsCount * 2;
        if ($movesCount < $minimumMoves) {
            throw new InvalidArgumentException(
                "Nombre de coups incohérent : $movesCount (minimum théorique: $minimumMoves)"
            );
        }
        
        $this->movesCount = $movesCount;
    }
    
    private function setTimeSeconds(int $timeSeconds): void 
    {
        if ($timeSeconds <= 0) {
            throw new InvalidArgumentException("Le temps doit être positif");
        }
        
        $maxTimeForPairs = self::TIME_LIMITS[$this->pairsCount] ?? self::MAX_TIME_SECONDS;
        if ($timeSeconds > $maxTimeForPairs) {
            throw new InvalidArgumentException(
                sprintf("Temps invalide : %d secondes (max pour %d paires: %d sec)", 
                    $timeSeconds, $this->pairsCount, $maxTimeForPairs)
            );
        }
        
        $this->timeSeconds = $timeSeconds;
    }
    
    private function setScore(float $score): void 
    {
        if ($score < self::MIN_SCORE || $score > self::MAX_SCORE) {
            throw new InvalidArgumentException(
                sprintf("Score invalide : %.2f (min: %.2f, max: %.2f)", 
                    $score, self::MIN_SCORE, self::MAX_SCORE)
            );
        }
        $this->score = round($score, 2);
    }
    
    // Getters
    
    public function getId(): ?int { return $this->id; }
    public function getPlayerId(): int { return $this->playerId; }
    public function getUsername(): string { return $this->username; }
    public function getPairsCount(): int { return $this->pairsCount; }
    public function getMovesCount(): int { return $this->movesCount; }
    public function getTimeSeconds(): int { return $this->timeSeconds; }
    public function getScore(): float { return $this->score; }
    public function getAchievedAt(): DateTime { return clone $this->achievedAt; }
    
   
    // Analyse des scores 
    
   
    public function getEfficiencyPercentage(): float 
    {
        $perfectMoves = $this->pairsCount * 2;
        if ($this->movesCount <= 0) return 0.0;
        
        return min(100.0, round(($perfectMoves / $this->movesCount) * 100, 1));
    }
    
    
    public function getAverageTimePerPair(): float 
    {
        if ($this->pairsCount <= 0) return 0.0;
        return round($this->timeSeconds / $this->pairsCount, 1);
    }
    
    
    public function getFormattedTime(): string 
    {
        $minutes = intval($this->timeSeconds / 60);
        $seconds = $this->timeSeconds % 60;
        
        if ($minutes > 0) {
            return sprintf("%d min %02d sec", $minutes, $seconds);
        }
        return sprintf("%d sec", $seconds);
    }
    
    
    public function getPerformanceLevel(): string 
    {
        $efficiency = $this->getEfficiencyPercentage();
        $timeUsage = $this->getTimeUsagePercentage();
        
        // Performance basée sur l'efficacité ET le respect du temps
        if ($efficiency >= 90 && $timeUsage <= 50) return 'Excellent';
        if ($efficiency >= 80 && $timeUsage <= 75) return 'Très bon';
        if ($efficiency >= 70 && $timeUsage <= 90) return 'Bon';
        if ($efficiency >= 60) return 'Moyen';
        return 'À améliorer';
    }
    
   
    public function isWithinTimeLimit(): bool 
    {
        $maxTime = self::TIME_LIMITS[$this->pairsCount] ?? self::MAX_TIME_SECONDS;
        return $this->timeSeconds <= $maxTime;
    }
    
   
    public function getTimeLimit(): int 
    {
        return self::TIME_LIMITS[$this->pairsCount] ?? self::MAX_TIME_SECONDS;
    }
    
   
    public function getTimeUsagePercentage(): float 
    {
        $timeLimit = $this->getTimeLimit();
        return min(100.0, round(($this->timeSeconds / $timeLimit) * 100, 1));
    }
    
    // Gestion base de donnée
    
    
    public function save(): bool 
    {
        try {
            $pdo = Database::getInstance();
            
            // Requête préparée sécurisée
            $sql = "INSERT INTO leaderboard (player_id, username, pairs_count, moves_count, time_seconds, score, achieved_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $this->playerId,
                $this->username,
                $this->pairsCount,
                $this->movesCount,
                $this->timeSeconds,
                $this->score,
                $this->achievedAt->format('Y-m-d H:i:s')
            ]);
            
            if ($success) {
                $this->id = (int)$pdo->lastInsertId();
            }
            
            return $success;
            
        } catch (PDOException $e) {
            error_log("Erreur sauvegarde score : " . $e->getMessage());
            return false;
        }
    }
    
   
    public function saveToGamesHistory(): bool 
    {
        try {
            $pdo = Database::getInstance();
            
            $sql = "INSERT INTO games (player_id, pairs_count, moves_count, time_seconds, completed_at) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                $this->playerId,
                $this->pairsCount,
                $this->movesCount,
                $this->timeSeconds,
                $this->achievedAt->format('Y-m-d H:i:s')
            ]);
            
        } catch (PDOException $e) {
            error_log("Erreur sauvegarde historique : " . $e->getMessage());
            return false;
        }
    }
    
    // Gestion récupération des données 
    
   
    public static function getTopScores(int $limit = 10): array 
    {
        try {
            $pdo = Database::getInstance();
            
            // Utilisation de la vue optimisée existante
            $sql = "SELECT * FROM top_10_players LIMIT ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur récupération top scores : " . $e->getMessage());
            return [];
        }
    }
    
    
    public static function getPlayerScores(int $playerId, int $limit = 20): array 
    {
        try {
            $pdo = Database::getInstance();
            
            $sql = "SELECT * FROM leaderboard 
                    WHERE player_id = ? 
                    ORDER BY score DESC, achieved_at DESC 
                    LIMIT ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$playerId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur récupération scores joueur : " . $e->getMessage());
            return [];
        }
    }
    
    public static function getPlayerStats(int $playerId): ?array 
    {
        try {
            $pdo = Database::getInstance();
            
            $sql = "SELECT * FROM player_stats WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$playerId]);
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            return $stats ?: null;
            
        } catch (PDOException $e) {
            error_log("Erreur récupération stats joueur : " . $e->getMessage());
            return null;
        }
    }
    
    
    public static function isTopScore(float $score): bool 
    {
        try {
            $pdo = Database::getInstance();
            
            
            $sql = "SELECT score FROM leaderboard ORDER BY score DESC LIMIT 10";
            $stmt = $pdo->query($sql);
            $topScores = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            
            if (count($topScores) < 10) {
                return true;
            }
            
            return $score > end($topScores);
            
        } catch (PDOException $e) {
            error_log("Erreur vérification top score : " . $e->getMessage());
            return false;
        }
    }
    
    
    public static function fromGameStats(array $finalStats, int $playerId, string $username): self 
    {
        $requiredFields = ['pairs_count', 'moves', 'time_seconds', 'score'];
        foreach ($requiredFields as $field) {
            if (!isset($finalStats[$field])) {
                throw new InvalidArgumentException("Champ manquant dans finalStats : $field");
            }
        }
        
        return new self(
            $playerId,
            $username,
            $finalStats['pairs_count'],
            $finalStats['moves'],
            $finalStats['time_seconds'],
            $finalStats['score']
        );
    }
    
   
    public function toArray(): array 
    {
        return [
            'id' => $this->id,
            'player_id' => $this->playerId,
            'username' => $this->username,
            'pairs_count' => $this->pairsCount,
            'moves_count' => $this->movesCount,
            'time_seconds' => $this->timeSeconds,
            'time_formatted' => $this->getFormattedTime(),
            'score' => $this->score,
            'efficiency_percentage' => $this->getEfficiencyPercentage(),
            'average_time_per_pair' => $this->getAverageTimePerPair(),
            'performance_level' => $this->getPerformanceLevel(),
            'achieved_at' => $this->achievedAt->format('Y-m-d H:i:s')
        ];
    }
    
    
    public static function fromArray(array $data): self 
    {
        $requiredFields = ['player_id', 'username', 'pairs_count', 'moves_count', 'time_seconds', 'score'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Champ manquant pour créer Score : $field");
            }
        }
        
        $score = new self(
            (int)$data['player_id'],
            $data['username'],
            (int)$data['pairs_count'],
            (int)$data['moves_count'],
            (int)$data['time_seconds'],
            (float)$data['score']
        );
        
        if (isset($data['id'])) {
            $score->id = (int)$data['id'];
        }
        
        if (isset($data['achieved_at'])) {
            $score->achievedAt = new DateTime($data['achieved_at']);
        }
        
        return $score;
    }
    
    
    public static function validateScoreData(array $data): array 
    {
        $errors = [];
        
        if (!isset($data['pairs_count']) || $data['pairs_count'] < self::MIN_PAIRS) {
            $errors[] = "Nombre de paires invalide";
        }
        
        if (!isset($data['moves_count']) || $data['moves_count'] < self::MIN_MOVES) {
            $errors[] = "Nombre de coups invalide";
        }
        
        if (!isset($data['time_seconds']) || $data['time_seconds'] <= 0) {
            $errors[] = "Temps invalide";
        }
        
        if (!isset($data['score']) || $data['score'] < self::MIN_SCORE) {
            $errors[] = "Score invalide";
        }
        
        
        if (isset($data['pairs_count'], $data['moves_count'])) {
            $minimumMoves = $data['pairs_count'] * 2;
            if ($data['moves_count'] < $minimumMoves) {
                $errors[] = "Incohérence : nombre de coups trop faible par rapport aux paires";
            }
        }
        
        if (isset($data['pairs_count'], $data['time_seconds'])) {
            $timeLimit = self::TIME_LIMITS[$data['pairs_count']] ?? self::MAX_TIME_SECONDS;
            if ($data['time_seconds'] > $timeLimit) {
                $errors[] = "Temps dépassé pour {$data['pairs_count']} paires (limite: {$timeLimit}s)";
            }
        }
        
        return $errors;
    }
}

?>