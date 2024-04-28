<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'config.php';

$message = '';
$cookieId = $_GET['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $url = $_POST['url'];
    $cookies = $_POST['cookies'];
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("UPDATE user_cookies SET url = :url, cookies = :cookies WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':url', $url);
        $stmt->bindParam(':cookies', $cookies);
        $stmt->bindParam(':id', $cookieId);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        // 重定向到管理页面
        header("Location: manage_cookies.php");
        exit;
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
