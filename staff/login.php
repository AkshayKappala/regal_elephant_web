<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - The Regal Elephant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #003e43;
            font-family: 'Arial', sans-serif;
            color: #eadab0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 30px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-logo h1 {
            color: #eadab0;
            font-size: 2rem;
            margin-bottom: 5px;
        }
        .login-logo p {
            color: #d4c3a2;
            font-size: 1rem;
        }
        .form-control {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid #a04b25;
            color: #eadab0;
        }
        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.15);
            border-color: #eadab0;
            color: #eadab0;
            box-shadow: 0 0 0 0.25rem rgba(234, 218, 176, 0.25);
        }
        .btn-login {
            background-color: #a04b25;
            border: none;
            color: #eadab0;
            width: 100%;
            padding: 10px;
            font-weight: bold;
        }
        .btn-login:hover {
            background-color: #b25529;
            color: #f5e7c8;
        }
        .login-error {
            color: #ff9e9e;
            background-color: rgba(220, 53, 69, 0.2);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h1>THE REGAL ELEPHANT</h1>
            <p>Staff Portal</p>
        </div>
        
        <?php if(isset($loginError)): ?>
            <div class="login-error">
                <?php echo $loginError; ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="index.php">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-login">Login</button>
        </form>
    </div>
</body>
</html>