<!DOCTYPE html>
<html>
<head>
    <title><?= $page_title ?></title>
</head>
<body>
    <h1>Classement Top 10 du salon </h1>
    
    <table>
        <thead>
            <tr>
                <th>Rang</th>
                <th>Joueur</th>
                <th>Score</th>
                <th>Paires</th>
                <th>Coups</th>
                <th>Temps</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leaderboard as $entry): ?>
                <tr>
                    <td><?= $entry['rank_position'] ?></td>
                    <td><?= htmlspecialchars($entry['username']) ?></td>
                    <td><?= $entry['score'] ?></td>
                    <td><?= $entry['pairs_count'] ?></td>
                    <td><?= $entry['moves_count'] ?></td>
                    <td><?= $entry['time_seconds'] ?>s</td>
                    <td><?= date('d/m/Y', strtotime($entry['achieved_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <a href="?action=menu">Retour au menu</a>
</body>
</html>