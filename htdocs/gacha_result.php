<?php
$dsn = 'mysql:host=mysql;dbname=game_db;charset=utf8mb4';
$user = 'root';
$password = 'p@ssword';

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ガチャの排出アイテムを取得
    $sql = "
        SELECT
            im.item_id,
            im.item_name,
            im.image_path,
            gim.weight
        FROM gacha_item_master AS gim
        JOIN item_master AS im
            ON gim.item_id = im.item_id
        WHERE gim.gacha_id = 1
    ";

    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new Exception('ガチャのアイテムが登録されていません。');
    }

    // 重みの合計を計算
    $totalWeight = 0;

    foreach ($items as $item) {
        $totalWeight += (int)$item['weight'];
    }

    if ($totalWeight <= 0) {
        throw new Exception('抽選確率が正しく設定されていません。');
    }

    // 10連ガチャの結果を抽選
    $results = [];

    for ($i = 0; $i < 10; $i++) {
        $random = random_int(1, $totalWeight);
        $currentWeight = 0;

        foreach ($items as $item) {
            $currentWeight += (int)$item['weight'];

            if ($random <= $currentWeight) {
                $results[] = $item;
                break;
            }
        }
    }

    // 履歴を保存
    $pdo->beginTransaction();

    try {
        // 最初の1件を仮のhistory_id=0で登録
        $insertSql = "
            INSERT INTO gacha_history (history_id, gacha_id, item_id)
            VALUES (0, 1, :item_id)
        ";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            ':item_id' => $results[0]['item_id']
        ]);

        // 最初の履歴IDを取得
        $historyId = (int)$pdo->lastInsertId();

        // 1件目のhistory_idを更新
        $updateSql = "
            UPDATE gacha_history
            SET history_id = :history_id
            WHERE id = :id
        ";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':history_id' => $historyId,
            ':id' => $historyId
        ]);

        // 残り9件を保存
        $insertSql = "
            INSERT INTO gacha_history (history_id, gacha_id, item_id)
            VALUES (:history_id, 1, :item_id)
        ";
        $insertStmt = $pdo->prepare($insertSql);

        for ($i = 1; $i < 10; $i++) {
            $insertStmt->execute([
                ':history_id' => $historyId,
                ':item_id' => $results[$i]['item_id']
            ]);
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    die('エラーが発生しました：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ガチャ結果</title>
</head>
<body>
    <h1>10連ガチャ結果</h1>

    <p>履歴ID：<?= htmlspecialchars((string)$historyId, ENT_QUOTES, 'UTF-8') ?></p>

    <h2>獲得アイテム</h2>

    <style>
    .result-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        max-width: 500px;
        padding: 10px;
        border: 2px solid red;
    }

    .result-item {
        text-align: center;
    }

    .result-item img {
        width: 120px;
        height: 120px;
        object-fit: contain;
    }
</style>

<div class="result-list">
    <?php foreach ($results as $item): ?>
        <div class="result-item">
            <p>
                <?= htmlspecialchars($item['item_id'], ENT_QUOTES, 'UTF-8') ?>
                ：
                <?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?>
            </p>

            <img
                src="<?= htmlspecialchars($item['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                alt="<?= htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8') ?>"
            >
        </div>
    <?php endforeach; ?>
</div>

    <a href="gacha_form.html">もう一度ガチャを回す</a>
</body>
</html>