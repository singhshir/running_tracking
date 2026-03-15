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
$name    = $_SESSION['name'];

// ---- Get Total Distance ----
$total_distance_result = mysqli_query($conn, 
    "SELECT SUM(distance_km) as total FROM run_activity WHERE user_id = '$user_id'");
$total_distance = mysqli_fetch_assoc($total_distance_result)['total'] ?? 0;

// ---- Get Total Runs ----
$total_runs_result = mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM run_activity WHERE user_id = '$user_id'");
$total_runs = mysqli_fetch_assoc($total_runs_result)['total'] ?? 0;

// ---- Get Total Duration ----
$total_duration_result = mysqli_query($conn, 
    "SELECT SUM(duration_min) as total FROM run_activity WHERE user_id = '$user_id'");
$total_duration = mysqli_fetch_assoc($total_duration_result)['total'] ?? 0;

// ---- Get Total Calories ----
$total_calories_result = mysqli_query($conn, 
    "SELECT SUM(calories_burned) as total FROM run_activity WHERE user_id = '$user_id'");
$total_calories = mysqli_fetch_assoc($total_calories_result)['total'] ?? 0;

// ---- Get Personal Best (longest run) ----
$best_run_result = mysqli_query($conn, 
    "SELECT * FROM run_activity WHERE user_id = '$user_id' 
     ORDER BY distance_km DESC LIMIT 1");
$best_run = mysqli_fetch_assoc($best_run_result);

// ---- Get Last 7 Runs for Chart ----
$chart_result = mysqli_query($conn, 
    "SELECT run_date, distance_km FROM run_activity 
     WHERE user_id = '$user_id' 
     ORDER BY run_date DESC LIMIT 7");

$chart_dates     = [];
$chart_distances = [];

while ($row = mysqli_fetch_assoc($chart_result)) {
    $chart_dates[]     = $row['run_date'];
    $chart_distances[] = $row['distance_km'];
}

// Reverse so oldest is first on chart
$chart_dates     = array_reverse($chart_dates);
$chart_distances = array_reverse($chart_distances);

// ---- Get Recent 5 Runs ----
$recent_runs_result = mysqli_query($conn, 
    "SELECT * FROM run_activity WHERE user_id = '$user_id' 
     ORDER BY run_date DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Running Tracker</title>
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js library for charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-brand">🏃 Running Tracker</div>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="add_run.php">Log Run</a>
            <a href="history.php">History</a>
            <a href="goals.php">Goals</a>
            <a href="logout.php">Logout 👋</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">

        <!-- Welcome Header -->
        <div class="page-header">
            <h2>👋 Welcome back, <?php echo htmlspecialchars($name); ?>!</h2>
            <p>Here is your running summary</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">

            <div class="stats-card">
                <span class="stats-icon">📏</span>
                <span class="stats-value">
                    <?php echo number_format($total_distance, 1); ?> km
                </span>
                <span class="stats-label">Total Distance</span>
            </div>

            <div class="stats-card">
                <span class="stats-icon">🏃</span>
                <span class="stats-value">
                    <?php echo $total_runs; ?>
                </span>
                <span class="stats-label">Total Runs</span>
            </div>

            <div class="stats-card">
                <span class="stats-icon">⏱️</span>
                <span class="stats-value">
                    <?php echo number_format($total_duration); ?> min
                </span>
                <span class="stats-label">Total Time</span>
            </div>

            <div class="stats-card">
                <span class="stats-icon">🔥</span>
                <span class="stats-value">
                    <?php echo number_format($total_calories); ?>
                </span>
                <span class="stats-label">Calories Burned</span>
            </div>

        </div>

        <!-- Personal Best Card -->
        <?php if ($best_run): ?>
        <div class="best-card">
            <span class="trophy">🏆</span>
            <div>
                <h3>Personal Best Run</h3>
                <p>
                    <?php echo $best_run['distance_km']; ?> km 
                    in <?php echo $best_run['duration_min']; ?> minutes 
                    at pace <?php echo $best_run['pace_min_km']; ?> min/km 
                    on <?php echo date('M d, Y', strtotime($best_run['run_date'])); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Progress Chart -->
        <?php if (count($chart_dates) > 0): ?>
        <div class="chart-section">
            <h3>📊 Your Last <?php echo count($chart_dates); ?> Runs</h3>
            <canvas id="runChart" height="100"></canvas>
        </div>
        <?php endif; ?>

        <!-- Recent Runs Table -->
        <div class="chart-section">
            <h3>🕐 Recent Runs</h3>

            <?php if ($total_runs == 0): ?>
                <div style="text-align:center; padding: 40px; color: var(--text-muted);">
                    <p style="font-size:48px;">🏃</p>
                    <p>No runs logged yet!</p>
                    <a href="add_run.php" class="btn btn-primary" 
                       style="margin-top:16px; display:inline-block;">
                        Log Your First Run
                    </a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Distance</th>
                                <th>Duration</th>
                                <th>Pace</th>
                                <th>Calories</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($run = mysqli_fetch_assoc($recent_runs_result)): ?>
                            <tr>
                                <td><?php echo date('M d, Y', 
                                    strtotime($run['run_date'])); ?></td>
                                <td>
                                    <span class="badge badge-orange">
                                        <?php echo $run['distance_km']; ?> km
                                    </span>
                                </td>
                                <td><?php echo $run['duration_min']; ?> min</td>
                                <td><?php echo $run['pace_min_km']; ?> min/km</td>
                                <td>🔥 <?php echo $run['calories_burned']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <div style="text-align:right; margin-top:16px;">
                    <a href="history.php" class="btn btn-secondary">
                        View All Runs →
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Chart.js Script -->
    <?php if (count($chart_dates) > 0): ?>
    <script>
        const ctx = document.getElementById('runChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_dates); ?>,
                datasets: [{
                    label: 'Distance (km)',
                    data: <?php echo json_encode($chart_distances); ?>,
                    backgroundColor: 'rgba(255, 107, 53, 0.7)',
                    borderColor:     'rgba(255, 107, 53, 1)',
                    borderWidth:     2,
                    borderRadius:    8,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: '#B8C4D0' }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#B8C4D0' },
                        grid:  { color: 'rgba(255,255,255,0.05)' }
                    },
                    y: {
                        ticks: { color: '#B8C4D0' },
                        grid:  { color: 'rgba(255,255,255,0.05)' },
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>