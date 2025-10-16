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
    <?php endif; ?>
    
    <h1>Memory Game</h1>
    <form method="POST" action="index.php">
        <input type="hidden" name="action" value="new_game">
        <label>Difficulté:</label>
        <select name="difficulty">
            <option value="facile">Facile (3 paires)</option>
            <option value="moyen">Moyen (6 paires)</option>
        </select>
        <button type="submit">Nouvelle partie</button>
    </form>
    
    <a href="?action=leaderboard">Classement</a>
</body>
</html>