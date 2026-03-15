<?php
// Include database connection
include 'config.php';

// Step 1: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

// Step 2: Check if run_id was passed in URL
if (!isset($_GET['run_id'])) {
    header("Location: history.php?error=Invalid request");
    exit();
}

// Step 3: Get user and run info
$user_id = $_SESSION['user_id'];
$run_id  = intval($_GET['run_id']);

// Step 4: Make sure this run belongs to the logged in user
// (prevents one user deleting another user's run)
$check = mysqli_query($conn,
    "SELECT run_id FROM run_activity 
     WHERE run_id = '$run_id' AND user_id = '$user_id'");

if (mysqli_num_rows($check) == 0) {
    header("Location: history.php?error=Run not found or access denied");
    exit();
}

// Step 5: Delete the run
$sql = "DELETE FROM run_activity WHERE run_id = '$run_id'";

if (mysqli_query($conn, $sql)) {
    header("Location: history.php?success=Run deleted successfully");
    exit();
} else {
    header("Location: history.php?error=Could not delete run, try again");
    exit();
}
?>