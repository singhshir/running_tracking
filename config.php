<?php

// Database connection settings
$host     = "localhost";      // XAMPP always uses localhost
$dbname   = "running_tracker"; // The database we just created
$username = "root";            // Default XAMPP username
$password = "";                // Default XAMPP password is empty

// Connect to the database
$conn = mysqli_connect($host, $username, $password, $dbname);

// Check if connection worked
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Start the session (needed for login system)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>