<?php

class GameController {
    private ?Game $game;
    
    public function __construct() 
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->game = null;
    }
    
   
    public function handleRequest(): void {
        $action = $_POST['action'] ?? $_GET['action'] ?? 'menu';
        
        switch ($action) {
            case 'new_game':
                $this->startNewGame();
                break;
                
            case 'flip_card':
                $this->flipCard();
                break;
                
            case 'restart':
                $this->restartGame();
                break;
            case 'hide_cards':         
            $this->hideCards();     
            break;   
                
            case 'game':
                $this->showGame();
                break;
                
            case 'leaderboard':
                $this->showLeaderboard();
                break;
                
            case 'menu':
            default:
                $this->showMenu();
                break;
        }
    }
    
    
    private function showMenu(): void {
        $data = [
            'page_title' => 'Memory Game - Menu Principal'
        ];
        
        $this->render('menu', $data);
    }
    
    
    private function startNewGame(): void {
        try {
            $difficulty = $_POST['difficulty'] ?? 'facile';
    
            if (!in_array($difficulty, ['facile', 'moyen'])) {
                throw new InvalidArgumentException("Difficulté invalide : {$difficulty}");
            }

            $this->game = new Game($difficulty);
            
            $_SESSION['game'] = $this->game->toSessionArray();
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => "Nouvelle partie ({$difficulty}) créée ! Trouvez toutes les paires."
            ];
            
            $this->redirect('?action=game');
            
        } catch (Exception $e) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur lors de la création : ' . $e->getMessage()
            ];
            
            $this->redirect('?action=menu');
        }
    }

    private function showGame(): void 
    {
        try {
            $this->loadGameFromSession();
            
            if (!$this->game) {
                $_SESSION['message'] = [
                    'type' => 'warning',
                    'text' => 'Aucune partie en cours. Créez une nouvelle partie.'
                ];
                $this->redirect('?action=menu');
                return;
            }
            
            $data = [
                'page_title' => 'Memory Game - Partie en cours',
                'game' => $this->game,
                'cards' => $this->game->getCards(),
                'game_state' => $this->game->getGameState(),
                'is_completed' => $this->game->isCompleted()
            ];
            
            if ($this->game->isCompleted()) {
                $data['final_stats'] = $this->game->getFinalStats();
                $this->saveScore();
            }
            
            $this->render('game', $data);
            
        } catch (Exception $e) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur : ' . $e->getMessage()
            ];
            $this->redirect('?action=menu');
        }
    }
    
    private function flipCard(): void {
        try {
            $this->loadGameFromSession();
            
            if (!$this->game) {
                throw new Exception('Aucune partie en cours');
            }
            
           
            $position = filter_input(INPUT_POST, 'card_id', FILTER_VALIDATE_INT);
            if ($position === false || $position === null) {
                throw new Exception('Position de carte invalide');
            }
            
           
            $result = $this->game->flipCard($position);
            
            if ($result['success']) {
                
                $_SESSION['game'] = $this->game->toSessionArray();
                if (isset($result['match'])) {
                    if ($result['match']) {
                        if ($result['game_completed'] ?? false) {
                            $_SESSION['message'] = [
                                'type' => 'success',
                                'text' =>  $result['message'] . ' Score : ' . $result['final_score']
                            ];
                        } else {
                            $_SESSION['message'] = [
                                'type' => 'success', 
                                'text' =>  $result['message']
                            ];
                        }
                    } else {
                        $_SESSION['message'] = [
                            'type' => 'info',
                            'text' =>  $result['message'],
                            'hide_cards' => true
                        ];
                    }
                }
            } else {
                $_SESSION['message'] = [
                    'type' => 'warning',
                    'text' => $result['message']
                ];
            }
            
        } catch (Exception $e) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur : ' . $e->getMessage()
            ];
        }
        
        $this->redirect('?action=game');
    }
    

    public function hideCards(): void {
        try {
            $this->loadGameFromSession();
            
            if ($this->game) {
                $this->game->hideNoMatchingCards();
                $_SESSION['game'] = $this->game->toSessionArray();
            }
            
        } catch (Exception $e) {
            error_log('Erreur lors du cachage : ' . $e->getMessage());
        }
        
        $this->redirect('?action=game');
    }
    
   
    private function restartGame(): void {
        try {
            $this->loadGameFromSession();
            
            if ($this->game) {
                $difficulty = $this->game->getDifficulty();
                $playerId = $this->game->getPlayerId();
               
                $this->game = new Game($difficulty, $playerId);
                $_SESSION['game'] = $this->game->toSessionArray();
                
                $_SESSION['message'] = [
                    'type' => 'info',
                    'text' => 'Partie redémarrée !'
                ];
            }
            
        } catch (Exception $e) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur lors du redémarrage : ' . $e->getMessage()
            ];
        }
        
        $this->redirect('?action=game');
    }
    
   
    private function loadGameFromSession(): void {
        if (isset($_SESSION['game']) && is_array($_SESSION['game'])) {
            try {
                $this->game = Game::fromSessionArray($_SESSION['game']);
            } catch (Exception $e) {
                error_log('Erreur chargement session : ' . $e->getMessage());
                unset($_SESSION['game']);
                $this->game = null;
            }
        }
    }
    
   
    private function saveScore(): void {
        try {
            if (!$this->game || !$this->game->isCompleted()) {
                return;
            }
            
            $pdo = Database::getInstance();
            $stats = $this->game->getFinalStats();
            
            $sql = "INSERT INTO leaderboard (player_id, username, pairs_count, moves_count, time_seconds, score) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $this->game->getPlayerId(),
                'Joueur_' . ($this->game->getPlayerId() ?? 'Anonyme'),
                $stats['pairs_count'],
                $stats['moves'],
                $stats['time_seconds'],
                $stats['score']
            ]);
            
        } catch (Exception $e) {
            error_log('Erreur sauvegarde score : ' . $e->getMessage());
        }
    }
    
   
    public function showLeaderboard(): void 
    {
        try {
            $pdo = Database::getInstance();
            
            $sql = "SELECT * FROM top_10_players";
            $stmt = $pdo->query($sql);
            $leaderboard = $stmt->fetchAll();
            
            $data = [
                'page_title' => 'Memory Game - Classement',
                'leaderboard' => $leaderboard
            ];
            
            $this->render('leaderboard', $data);
            
        } catch (Exception $e) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Erreur chargement classement : ' . $e->getMessage()
            ];
            $this->redirect('?action=menu');
        }
    }
    
    
    public function getFlashMessage(): ?array 
    {
        if (isset($_SESSION['message'])) {
            $message = $_SESSION['message'];
            unset($_SESSION['message']);
            return $message;
        }
        return null;
    }
    
   private function render(string $view, array $data = []): void {
    
    $flash_message = $this->getFlashMessage();
    $page_title = $data['page_title'] ?? 'Memory Game';
    
    $game = $data['game'] ?? null;
    $cards = $data['cards'] ?? [];
    $game_state = $data['game_state'] ?? [];
    $is_completed = $data['is_completed'] ?? false;
    $final_stats = $data['final_stats'] ?? [];
    $leaderboard = $data['leaderboard'] ?? [];
    
    
    extract($data);
    
    
    if (!isset($flash_message)) {
        $flash_message = null;
    }
    
    $viewFile = "views/{$view}.php";
    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        throw new RuntimeException("Vue '{$view}' introuvable : {$viewFile}");
    }
}

    private function redirect(string $url): void {
        header("Location: {$url}");
        exit;
    }
}