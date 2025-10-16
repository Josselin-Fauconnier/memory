<?php



class Game 
{
    private array $cards;
    private array $flippedPositions;
    private int $foundPairs;
    private int $moves;
    private int $startTime;
    private bool $isCompleted; 
    private string $difficulty;
    private int $totalPairs;
    private ?int $playerId;

   public function __construct(string $difficulty = 'facile', ?int $playerId = null) {
    $this->validateAndSetDifficulty($difficulty);
    
    if ($playerId !== null && $playerId <= 0) {
        throw new InvalidArgumentException("L'ID du joueur doit être un entier positif");
    }
    
    $this->foundPairs = 0;
    $this->moves = 0;
    $this->flippedPositions = [];
    $this->isCompleted = false;
    $this->playerId = $playerId;
    $this->startTime = time();
    
    $this->initializeCards();
}

    private function validateAndSetDifficulty(string $difficulty): void 
    {
        $difficulties = [
            'facile' => 3,  
            'moyen'  => 6   
        ];
        
        $normalizedDifficulty = strtolower(trim($difficulty));
        
        if (!array_key_exists($normalizedDifficulty, $difficulties)) {
            throw new InvalidArgumentException(
                "Difficulté invalide: '{$difficulty}'. Choix disponibles: " . implode(', ', array_keys($difficulties))
            );
        }
        
        $this->difficulty = $normalizedDifficulty;
        $this->totalPairs = $difficulties[$normalizedDifficulty];
        
        $availableImages = Card::getAvailableImages();
        if ($this->totalPairs > count($availableImages)) {
            throw new InvalidArgumentException(
                "Pas assez d'images pour la difficulté '{$normalizedDifficulty}' ({$this->totalPairs} paires). Images disponibles: " . count($availableImages)
            );
        }
    } 

    private function initializeCards(): void 
    {
        $selectedImages = Card::getImagesForGame($this->totalPairs);
        $cardDeck = [];
        $cardId = 0;
        
        foreach ($selectedImages as $image) {
            $cardDeck[] = new Card($cardId++, $image);
            $cardDeck[] = new Card($cardId++, $image);
        }
        
        $this->shuffleCards($cardDeck);
        $this->cards = $cardDeck;
    }

    private function shuffleCards(array &$cards): void 
    {
        $count = count($cards);
        
        for ($i = $count - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$cards[$i], $cards[$j]] = [$cards[$j], $cards[$i]];
        }
    }

    public function flipCard(int $position): array  
    {
        if ($position < 0) {
        return [
            'success' => false, 
            'message' => 'Position ne peut pas être négative',
            'error_code' => 'NEGATIVE_POSITION'
        ];
    }
        if (!$this->isValidPosition($position)) { 
            return [
                'success' => false, 
                'message' => 'Position invalide',
                'error_code' => 'INVALID_POSITION'
            ];
        }
        
        if ($this->isCompleted) {
            return [
                'success' => false, 
                'message' => 'Partie déjà terminée',
                'error_code' => 'GAME_COMPLETED'
            ];
        }
        
        $card = $this->cards[$position];
        
        if (!$card->canBeFlipped()) {
            return [
                'success' => false, 
                'message' => 'Cette carte ne peut pas être retournée',
                'error_code' => 'CARD_NOT_FLIPPABLE'
            ];
        }
        
        if (count($this->flippedPositions) >= 2) {
            return [
                'success' => false, 
                'message' => 'Deux cartes déjà retournées. Attendez le résultat.',
                'error_code' => 'TOO_MANY_FLIPPED'
            ];
        }
       
        $card->flip();
        $this->flippedPositions[] = $position;
        
        $result = [
            'success' => true,
            'position' => $position,
            'card_id' => $card->getId(),
            'image' => $card->getImage(),
            'flipped_count' => count($this->flippedPositions)
        ];
        
        if (count($this->flippedPositions) === 2) {
            $matchResult = $this->processMatchAttempt();
            $result = array_merge($result, $matchResult);
        }
        
        return $result;
    }

    private function processMatchAttempt(): array 
    {
        $this->moves++; 
        
        $pos1 = $this->flippedPositions[0];
        $pos2 = $this->flippedPositions[1];
        $card1 = $this->cards[$pos1];
        $card2 = $this->cards[$pos2];
        
        if ($card1->matches($card2)) {
            $card1->match();
            $card2->match();
            $this->foundPairs++;
            
            $this->flippedPositions = [];
            
            if ($this->foundPairs === $this->totalPairs) {
                $this->isCompleted = true;
                
                return [
                    'match' => true,
                    'positions' => [$pos1, $pos2],
                    'game_completed' => true,
                    'final_score' => $this->calculateScore(),
                    'total_time' => $this->getElapsedTime(),
                    'total_moves' => $this->moves,
                    'message' => 'Félicitations ! Partie terminée !'
                ];
            }
            
            return [
                'match' => true,
                'positions' => [$pos1, $pos2],
                'game_completed' => false,
                'pairs_found' => $this->foundPairs,
                'pairs_remaining' => $this->totalPairs - $this->foundPairs,
                'message' => 'Paire trouvée !'
            ];
            
        } else {
            return [
                'match' => false,
                'positions' => [$pos1, $pos2],
                'game_completed' => false,
                'message' => 'Pas de correspondance. Les cartes vont se retourner.',
                'hide_delay' => 1000 
            ];
        }
    }

    public function hideNoMatchingCards(): bool 
    {
        if (empty($this->flippedPositions)) {
            return false;
        }
        
        $hidden = false;
        foreach ($this->flippedPositions as $position) {
            if ($this->cards[$position]->hide()) {
                $hidden = true;
            }
        }
        
        $this->flippedPositions = [];
        return $hidden;
    }

    private function calculateScore(): int 
    {
        $basePoints = [
            'facile' => 300,  
            'moyen'  => 600   
        ];
        
        $score = $basePoints[$this->difficulty] ?? 300;
        
        $elapsedTime = $this->getElapsedTime();
        if ($elapsedTime < 120) {
           
        }
        
        $minimumMoves = $this->totalPairs * 2;
        $extraMoves = $this->moves - $minimumMoves;
        
        if ($extraMoves > 0) {
            $score -= ($extraMoves * 10);
        }
        
        return max(50, $score);
    }

    private function isValidPosition(int $position): bool 
    {
        return $position >= 0 && $position < count($this->cards);
    }

    // Getters
    public function getCardsData(): array 
    {
        $cardsData = [];
        foreach ($this->cards as $index => $card) {
            $cardData = $card->toArray();
            $cardData['position'] = $index;
            $cardsData[] = $cardData;
        }
        return $cardsData;
    }

    public function getCards(): array 
    {
        return $this->cards;
    }

    public function getMoves(): int 
    {
        return $this->moves;
    }

    public function getFoundPairs(): int 
    {
        return $this->foundPairs;
    }

    public function getDifficulty(): string 
    {
        return $this->difficulty;
    }
    
    public function getTotalPairs(): int 
    {
        return $this->totalPairs;
    }
    
    public function isCompleted(): bool 
    {
        return $this->isCompleted;
    }
    
    public function getElapsedTime(): int 
    {
        return time() - $this->startTime;
    }
    
    public function getScore(): int 
    {
        return $this->calculateScore();
    }
    
    public function getPlayerId(): ?int 
    {
        return $this->playerId;
    }
    
    public function getFlippedPositions(): array 
    {
        return $this->flippedPositions;
    }
    
    public function getProgressPercentage(): float 
    {
        if ($this->totalPairs === 0) {
            return 100.0;
        }
        return round(($this->foundPairs / $this->totalPairs) * 100, 1);
    }
    
    public function getGameState(): array 
    {
        return [
            'cards' => $this->getCardsData(),
            'moves' => $this->moves,
            'found_pairs' => $this->foundPairs,
            'total_pairs' => $this->totalPairs,
            'elapsed_time' => $this->getElapsedTime(),
            'is_completed' => $this->isCompleted,
            'score' => $this->getScore(),
            'flipped_positions' => $this->flippedPositions,
            'progress_percentage' => $this->getProgressPercentage(),
            'player_id' => $this->playerId,
            'start_time' => $this->startTime
        ];
    }
    
    public function getFinalStats(): array 
    {
        if (!$this->isCompleted) {
            throw new LogicException("La partie n'est pas encore terminée");
        }
        
        $elapsedTime = $this->getElapsedTime();
        $efficiency = $this->totalPairs > 0 ? ($this->foundPairs / $this->moves) * 100 : 0;
        
        return [
            'score' => $this->getScore(),
            'moves' => $this->moves,
            'time_seconds' => $elapsedTime,
            'time_formatted' => $this->formatTime($elapsedTime),
            'pairs_count' => $this->totalPairs,
            'difficulty' => $this->difficulty,
            'efficiency_percentage' => round($efficiency, 1),
            'average_time_per_pair' => $this->foundPairs > 0 ? round($elapsedTime / $this->foundPairs, 1) : 0,
            'player_id' => $this->playerId
        ];
    }
    
    private function formatTime(int $seconds): string 
    {
        $minutes = intval($seconds / 60);
        $seconds = $seconds % 60;
        
        if ($minutes > 0) {
            return sprintf("%d min %02d sec", $minutes, $seconds);
        } else {
            return sprintf("%d sec", $seconds);
        }
    }
    
    public function toSessionArray(): array 
    {
        return [
            'cards' => array_map(fn($card) => $card->toArray(), $this->cards),
            'flipped_positions' => $this->flippedPositions,
            'total_pairs' => $this->totalPairs,
            'difficulty' => $this->difficulty,
            'found_pairs' => $this->foundPairs,
            'moves' => $this->moves,
            'start_time' => $this->startTime,
            'is_completed' => $this->isCompleted,
            'player_id' => $this->playerId
        ];
    }
    
    public static function fromSessionArray(array $data): Game 
    {
        $requiredFields = ['cards', 'total_pairs', 'difficulty', 'found_pairs', 'moves', 'start_time'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Données de session invalides : champ '{$field}' manquant");
            }
        }

         if (!is_array($data['cards'])) {
        throw new InvalidArgumentException("Les cartes doivent être un tableau");
    }
    
        if (!is_int($data['total_pairs']) || $data['total_pairs'] <= 0) {
        throw new InvalidArgumentException("Le nombre total de paires doit être un entier positif");
    }
    
       if (!is_int($data['moves']) || $data['moves'] < 0) {
        throw new InvalidArgumentException("Le nombre de coups doit être un entier non négatif");
    }
    
       if (!is_int($data['start_time']) || $data['start_time'] <= 0) {
        throw new InvalidArgumentException("L'heure de début doit être un timestamp valide");
    }
        
        $game = new self($data['difficulty'], $data['player_id'] ?? null);
        
        $game->cards = array_map(fn($cardData) => Card::fromArray($cardData), $data['cards']);
        
        $game->flippedPositions = $data['flipped_positions'] ?? [];
        $game->foundPairs = $data['found_pairs'];
        $game->moves = $data['moves'];
        $game->startTime = $data['start_time'];
        $game->isCompleted = $data['is_completed'] ?? false;
        
        return $game;
    }
    
    
}