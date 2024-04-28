<?php
require 'config.php';
$search = $_GET['search'] ?? '';  // 获取搜索关键词

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM user_cookies WHERE url LIKE ?");
    $stmt->execute(["%$search%"]);
    $cookies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cookies as $cookie) {
        echo "<tr>
                <td>{$cookie['id']}</td>
                <td>{$cookie['url']}</td>
                <td>{$cookie['cookies']}</td>
                <td>
                    <button class='btn btn-danger'>Delete</button>
                    <button class='btn btn-secondary'>Edit</button>
                </td>
              </tr>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
