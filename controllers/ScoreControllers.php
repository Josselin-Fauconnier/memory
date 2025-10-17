<?php

class ScoreController 
{
    public function __construct() 
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
   
    public function handleRequest(): void 
    {
        $action = $_POST['action'] ?? $_GET['action'] ?? 'leaderboard';
        
        switch ($action) {
            case 'leaderboard':
                $this->showLeaderboard();
                break;
                
            case 'personal':
                $this->showPersonalScores();
                break;
                
            case 'save_score':
                $this->saveGameScore();
                break;
                
            case 'statistics':
                $this->showStatistics();
                break;
                
            case 'validate_score':
                $this->validateScore();
                break;
                
            default:
                $this->showLeaderboard();
                break;
        }
    }
    
    
    public function showLeaderboard(): void 
    {
        try {
            
            $leaderboard = Score::getTopScores(10);
            
            // Statistiques générales
            $stats = $this->getGlobalStatistics();
            
            $data = [
                'page_title' => 'Memory Game - Classement Mondial',
                'leaderboard' => $leaderboard,
                'global_stats' => $stats,
                'total_players' => $this->getTotalPlayersCount()
            ];
            
            $this->render('leaderboard', $data);
            
        } catch (Exception $e) {
            error_log('Leaderboard error: ' . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur lors du chargement du classement'
            ];
            $this->redirect('?controller=game&action=menu');
        }
    }
    
   
    public function showPersonalScores(): void 
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['message'] = [
                'type' => 'warning',
                'text' => 'Connectez-vous pour voir vos scores personnels'
            ];
            $this->redirect('?controller=user&action=show_login');
            return;
        }
        
        try {
            $userId = $_SESSION['user_id'];

            $personalScores = Score::getPlayerScores($userId, 20);
            $playerStats = Score::getPlayerStats($userId);
            
            $data = [
                'page_title' => 'Memory Game - Mes Scores',
                'scores' => $personalScores,
                'player_stats' => $playerStats,
                'username' => $_SESSION['username'] ?? 'Joueur',
                'user_id' => $userId
            ];
            
            $this->render('personal', $data);
            
        } catch (Exception $e) {
            error_log('Personal scores error: ' . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur lors du chargement de vos scores'
            ];
            $this->redirect('?controller=game&action=menu');
        }
    }
    
  
    public function saveGameScore(): void 
    {
        try {
            
            if (!isset($_SESSION['game'])) {
                throw new Exception('Aucune partie en cours à sauvegarder');
            }
            
            $gameData = $_SESSION['game'];
            $userId = $_SESSION['user_id'] ?? null;
            $username = $_SESSION['username'] ?? 'Joueur_Anonyme';
            
            if (!$userId) {
                throw new Exception('Utilisateur non connecté');
            }
            
            $scoreErrors = Score::validateScoreData($gameData);
            
            if (!empty($scoreErrors)) {
                throw new InvalidArgumentException(implode('. ', $scoreErrors));
            }
            
            $score = new Score(
                $userId,
                $username,
                $gameData['pairs_count'],
                $gameData['moves_count'],
                $gameData['time_seconds'],
                $gameData['score']
            );
            
            $scoreSuccess = $score->save();
            $historySuccess = $score->saveToGamesHistory();
            
            if ($scoreSuccess && $historySuccess) {
                $isTopScore = Score::isTopScore($score->getScore());
                
                $message = 'Score enregistré avec succès !';
                if ($isTopScore) {
                    $message .= ' Félicitations, vous êtes dans le top 10 !';
                }
                
                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => $message
                ];
                
                unset($_SESSION['game']);
                
            } else {
                throw new Exception('Erreur lors de l\'enregistrement');
            }
            
        } catch (Exception $e) {
            error_log('Save score error: ' . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()
            ];
        }
        
        $this->redirect('?controller=score&action=leaderboard');
    }
    

    public function showStatistics(): void 
    {
        try {
            $globalStats = $this->getGlobalStatistics();
            $difficultyStats = $this->getDifficultyStatistics();
            $topPerformers = $this->getTopPerformers();
            
            $data = [
                'page_title' => 'Memory Game - Statistiques',
                'global_stats' => $globalStats,
                'difficulty_stats' => $difficultyStats,
                'top_performers' => $topPerformers
            ];
            
            $this->render('statistics', $data);
            
        } catch (Exception $e) {
            error_log('Statistics error: ' . $e->getMessage());
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur lors du chargement des statistiques'
            ];
            $this->redirect('?controller=game&action=menu');
        }
    }
    
    
    
    public function validateScore(): void 
    {
        try {
            $scoreData = $_POST;
            
            $errors = Score::validateScoreData($scoreData);
            
            if (empty($errors)) {
                echo json_encode(['valid' => true]);
            } else {
                echo json_encode(['valid' => false, 'errors' => $errors]);
            }
            
        } catch (Exception $e) {
            echo json_encode(['valid' => false, 'errors' => ['Erreur de validation']]);
        }
        
        exit;
    }
    
   
    private function getGlobalStatistics(): array 
    {
        try {
            $pdo = Database::getInstance();
            
            $sql = "SELECT 
                        COUNT(DISTINCT player_id) as total_players,
                        COUNT(*) as total_games,
                        AVG(score) as avg_score,
                        MAX(score) as best_score,
                        AVG(moves_count) as avg_moves,
                        MIN(time_seconds) as best_time
                    FROM leaderboard";
            
            $stmt = $pdo->query($sql);
            $stats = $stmt->fetch();
            
            return [
                'total_players' => (int)$stats['total_players'],
                'total_games' => (int)$stats['total_games'],
                'avg_score' => round($stats['avg_score'] ?? 0, 1),
                'best_score' => (float)($stats['best_score'] ?? 0),
                'avg_moves' => round($stats['avg_moves'] ?? 0, 1),
                'best_time' => (int)($stats['best_time'] ?? 0)
            ];
            
        } catch (Exception $e) {
            error_log('Global stats error: ' . $e->getMessage());
            return [];
        }
    }
    
    
    private function getDifficultyStatistics(): array 
    {
        try {
            $pdo = Database::getInstance();
            
            $sql = "SELECT 
                        pairs_count,
                        COUNT(*) as games_count,
                        AVG(score) as avg_score,
                        AVG(moves_count) as avg_moves,
                        AVG(time_seconds) as avg_time,
                        MAX(score) as best_score
                    FROM leaderboard 
                    GROUP BY pairs_count
                    ORDER BY pairs_count";
            
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll();
            
            $difficulties = [];
            foreach ($results as $result) {
                $difficulty = $this->getDifficultyName($result['pairs_count']);
                $difficulties[$difficulty] = [
                    'pairs_count' => (int)$result['pairs_count'],
                    'games_count' => (int)$result['games_count'],
                    'avg_score' => round($result['avg_score'], 1),
                    'avg_moves' => round($result['avg_moves'], 1),
                    'avg_time' => round($result['avg_time'], 1),
                    'best_score' => (float)$result['best_score']
                ];
            }
            
            return $difficulties;
            
        } catch (Exception $e) {
            error_log('Difficulty stats error: ' . $e->getMessage());
            return [];
        }
    }
    
    
    private function getTopPerformers(): array 
    {
        try {
            $pdo = Database::getInstance();
            
            $sql = "SELECT 
                        username,
                        COUNT(*) as games_played,
                        AVG(score) as avg_score,
                        MAX(score) as best_score
                    FROM leaderboard 
                    GROUP BY username, player_id
                    HAVING games_played >= 3
                    ORDER BY avg_score DESC 
                    LIMIT 5";
            
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log('Top performers error: ' . $e->getMessage());
            return [];
        }
    }
    
   
    private function getTotalPlayersCount(): int 
    {
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->query("SELECT COUNT(DISTINCT player_id) FROM leaderboard");
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getDifficultyName(int $pairs): string 
    {
        return match($pairs) {
            3 => 'Facile',        
            6 => 'Moyen',         
            default => 'Personnalisé'
        };
    }
  
    private function render(string $view, array $data = []): void 
    {
        $flash_message = $this->getFlashMessage();
        $page_title = $data['page_title'] ?? 'Memory Game';
        
        $leaderboard = $data['leaderboard'] ?? [];
        $scores = $data['scores'] ?? [];
        $player_stats = $data['player_stats'] ?? [];
        $global_stats = $data['global_stats'] ?? [];
        $difficulty_stats = $data['difficulty_stats'] ?? [];
        $top_performers = $data['top_performers'] ?? [];
        $username = $data['username'] ?? '';
        $user_id = $data['user_id'] ?? null;
        $total_players = $data['total_players'] ?? 0;
        
        require_once "views/score/{$view}.php";
    }
    
    
    private function redirect(string $url): void 
    {
        header("Location: {$url}");
        exit;
    }
    
    private function getFlashMessage(): ?array 
    {
        if (isset($_SESSION['message'])) {
            $message = $_SESSION['message'];
            unset($_SESSION['message']);
            return $message;
        }
        return null;
    }
}