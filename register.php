<?php
// Include database connection
include 'config.php';

// If already logged in go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name             = trim($_POST['name']);
    $email            = trim($_POST['email']);
    $username         = trim($_POST['username']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check empty fields
    if (empty($name) || empty($email) || empty($username) || empty($password)) {
        header("Location: register.php?error=All fields are required");
        exit();
    }

    // Check passwords match
    if ($password !== $confirm_password) {
        header("Location: register.php?error=Passwords do not match");
        exit();
    }

    // Check password length
    if (strlen($password) < 6) {
        header("Location: register.php?error=Password must be at least 6 characters");
        exit();
    }

    // Check username exists
    $check_username = mysqli_query($conn,
        "SELECT user_id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check_username) > 0) {
        header("Location: register.php?error=Username already taken, choose another");
        exit();
    }

    // Check email exists
    $check_email = mysqli_query($conn,
        "SELECT user_id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        header("Location: register.php?error=Email already registered");
        exit();
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Save to database
    $sql = "INSERT INTO users (name, email, username, password_hash)
            VALUES ('$name', '$email', '$username', '$password_hash')";

    if (mysqli_query($conn, $sql)) {
        header("Location: index.php?success=Account created successfully! Please login.");
        exit();
    } else {
        header("Location: register.php?error=Something went wrong, please try again");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Running Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Back to home -->
    <div style="padding: 16px 40px;">
        <a href="index.html"
           style="color: var(--text-muted);
                  font-size: 14px;
                  text-decoration: none;">
            ← Back to Home
        </a>
    </div>

    <div class="auth-container">

        <!-- Logo -->
        <div class="auth-header">
            <h1>🏃 Running Tracker</h1>
            <p>Create your free account</p>
        </div>

        <!-- Error Message -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Success Message -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Register Form -->
        <form action="register.php" method="POST">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text"
                       id="name"
                       name="name"
                       placeholder="e.g. Shishir Kumar Singh"
                       required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email"
                       id="email"
                       name="email"
                       placeholder="e.g. shishir@email.com"
                       required>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text"
                       id="username"
                       name="username"
                       placeholder="e.g. shishir123"
                       required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password"
                       id="password"
                       name="password"
                       placeholder="Minimum 6 characters"
                       required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password"
                       id="confirm_password"
                       name="confirm_password"
                       placeholder="Repeat your password"
                       required>
            </div>

            <button type="submit" class="btn btn-primary"
                    style="width: 100%;">
                Create Account 🚀
            </button>

        </form>

        <!-- Login link -->
        <div class="auth-footer">
            <p>Already have an account?
               <a href="index.php">Login here</a>
            </p>
        </div>

    </div>

</body>
</html>