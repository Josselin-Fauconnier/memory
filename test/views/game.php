<!DOCTYPE html>
<html>
<head>
    <title><?= $page_title ?></title>
</head>
<body>
    <?php if ($flash_message): ?>
        <div class="message <?= $flash_message['type'] ?>">
            <?= htmlspecialchars($flash_message['text']) ?>
        </div>
        
        <?php if (isset($flash_message['hide_cards'])): ?>
            <form method="POST" action="index.php" style="display:inline;">
                <input type="hidden" name="action" value="hide_cards">
                <button type="submit">Continuer</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <div class="game-info">
        <p>Coups: <?= $game_state['moves'] ?></p>
        <p>Paires: <?= $game_state['found_pairs'] ?>/<?= $game_state['total_pairs'] ?></p>
        <p>Temps: <?= $game_state['elapsed_time'] ?>s</p>
        <p>Score: <?= $game_state['score'] ?></p>
    </div>

    <div class="card-grid">
        <?php foreach ($cards as $card): ?>
            <?= $card->renderClickableForm() ?>
        <?php endforeach; ?>
    </div>

    <?php if ($is_completed): ?>
        <div class="game-completed">
            <h2>Partie terminée !</h2>
            <p>Score final: <?= $final_stats['score'] ?></p>
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="restart">
                <button type="submit">Rejouer</button>
            </form>
            <a href="?action=menu">Menu</a>
        </div>
    <?php endif; ?>
</body>
</html>