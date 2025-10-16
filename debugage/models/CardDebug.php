<?php
require_once 'Card.php';

class CardDebugger 
{
    /**
     * Affichage textuel d'une carte
     */
    public static function cardToString(Card $card): string 
    {
        $status = $card->isMatched() ? 'TROUVÉE' : 
                 ($card->isFlipped() ? 'VISIBLE' : 'CACHÉE');
                 
        return "Carte #{$card->getId()} ({$card->getImage()}) - {$status}";
    }

    /**
     * Debug complet d'une carte
     */
    public static function debugCard(Card $card): array 
    {
        return [
            'id' => $card->getId(),
            'image' => $card->getImage(),
            'image_path' => $card->getImagePath(),
            'is_flipped' => $card->isFlipped(),
            'is_matched' => $card->isMatched(),
            'can_be_flipped' => $card->canBeFlipped(),
            'css_classes' => self::getCssClasses($card),
            'display_image' => self::getDisplayImage($card)
        ];
    }

    /**
     * Récupère les classes CSS d'une carte (méthode privée exposée pour debug)
     */
    private static function getCssClasses(Card $card): string 
    {
        $classes = [];

        if ($card->isFlipped()) {
            $classes[] = 'flipped';
        }

        if ($card->isMatched()) {
            $classes[] = 'matched';
        }

        if ($card->canBeFlipped()) {
            $classes[] = 'clickable';
        }

        return implode(' ', $classes);
    }

    /**
     * Récupère l'image à afficher 
     */
    private static function getDisplayImage(Card $card): string 
    {
        if ($card->isFlipped() || $card->isMatched()) {
            return $card->getImagePath();
        } else {
            return "images/joker.svg";
        }
    }

    /**
     * Affiche toutes les cartes d'un tableau
     */
    public static function displayCards(array $cards): void 
    {
        echo "<h3>🃏 État des cartes :</h3>\n";
        foreach ($cards as $index => $card) {
            $status = self::cardToString($card);
            $canFlip = $card->canBeFlipped() ? '✅' : '❌';
            echo "<div>Position $index: $status (Cliquable: $canFlip)</div>\n";
        }
    }

    /**
     * Analyse la distribution des images
     */
    public static function analyzeImageDistribution(array $cards): array 
    {
        $distribution = [];
        $totalCards = count($cards);
        
        foreach ($cards as $card) {
            $image = $card->getImage();
            $distribution[$image] = ($distribution[$image] ?? 0) + 1;
        }

        return [
            'total_cards' => $totalCards,
            'unique_images' => count($distribution),
            'distribution' => $distribution,
            'is_valid_pairs' => self::validatePairs($distribution)
        ];
    }

    /**
     * Valide que chaque image forme une paire valide
     */
    private static function validatePairs(array $distribution): bool 
    {
        foreach ($distribution as $count) {
            if ($count !== 2) {
                return false;
            }
        }
        return true;
    }
}
