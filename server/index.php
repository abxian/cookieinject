<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Cookies Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        #background {
            position: fixed;
            width: 100%;
            height: 100%;
            background-color: #000;
            z-index: -1;
        }

        .container-center {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            width: 400px;
        }
    </style>
</head>
<body>
    
    <canvas id="background"></canvas>

    <div class="container-center">
        <div class="form-container">
            <h1 class="text-center mb-4">User Cookies Management</h1>
            
            <div class="mb-3">
                <h2 class="mb-3">Register</h2>
                <form method="post" action="register.php">
                    <input type="text" name="username" placeholder="Username" class="form-control mb-2" required>
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </form>
            </div>

            <hr>

            <div>
                <h2 class="mb-3">Login</h2>
                <form method="post" action="login.php">
                    <input type="text" name="username" placeholder="Username" class="form-control mb-2" required>
                    <input type="text" name="token" placeholder="Token" class="form-control mb-2" required>
                    <button type="submit" class="btn btn-success btn-block">Login</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        
        const canvas = document.getElementById("background");
        const ctx = canvas.getContext("2d");

        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const colors = ["#00bcd4", "#4caf50", "#ff9800", "#9c27b0", "#f44336"];

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 5 + 1;
                this.speedX = Math.random() * 3 - 1.5;
                this.speedY = Math.random() * 3 - 1.5;
                this.color = colors[Math.floor(Math.random() * colors.length)];
            }

            update() {
                this.x += this.speedX;
                this.y += this.speedY;

                if (this.x + this.size > canvas.width || this.x - this.size < 0) {
                    this.speedX = -this.speedX;
                }

                if (this.y + this.size > canvas.height || this.y - this.size < 0) {
                    this.speedY = -this.speedY;
                }
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fillStyle = this.color;
                ctx.fill();
            }
        }

        const particles = [];

        function init() {
            for (let i = 0; i < 100; i++) {
                particles.push(new Particle());
            }
        }

        function animate() {
            requestAnimationFrame(animate);
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });
        }

        init();
        animate();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
