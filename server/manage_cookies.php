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


$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10; //分页行数
$offset = ($page - 1) * $limit;

$search = isset($_POST['search']) ? $_POST['search'] : '';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!empty($search)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_cookies WHERE user_id = :user_id AND url LIKE :search");
        $stmt->bindValue(':user_id', $_SESSION['user_id']);
        $stmt->bindValue(':search', "%$search%");
        $stmt->execute();
        $total_records = $stmt->fetchColumn();
        $total_pages = ceil($total_records / $limit);

        $stmt = $pdo->prepare("SELECT * FROM user_cookies WHERE user_id = :user_id AND url LIKE :search LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':user_id', $_SESSION['user_id']);
        $stmt->bindValue(':search', "%$search%");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $cookies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_cookies WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $total_records = $stmt->fetchColumn();
        $total_pages = ceil($total_records / $limit);

        $stmt = $pdo->prepare("SELECT * FROM user_cookies WHERE user_id = :user_id LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $cookies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
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
    <style>
        body {
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #000;
            color: #fff; 
        }

        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            background-color: #000;
            z-index: -1;
        }

        
        table, th, td {
            color: #fff;
        }

        
        .cookie-cell {
            position: relative;
        }

        .cookie-text {
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px; /* 设置最大宽度 */
            white-space: nowrap; /* 防止自动换行 */
        }

        
        .btn-copy {
            position: absolute;
            top: 0;
            right: 0;
        }
    </style>
</head>
<body>
<div id="particles-js"></div>
<div class="container mt-5">
    <h1 class="text-center">Cookie Management Dashboard</h1>
    <p class="text-center">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
    <p class="text-center">Current token: <?php echo htmlspecialchars($_SESSION['token']); ?></p>
    <p class="text-center"><?php echo $message; ?></p>
    
    <form class="text-center" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="text" name="new_token" required placeholder="Enter new token" class="form-control mb-3 mx-auto" style="max-width: 300px;">
        <button type="submit" class="btn btn-primary">Update Token</button>
    </form>

    <form class="d-flex justify-content-center mb-3" method="post">
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
                    <?php
                        $url = parse_url($cookie['url']);
                        $host = isset($url['host']) ? $url['host'] : '';
                        $port = isset($url['port']) ? ':' . $url['port'] : '';
                        $url_display = $host . $port;
                    ?>
                    <td><?php echo htmlspecialchars($url_display); ?></td>
                    <td class="cookie-cell">
                        <span class="cookie-text"><?php echo htmlspecialchars(substr($cookie['cookies'], 0, 50)); ?>...</span> 
                        <button onclick="copyToClipboard('<?php echo htmlspecialchars($cookie['cookies']); ?>')" class="btn btn-sm btn-secondary btn-copy">Copy</button>
                    </td>
                    <td>
                       
                        <a href="delete_cookie.php?id=<?php echo $cookie['id']; ?>" class="btn btn-sm btn-danger">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    particlesJS("particles-js", {
        "particles": {
            "number": {
                "value": 80,
                "density": {
                    "enable": true,
                    "value_area": 800
                }
            },
            "color": {
                "value": "#ffffff"
            },
            "shape": {
                "type": "circle",
                "stroke": {
                    "width": 0,
                    "color": "#000000"
                },
                "polygon": {
                    "nb_sides": 5
                },
                "image": {
                    "src": "img/github.svg",
                    "width": 100,
                    "height": 100
                }
            },
            "opacity": {
                "value": 0.5,
                "random": true,
                "anim": {
                    "enable": false,
                    "speed": 1,
                    "opacity_min": 0.1,
                    "sync": false
                }
            },
            "size": {
                "value": 3,
                "random": true,
                "anim": {
                    "enable": false,
                    "speed": 40,
                    "size_min": 0.1,
                    "sync": false
                }
            },
            "line_linked": {
                "enable": true,
                "distance": 150,
                "color": "#ffffff",
                "opacity": 0.4,
                "width": 1
            },
            "move": {
                "enable": true,
                "speed": 6,
                "direction": "none",
                "random": false,
                "straight": false,
                "out_mode": "out",
                "bounce": false,
                "attract": {
                    "enable": false,
                    "rotateX": 600,
                    "rotateY": 1200
                }
            }
        },
        "interactivity": {
            "detect_on": "canvas",
            "events": {
                "onhover": {
                    "enable": true,
                    "mode": "repulse"
                },
                "onclick": {
                    "enable": true,
                    "mode": "push"
                },
                "resize": true
            },
            "modes": {
                "grab": {
                    "distance": 400,
                    "line_linked": {
                        "opacity": 1
                    }
                },
                "bubble": {
                    "distance": 400,
                    "size": 40,
                    "duration": 2,
                    "opacity": 8,
                    "speed": 3
                },
                "repulse": {
                    "distance": 200,
                    "duration": 0.4
                },
                "push": {
                    "particles_nb": 4
                },
                "remove": {
                    "particles_nb": 2
                }
            }
        },
        "retina_detect": true
    });
    
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
