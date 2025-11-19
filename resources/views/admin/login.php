<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="admin-login">
        <div class="login-card">
            <div class="login-header">
                <h2><?php echo APP_NAME; ?> Admin</h2>
                <p>Login to continue</p>
            </div>

            <?php
            $errorMsg = session('error');
            if ($errorMsg):
            ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($errorMsg); ?></div>
            <?php endif; ?>

            <form method="POST" action="/admin/login" class="login-form">
                <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username or Email</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="login-footer" style="margin-top: 20px; text-align: center; color: #7f8c8d;">
                <p style="font-size: 12px;">Default: admin / password</p>
                <a href="/shop" style="color: #3498db;">← Back to Website</a>
            </div>
        </div>
    </div>
</body>
</html>
