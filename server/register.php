<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 检查用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "Registration failed: Username already exists.";
        } else {
            $token = bin2hex(random_bytes(16));  // 生成一个随机 token
            $stmt = $pdo->prepare("INSERT INTO users (username, token) VALUES (:username, :token)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':token', $token);
            $stmt->execute();

            // 设置用户 session
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            $_SESSION['token'] = $token;

            
            header("Location: manage_cookies.php");
            exit;
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    echo "Invalid request method.";
}
?>
