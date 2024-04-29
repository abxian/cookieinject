<?php
require 'config.php';  

header('Content-Type: application/json');

$postData = file_get_contents("php://input");
$data = json_decode($postData, true);
$url = $data['url'];
$encodedCookies = $data['cookies'];
$token = $data['token'];
$domain = parse_url($url, PHP_URL_HOST);  // 提取 URL 的域名部分

// 解码 cookies
$cookies = base64_decode($encodedCookies);

// 使用 PDO 连接数据库
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 检查 token 并找到对应的用户
    $stmt = $pdo->prepare("SELECT id FROM users WHERE token = :token");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user) {
        $userId = $user['id'];
        // 检查是否存在相同域名的 cookie
        $stmt = $pdo->prepare("SELECT id FROM user_cookies WHERE user_id = :user_id AND url LIKE :domain");
        $domainLike = "%$domain%";
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':domain', $domainLike);
        $stmt->execute();
        $existingCookie = $stmt->fetch();

        if ($existingCookie) {
            // 更新现有的 cookie 记录
            $stmt = $pdo->prepare("UPDATE user_cookies SET cookies = :cookies WHERE id = :id");
            $stmt->bindParam(':cookies', $cookies);
            $stmt->bindParam(':id', $existingCookie['id']);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => 'Cookies updated successfully']);
        } else {
            // 插入新的 cookie 记录
            $stmt = $pdo->prepare("INSERT INTO user_cookies (user_id, url, cookies) VALUES (:user_id, :url, :cookies)");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':url', $url);
            $stmt->bindParam(':cookies', $cookies);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => 'New cookies saved successfully']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
