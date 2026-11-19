
<?php
$dsn = 'mysql:host=mysql;dbname=game_db;charset=utf8mb4';
$user = 'root';
$password = 'p@ssword';

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $card_id = $_GET['card_id'] ?? null;
    $target_id = $_GET['target_id'] ?? null;
    $material_id = $_GET['material_id'] ?? null;

    if (!$card_id || !$target_id || !$material_id) {
        exit('アイテム情報が不足しています。');
    }

    // 今回の強化設定
    $new_card_id = 1;

    $pdo->beginTransaction();

    // ベースアイテムを取得
    $sql = "
        SELECT uc.id, uc.card_id, c.name
        FROM user_cards AS uc
        JOIN cards AS c ON uc.card_id = c.id
        WHERE uc.id = :target_id
        AND uc.user_id = 1
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['target_id' => $target_id]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    // 素材アイテムを取得
    $sql = "
        SELECT uc.id, uc.card_id, c.name
        FROM user_cards AS uc
        JOIN cards AS c ON uc.card_id = c.id
        WHERE uc.id = :material_id
        AND uc.user_id = 1
        FOR UPDATE
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['material_id' => $material_id]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    // 選択内容を確認
    if (
        !$target ||
        !$material ||
        $target['card_id'] != $card_id ||
        $material['card_id'] != $card_id ||
        $target['id'] == $material['id']
    ) {
        throw new Exception('アイテムの選択が正しくありません。');
    }

    // 新しいアイテム情報を取得
    $sql = "SELECT id, name FROM cards WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $new_card_id]);
    $new_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$new_item) {
        throw new Exception('強化後のアイテムが見つかりません。');
    }

    // ベースアイテムを新しいアイテムに変更
    $sql = "
        UPDATE user_cards
        SET card_id = :new_card_id
        WHERE id = :target_id
        AND user_id = 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'new_card_id' => $new_card_id,
        'target_id' => $target_id
    ]);

    // 素材アイテムを削除
    $sql = "
        DELETE FROM user_cards
        WHERE id = :material_id
        AND user_id = 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['material_id' => $material_id]);

    $pdo->commit();

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    exit('エラー：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>強化結果</title>
</head>
<body>

<div style="text-align: center;">

    <h2 style="color: green;">🎉 強化成功！ 🎉</h2>

    <p>以下のアイテムを消費しました：</p>

    <p>
        ベース：
        <strong>
            <?= htmlspecialchars($target['name'], ENT_QUOTES, 'UTF-8') ?>
        </strong>
        （インスタンスID：<?= htmlspecialchars($target['id']) ?>）
    </p>

    <p>
        素材：
        <strong>
            <?= htmlspecialchars($material['name'], ENT_QUOTES, 'UTF-8') ?>
        </strong>
        （インスタンスID：<?= htmlspecialchars($material['id']) ?>）
    </p>

    <hr>

    <p>新しいアイテムを獲得しました：</p>

    <h2 style="color: navy;">
        <?= htmlspecialchars($new_item['name'], ENT_QUOTES, 'UTF-8') ?>
    </h2>

    <p style="color: red;">
        （新しいアイテムID：<?= htmlspecialchars($new_item['id']) ?>）
    </p>

    <br>

    <a href="index.php">続けて強化を行う</a>

</div>

</body>
</html>
