<?php
// Include database connection
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

// Get logged in user info
$user_id = $_SESSION['user_id'];

// Show success or error message
$message = '';
if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success">' . 
                htmlspecialchars($_GET['success']) . '</div>';
}
if (isset($_GET['error'])) {
    $message = '<div class="alert alert-error">' . 
                htmlspecialchars($_GET['error']) . '</div>';
}

// Get ALL runs for this user
$runs_result = mysqli_query($conn,
    "SELECT * FROM run_activity 
     WHERE user_id = '$user_id' 
     ORDER BY run_date DESC");

$total_runs = mysqli_num_rows($runs_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Run History - Running Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-brand">🏃 Running Tracker</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="add_run.php">Log Run</a>
            <a href="history.php" class="active">History</a>
            <a href="goals.php">Goals</a>
            <a href="logout.php">Logout 👋</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">

        <!-- Page Header -->
        <div class="page-header">
            <h2>📋 Run History</h2>
            <p>All your recorded running activities</p>
        </div>

        <!-- Messages -->
        <?php echo $message; ?>

        <!-- Log New Run Button -->
        <div style="margin-bottom: 24px;">
            <a href="add_run.php" class="btn btn-primary">
                + Log New Run
            </a>
        </div>

        <!-- Runs Table -->
        <?php if ($total_runs == 0): ?>
            <div style="text-align:center; padding:60px; color:var(--text-muted);">
                <p style="font-size:64px;">🏃</p>
                <p style="font-size:20px; margin-bottom:8px;">
                    No runs logged yet!
                </p>
                <p style="margin-bottom:24px;">
                    Start tracking your running journey today.
                </p>
                <a href="add_run.php" class="btn btn-primary">
                    Log Your First Run
                </a>
            </div>

        <?php else: ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Distance</th>
                            <th>Duration</th>
                            <th>Pace</th>
                            <th>Calories</th>
                            <th>Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        while ($run = mysqli_fetch_assoc($runs_result)): 
                        ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>
                                <?php echo date('M d, Y', 
                                    strtotime($run['run_date'])); ?>
                            </td>
                            <td>
                                <span class="badge badge-orange">
                                    <?php echo $run['distance_km']; ?> km
                                </span>
                            </td>
                            <td><?php echo $run['duration_min']; ?> min</td>
                            <td>
                                <span class="badge badge-green">
                                    <?php echo $run['pace_min_km']; ?> min/km
                                </span>
                            </td>
                            <td>🔥 <?php echo $run['calories_burned']; ?></td>
                            <td>
                                <?php echo $run['notes'] 
                                    ? htmlspecialchars($run['notes']) 
                                    : '<span style="color:var(--text-muted)">—</span>'; 
                                ?>
                            </td>
                            <td>
                                <a href="delete_run.php?run_id=<?php echo $run['run_id']; ?>"
                                   class="btn btn-danger"
                                   style="padding:6px 14px; font-size:13px;"
                                   onclick="return confirm('Are you sure you want to delete this run?')">
                                    🗑️ Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Row -->
            <div style="margin-top:20px; 
                        text-align:right; 
                        color:var(--text-muted); 
                        font-size:14px;">
                Total <?php echo $total_runs; ?> run(s) recorded
            </div>

        <?php endif; ?>

    </div>

</body>
</html>