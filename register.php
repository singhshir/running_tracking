<?php

// Include database connection
include 'config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Step 1: Get the data from the form
    $name             = trim($_POST['name']);
    $email            = trim($_POST['email']);
    $username         = trim($_POST['username']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Step 2: Check if any field is empty
    if (empty($name) || empty($email) || empty($username) || empty($password)) {
        header("Location: register.html?error=All fields are required");
        exit();
    }

    // Step 3: Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: register.html?error=Passwords do not match");
        exit();
    }

    // Step 4: Check password length
    if (strlen($password) < 6) {
        header("Location: register.html?error=Password must be at least 6 characters");
        exit();
    }

    // Step 5: Check if username already exists
    $check_username = mysqli_query($conn, "SELECT user_id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check_username) > 0) {
        header("Location: register.html?error=Username already taken, choose another");
        exit();
    }

    // Step 6: Check if email already exists
    $check_email = mysqli_query($conn, "SELECT user_id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        header("Location: register.html?error=Email already registered");
        exit();
    }

    // Step 7: Hash the password for security
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Step 8: Save user to the database
    $sql = "INSERT INTO users (name, email, username, password_hash) 
            VALUES ('$name', '$email', '$username', '$password_hash')";

    if (mysqli_query($conn, $sql)) {
        // Success! Send to login page with success message
        header("Location: index.php?success=Account created successfully! Please login.");
        exit();
    } else {
        // Something went wrong with database
        header("Location: register.html?error=Something went wrong, please try again");
        exit();
    }

} else {
    // If someone tries to open register.php directly, send them to register page
    header("Location: register.html");
    exit();
}

?>