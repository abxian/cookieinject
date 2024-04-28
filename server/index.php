<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Cookies Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.5/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">User Cookies Management</h1>
        
        <div class="row">
            <div class="col-md-6">
                <h2>Register</h2>
                <form method="post" action="register.php">
                    <input type="text" name="username" placeholder="Username" class="form-control mb-2" required>
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
            </div>
            <div class="col-md-6">
                <h2>Login</h2>
                <form method="post" action="login.php">
                    <input type="text" name="username" placeholder="Username" class="form-control mb-2" required>
                    <input type="text" name="token" placeholder="Token" class="form-control mb-2" required>
                    <button type="submit" class="btn btn-success">Login</button>
                </form>
            </div>
        </div>

    
       
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
