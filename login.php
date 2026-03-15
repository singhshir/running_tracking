<?php

// Include database connection
include 'config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Step 1: Get data from the form
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Step 2: Check if fields are empty
    if (empty($username) || empty($password)) {
        header("Location: index.php?error=Please enter username and password");
        exit();
    }

    // Step 3: Search for user in database
    $sql    = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    $user   = mysqli_fetch_assoc($result);

    // Step 4: Check if user exists and password is correct
    if ($user && password_verify($password, $user['password_hash'])) {

        // Step 5: Save user info in session (remember who is logged in)
        $_SESSION['user_id']  = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name']     = $user['name'];

        // Step 6: Send to dashboard
        header("Location: dashboard.php");
        exit();

    } else {
        // Wrong username or password
        header("Location: index.php?error=Invalid username or password");
        exit();
    }

} else {
    // If opened directly, go back to login page
    header("Location: index.php");
    exit();
}

?>
