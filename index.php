<?php
// Include database connection
include 'config.php';

// If user is already logged in, send them to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Running Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="auth-container">

        <!-- Logo / Title -->
        <div class="auth-header">
            <h1>🏃 Running Tracker</h1>
            <p>Login to your account</p>
        </div>

        <!-- Show error message if any -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Show success message if any -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="login.php" method="POST">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>

        </form>

        <!-- Link to register -->
        <div class="auth-footer">
            <p>Don't have an account? <a href="register.html">Register here</a></p>
        </div>

    </div>

</body>
</html>