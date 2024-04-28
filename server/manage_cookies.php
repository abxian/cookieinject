<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'config.php';
$message = '';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_token'], $_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "CSRF token mismatch.";
    } else {
        $newToken = $_POST['new_token']; 

        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE token = :newToken");
            $stmt->bindParam(':newToken', $newToken);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $message = "Token update failed: Token already in use.";
            } else {
            
                $stmt = $pdo->prepare("UPDATE users SET token = :newToken WHERE id = :user_id");
                $stmt->bindParam(':newToken', $newToken);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->execute();

                $_SESSION['token'] = $newToken; 
                $message = "Token updated successfully!";
            }
        } catch (PDOException $e) {
            $message = "Error updating token: " . $e->getMessage();
        }
    }
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT * FROM user_cookies WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $cookies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Cookies</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1>Cookie Management Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
    <p>Current token: <?php echo htmlspecialchars($_SESSION['token']); ?></p>
    <p><?php echo $message; ?></p>
    
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="text" name="new_token" required placeholder="Enter new token" class="form-control mb-3">
        <button type="submit" class="btn btn-primary">Update Token</button>
    </form>

    <form class="d-flex mb-3" method="get">
        <input class="form-control me-2" type="search" name="search" placeholder="Search by URL" aria-label="Search">
        <button class="btn btn-outline-success" type="submit">Search</button>
    </form>
    
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">URL</th>
                <th scope="col">Cookies</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cookies as $cookie): ?>
            <tr>
                <td><?php echo htmlspecialchars($cookie['id']); ?></td>
                <td><?php echo htmlspecialchars($cookie['url']); ?></td>
                <td>
                    <div style="position: relative;">
                        <span style="overflow: hidden; display: inline-block; max-width: 300px; text-overflow: ellipsis;"><?php echo htmlspecialchars($cookie['cookies']); ?></span>
                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($cookie['cookies']); ?>')" class="btn btn-sm btn-secondary" style="position: absolute; top: 0; right: 0;">Copy</button>
                    </div>
                </td>
                <td>
                    <!-- <a href="edit_cookie.php?id=<?php echo $cookie['id']; ?>" class="btn btn-sm btn-primary">Edit</a> -->
                    <a href="delete_cookie.php?id=<?php echo $cookie['id']; ?>" class="btn btn-sm btn-danger">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text)
            .then(() => {
                alert("Copied to clipboard!");
            })
            .catch((error) => {
                console.error("Unable to copy to clipboard:", error);
            });
    }
</script>
</body>
</html>
