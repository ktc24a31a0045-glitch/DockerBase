
<?php
$dsn = 'mysql:host=mysql;dbname=game_db;charset=utf8mb4';
$user = 'root';
$password = 'p@ssword';

try {
    $pdo = new PDO($dsn, $user, $password);

    $card_id = $_GET['card_id'] ?? null;

    if (!$card_id) {
        exit('アイテムが選択されていません。');
    }

    // アイテム情報を取得
    $sql = "
        SELECT id, name
        FROM cards
        WHERE id = :card_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['card_id' => $card_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        exit('アイテムが見つかりません。');
    }

    // 所持しているアイテムを取得
    $sql = "
        SELECT
            id,
            card_id,
            acquired_at
        FROM user_cards
        WHERE user_id = 1
        AND card_id = :card_id
        ORDER BY id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['card_id' => $card_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit('エラー：' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ベースアイテム選択</title>
</head>
<body>

<h2>
    ベースアイテムインスタンス選択
    (アイテムID：<?= htmlspecialchars($item['id']) ?>)
</h2>

<p>
    強化のベース（土台）となるアイテムインスタンスを1つ選択してください。
</p>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>インスタンスID</th>
        <th>アイテム名</th>
        <th>取得日時</th>
        <th>ベースとして選択</th>
    </tr>

    <?php foreach ($items as $owned_item): ?>
        <tr>
            <td><?= htmlspecialchars($owned_item['id']) ?></td>
            <td><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($owned_item['acquired_at']) ?></td>
            <td>
                <a href="material_select.php?card_id=<?= $card_id ?>&base_id=<?= $owned_item['id'] ?>">
                    ベースに決定
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<p>
    <a href="index.php">強化対象アイテム選択に戻る</a>
</p>

</body>
</html>
