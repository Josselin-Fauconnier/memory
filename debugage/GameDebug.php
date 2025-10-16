<?php

require_once 'Game.php';
require_once 'CardDebug.php';  
require_once 'GeneralDebug.php';


/**
 * Fonctions de debug pour la classe Game
 */
class GameDebugger 
{
    /**
     * Affichage textuel du jeu
     */
    public static function gameToString(Game $game): string 
    {
        $status = $game->isCompleted() ? 'TERMINÉE' : 'EN COURS';
        return sprintf(
            "Partie %s (%s) - %d/%d paires trouvées - %d coups - %s",
            $status,
            ucfirst($game->getDifficulty()),
            $game->getFoundPairs(),
            $game->getTotalPairs(),
            $game->getMoves(),
            self::formatTime($game->getElapsedTime())
        );
    }

    /**
     * Debug complet du jeu
     */
    public static function debugGame(Game $game): array 
    {
        return [
            'game_state' => $game->getGameState(),
            'cards_debug' => array_map([CardDebugger::class, 'debugCard'], $game->getCards()),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'image_analysis' => CardDebugger::analyzeImageDistribution($game->getCards())
        ];
    }

    /**
     * Formate le temps en minutes/secondes
     */
    private static function formatTime(int $seconds): string 
    {
        $minutes = intval($seconds / 60);
        $seconds = $seconds % 60;
        
        if ($minutes > 0) {
            return sprintf("%d min %02d sec", $minutes, $seconds);
        } else {
            return sprintf("%d sec", $seconds);
        }
    }

    /**
     * Affiche l'état détaillé du jeu
     */
    public static function displayGameState(Game $game): void 
    {
        echo "<h2>🎮 État du jeu</h2>\n";
        echo "<div>" . self::gameToString($game) . "</div>\n";
        
        $state = $game->getGameState();
        echo "<h3>📊 Statistiques :</h3>\n";
        echo "<ul>\n";
        echo "<li>Progression: {$state['progress_percentage']}%</li>\n";
        echo "<li>Score actuel: {$state['score']}</li>\n";
        echo "<li>Cartes retournées: " . count($state['flipped_positions']) . "</li>\n";
        echo "</ul>\n";

        CardDebugger::displayCards($game->getCards());
    }

    /**
     * Simule des coups de jeu pour tester
     */
    public static function simulateRandomMoves(Game $game, int $moveCount = 5): array 
    {
        $results = [];
        $cards = $game->getCards();
        $maxPosition = count($cards) - 1;

        echo "<h3>🎲 Simulation de $moveCount coups aléatoires</h3>\n";

        for ($i = 0; $i < $moveCount && !$game->isCompleted(); $i++) {
            $randomPosition = random_int(0, $maxPosition);
            $result = $game->flipCard($randomPosition);
            
            $results[] = [
                'move' => $i + 1,
                'position' => $randomPosition,
                'result' => $result
            ];

            $status = $result['success'] ? '✅' : '❌';
            echo "<div>Coup " . ($i + 1) . ": Position $randomPosition $status</div>\n";
            
            if (isset($result['message'])) {
                echo "<div style='margin-left: 20px; color: blue;'>{$result['message']}</div>\n";
            }
        }

        return $results;
    }

    /**
     * Trouve toutes les paires possibles (triche pour les tests!)
     */
    public static function findAllPairs(Game $game): array 
    {
        $cards = $game->getCards();
        $pairs = [];
        
        for ($i = 0; $i < count($cards); $i++) {
            for ($j = $i + 1; $j < count($cards); $j++) {
                if ($cards[$i]->matches($cards[$j])) {
                    $pairs[] = [
                        'image' => $cards[$i]->getImage(),
                        'positions' => [$i, $j]
                    ];
                }
            }
        }
        
        return $pairs;
    }

    /**
     * Mode triche : résout automatiquement le jeu
     */
    public static function solveGame(Game $game): void 
    {
        echo "<h3>🧙‍♂️ Mode triche activé - Résolution automatique</h3>\n";
        
        $pairs = self::findAllPairs($game);
        
        foreach ($pairs as $pair) {
            if ($game->isCompleted()) break;
            
            $pos1 = $pair['positions'][0];
            $pos2 = $pair['positions'][1];
            
            echo "<div>🎯 Tentative paire {$pair['image']}: positions $pos1 et $pos2</div>\n";
            
            $result1 = $game->flipCard($pos1);
            if ($result1['success']) {
                $result2 = $game->flipCard($pos2);
                if ($result2['success'] && isset($result2['match']) && $result2['match']) {
                    echo "<div style='color: green;'>✨ Paire trouvée!</div>\n";
                }
            }
        }
        
        if ($game->isCompleted()) {
            echo "<div style='color: green; font-weight: bold;'>🎉 Jeu résolu automatiquement!</div>\n";
            $stats = $game->getFinalStats();
            echo "<div>Score final: {$stats['score']}</div>\n";
        }
    }
}

?>