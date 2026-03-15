<?php

// Include config to start session
include 'config.php';

// Step 1: Remove all session data
$_SESSION = array();

// Step 2: Destroy the session completely
session_destroy();

// Step 3: Send user back to login page with message
header("Location: index.php?success=You have been logged out successfully");
exit();

?>
