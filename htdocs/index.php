
<?php
$dsn = 'mysql:host=mysql;dbname=game_db;charset=utf8mb4';
$user = 'root';
$password = 'p@ssword';

try {
    $pdo = new PDO($dsn, $user, $password);

    $sql = "
        SELECT
            c.id,
            c.name,
            COUNT(uc.id) AS count
        FROM cards AS c
        JOIN user_cards AS uc
            ON c.id = uc.card_id
        WHERE uc.user_id = 1
        GROUP BY c.id, c.name
        HAVING COUNT(uc.id) >= 2
    ";

    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit('エラー：' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>強化対象アイテム選択</title>
</head>
<body>

<h1>強化対象アイテム選択</h1>

<p>強化のベースとして利用するアイテムを選択してください。</p>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>アイテムID</th>
        <th>アイテム名</th>
        <th>所持数</th>
        <th>選択</th>
    </tr>

    <?php foreach ($items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['id']) ?></td>
            <td><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($item['count']) ?></td>
            <td>
                <a href="material.php?card_id=<?= $item['id'] ?>">
                    強化する
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
