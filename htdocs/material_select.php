
<?php
$dsn = 'mysql:host=mysql;dbname=game_db;charset=utf8mb4';
$user = 'root';
$password = 'p@ssword';

try {
    $pdo = new PDO($dsn, $user, $password);

    $card_id = $_GET['card_id'] ?? null;
    $base_id = $_GET['base_id'] ?? null;

    if (!$card_id || !$base_id) {
        exit('アイテム情報が不足しています。');
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

    // ベースアイテムを確認
    $sql = "
        SELECT id
        FROM user_cards
        WHERE id = :base_id
        AND user_id = 1
        AND card_id = :card_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'base_id' => $base_id,
        'card_id' => $card_id
    ]);

    $base = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$base) {
        exit('ベースアイテムが正しくありません。');
    }

    // ベース以外のアイテムを素材候補として取得
    $sql = "
        SELECT id, acquired_at
        FROM user_cards
        WHERE user_id = 1
        AND card_id = :card_id
        AND id != :base_id
        ORDER BY id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'card_id' => $card_id,
        'base_id' => $base_id
    ]);

    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit('エラー：' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>素材アイテム選択</title>
</head>
<body>

<h2>
    素材アイテムインスタンス選択
    (アイテムID：<?= htmlspecialchars($card_id) ?>)
</h2>

<p>
    ベースアイテムインスタンスID：
    <?= htmlspecialchars($base_id) ?>
</p>

<p>
    強化の素材として利用するアイテムインスタンスを1つ選択してください。
</p>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>インスタンスID</th>
        <th>アイテム名（素材ID：<?= htmlspecialchars($card_id) ?>）</th>
        <th>取得日時</th>
        <th>選択</th>
    </tr>

    <?php foreach ($materials as $material): ?>
        <tr>
            <td><?= htmlspecialchars($material['id']) ?></td>
            <td><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($material['acquired_at']) ?></td>
            <td>
                <a href="result.php?card_id=<?= $card_id ?>&target_id=<?= $base_id ?>&material_id=<?= $material['id'] ?>">
                    素材に決定し強化開始
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<p>
    <a href="index.php">最初に戻る</a>
</p>

</body>
</html>
