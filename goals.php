<?php
// Include database connection
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];

// ---- Handle New Goal Submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $goal_type           = $_POST['goal_type'];
    $target_distance_km  = floatval($_POST['target_distance_km']);
    $start_date          = $_POST['start_date'];
    $end_date            = $_POST['end_date'];

    // Validate fields
    if (empty($goal_type) || $target_distance_km <= 0 || 
        empty($start_date) || empty($end_date)) {
        header("Location: goals.php?error=Please fill all fields correctly");
        exit();
    }

    // Check end date is after start date
    if ($end_date <= $start_date) {
        header("Location: goals.php?error=End date must be after start date");
        exit();
    }

    // Save goal to database
    $sql = "INSERT INTO goals 
                (user_id, goal_type, target_distance_km, start_date, end_date)
            VALUES 
                ('$user_id', '$goal_type', '$target_distance_km', 
                 '$start_date', '$end_date')";

    if (mysqli_query($conn, $sql)) {
        header("Location: goals.php?success=Goal set successfully!");
        exit();
    } else {
        header("Location: goals.php?error=Something went wrong, try again");
        exit();
    }
}

// ---- Get All Goals for This User ----
$goals_result = mysqli_query($conn,
    "SELECT * FROM goals 
     WHERE user_id = '$user_id' 
     ORDER BY start_date DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goals - Running Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-brand">🏃 Running Tracker</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="add_run.php">Log Run</a>
            <a href="history.php">History</a>
            <a href="goals.php" class="active">Goals</a>
            <a href="logout.php">Logout 👋</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">

        <!-- Page Header -->
        <div class="page-header">
            <h2>🎯 My Running Goals</h2>
            <p>Set targets and track your progress</p>
        </div>

        <!-- Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Two Column Layout -->
        <div style="display:grid; 
                    grid-template-columns: 1fr 1fr; 
                    gap:28px; 
                    align-items:start;">

            <!-- LEFT: Set New Goal Form -->
            <div class="form-card">
                <h3 style="font-size:18px; 
                           font-weight:600; 
                           margin-bottom:24px; 
                           color:var(--text-white);">
                    ➕ Set New Goal
                </h3>

                <form action="goals.php" method="POST">

                    <div class="form-group">
                        <label for="goal_type">Goal Type</label>
                        <select id="goal_type" name="goal_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="weekly">Weekly Goal</option>
                            <option value="monthly">Monthly Goal</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="target_distance_km">
                            Target Distance (km)
                        </label>
                        <input type="number" 
                               id="target_distance_km" 
                               name="target_distance_km"
                               placeholder="e.g. 30"
                               step="0.5" 
                               min="1" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" 
                               id="start_date" 
                               name="start_date" 
                               required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" 
                               id="end_date" 
                               name="end_date" 
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary" 
                            style="width:100%;">
                        Set Goal 🎯
                    </button>

                </form>
            </div>

            <!-- RIGHT: My Goals List -->
            <div>
                <h3 style="font-size:18px; 
                           font-weight:600; 
                           margin-bottom:24px; 
                           color:var(--text-white);">
                    📋 My Goals
                </h3>

                <?php if (mysqli_num_rows($goals_result) == 0): ?>
                    <div style="text-align:center; 
                                padding:40px; 
                                color:var(--text-muted);
                                background:var(--card-bg);
                                border:1px solid var(--card-border);
                                border-radius:var(--radius-lg);">
                        <p style="font-size:48px;">🎯</p>
                        <p>No goals set yet!</p>
                        <p style="font-size:13px; margin-top:8px;">
                            Set your first goal on the left.
                        </p>
                    </div>

                <?php else: ?>
                    <?php while ($goal = mysqli_fetch_assoc($goals_result)): ?>

                        <?php
                        // Calculate distance covered in this goal period
                        $covered_result = mysqli_query($conn,
                            "SELECT SUM(distance_km) as covered 
                             FROM run_activity 
                             WHERE user_id = '$user_id' 
                             AND run_date BETWEEN 
                                '{$goal['start_date']}' AND '{$goal['end_date']}'");

                        $covered = mysqli_fetch_assoc($covered_result)['covered'] ?? 0;
                        $target  = $goal['target_distance_km'];

                        // Calculate percentage (max 100%)
                        $percent = $target > 0 
                            ? min(100, round(($covered / $target) * 100)) 
                            : 0;

                        // Determine status color
                        $bar_color = $percent >= 100 
                            ? 'var(--secondary)' 
                            : 'var(--primary)';
                        ?>

                        <div class="goal-card">

                            <!-- Goal Header -->
                            <div style="display:flex; 
                                        justify-content:space-between; 
                                        align-items:center;
                                        margin-bottom:12px;">
                                <h4>
                                    <?php echo $goal['goal_type'] === 'weekly' 
                                        ? '📅 Weekly Goal' 
                                        : '🗓️ Monthly Goal'; ?>
                                </h4>
                                <span class="badge <?php echo $percent >= 100 
                                    ? 'badge-green' 
                                    : 'badge-orange'; ?>">
                                    <?php echo $percent >= 100 
                                        ? '✅ Completed!' 
                                        : 'In Progress'; ?>
                                </span>
                            </div>

                            <!-- Date Range -->
                            <p style="font-size:13px; 
                                      color:var(--text-muted); 
                                      margin-bottom:12px;">
                                📆 <?php echo date('M d', 
                                    strtotime($goal['start_date'])); ?> 
                                — 
                                <?php echo date('M d, Y', 
                                    strtotime($goal['end_date'])); ?>
                            </p>

                            <!-- Progress Bar -->
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" 
                                     style="width:<?php echo $percent; ?>%;
                                            background:<?php echo $bar_color; ?>;">
                                </div>
                            </div>

                            <!-- Progress Label -->
                            <div style="display:flex; 
                                        justify-content:space-between;
                                        margin-top:8px;">
                                <span class="progress-label">
                                    <?php echo number_format($covered, 1); ?> km done
                                </span>
                                <span class="progress-label">
                                    Target: <?php echo $target; ?> km 
                                    (<?php echo $percent; ?>%)
                                </span>
                            </div>

                        </div>

                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>