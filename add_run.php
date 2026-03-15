<?php
// Include database connection
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

// Handle form submission (save logic)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id      = $_SESSION['user_id'];
    $run_date     = $_POST['run_date'];
    $distance_km  = floatval($_POST['distance_km']);
    $duration_min = intval($_POST['duration_min']);
    $notes        = trim($_POST['notes']);

    // Validate fields
    if (empty($run_date) || $distance_km <= 0 || $duration_min <= 0) {
        header("Location: add_run.php?error=Please fill all required fields correctly");
        exit();
    }

    // Calculate pace
    $pace_min_km = round($duration_min / $distance_km, 2);

    // Calculate calories
    $calories_burned = round($distance_km * 60);

    // Save to database
    $sql = "INSERT INTO run_activity 
                (user_id, distance_km, duration_min, pace_min_km, calories_burned, run_date, notes)
            VALUES 
                ('$user_id', '$distance_km', '$duration_min', '$pace_min_km', 
                 '$calories_burned', '$run_date', '$notes')";

    if (mysqli_query($conn, $sql)) {
        header("Location: history.php?success=Run logged successfully!");
        exit();
    } else {
        header("Location: add_run.php?error=Something went wrong, please try again");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log a Run - Running Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        #map {
            height:        450px;
            width:         100%;
            border-radius: 12px;
            border:        1px solid var(--card-border);
            z-index:       1;
        }

        .map-status {
            display:       flex;
            align-items:   center;
            gap:           10px;
            padding:       12px 16px;
            background:    var(--dark-3);
            border:        1px solid var(--card-border);
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-size:     14px;
            color:         var(--text-light);
        }

        .status-dot {
            width:         10px;
            height:        10px;
            border-radius: 50%;
            background:    var(--secondary);
            flex-shrink:   0;
            animation:     pulse 1.5s infinite;
        }

        .status-dot.searching { background: #F39C12; }
        .status-dot.error     { background: var(--error); animation: none; }
        .status-dot.success   { background: var(--secondary); animation: none; }

        @keyframes pulse {
            0%,100% { opacity:1;   transform:scale(1);   }
            50%      { opacity:0.5; transform:scale(1.3); }
        }

        .map-steps {
            display:               grid;
            grid-template-columns: repeat(3, 1fr);
            gap:                   12px;
            margin-bottom:         16px;
        }

        .map-step {
            background:    var(--dark-3);
            border:        1px solid var(--card-border);
            border-radius: var(--radius);
            padding:       14px;
            text-align:    center;
            transition:    all 0.3s ease;
        }

        .map-step.active {
            border-color: var(--primary);
            background:   rgba(255,107,53,0.08);
        }

        .map-step.done {
            border-color: var(--secondary);
            background:   rgba(46,204,113,0.08);
        }

        .map-step .step-icon  { font-size: 24px; margin-bottom: 6px; }
        .map-step .step-title {
            font-size:   13px;
            font-weight: 600;
            color:       var(--text-white);
            margin-bottom: 4px;
        }
        .map-step .step-desc {
            font-size: 12px;
            color:     var(--text-muted);
        }

        .map-controls {
            display:       flex;
            gap:           10px;
            margin-top:    16px;
            margin-bottom: 8px;
            flex-wrap:     wrap;
        }

        .btn-map {
            padding:       10px 18px;
            border-radius: var(--radius);
            font-size:     14px;
            font-weight:   600;
            cursor:        pointer;
            border:        none;
            transition:    all 0.3s ease;
        }

        .btn-locate {
            background: var(--grad-green);
            color:      white;
        }

        .btn-locate:hover {
            transform:  translateY(-2px);
            box-shadow: 0 4px 16px rgba(46,204,113,0.3);
        }

        .btn-reset {
            background: rgba(231,76,60,0.15);
            color:      #ff6b6b;
            border:     1px solid rgba(231,76,60,0.3);
        }

        .btn-reset:hover {
            background: rgba(231,76,60,0.25);
        }

        .route-info {
            display:               grid;
            grid-template-columns: repeat(3, 1fr);
            gap:                   12px;
            margin-top:            16px;
        }

        .route-card {
            background:    var(--dark-3);
            border:        1px solid var(--card-border);
            border-radius: var(--radius);
            padding:       16px;
            text-align:    center;
            transition:    all 0.3s ease;
        }

        .route-card.highlight {
            border-color: var(--primary);
            background:   rgba(255,107,53,0.08);
        }

        .route-card .r-label {
            font-size:     12px;
            color:         var(--text-muted);
            margin-bottom: 6px;
        }

        .route-card .r-value {
            font-size:   22px;
            font-weight: 700;
            color:       var(--primary);
        }

        .route-card .r-unit {
            font-size: 12px;
            color:     var(--text-muted);
            margin-top: 2px;
        }

        .section-label {
            font-size:      13px;
            font-weight:    600;
            color:          var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom:  16px;
        }

        .tip-box {
            background:    rgba(46,204,113,0.08);
            border:        1px solid rgba(46,204,113,0.2);
            border-radius: var(--radius);
            padding:       12px 16px;
            font-size:     13px;
            color:         var(--secondary);
            margin-top:    12px;
            text-align:    center;
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-brand">🏃 Running Tracker</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="add_run.php" class="active">Log Run</a>
            <a href="history.php">History</a>
            <a href="goals.php">Goals</a>
            <a href="logout.php">Logout 👋</a>
        </div>
    </nav>

    <div class="main-container">

        <!-- Page Header -->
        <div class="page-header">
            <h2>🏅 Log a New Run</h2>
            <p>Pick your route on the map — distance fills automatically!</p>
        </div>

        <!-- Messages -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <!-- MAP CARD -->
        <div class="form-card" style="max-width:100%; margin-bottom:24px;">

            <p class="section-label">🗺️ Route Map</p>

            <!-- How to use steps -->
            <div class="map-steps">
                <div class="map-step" id="step1">
                    <div class="step-icon">📍</div>
                    <div class="step-title">Step 1</div>
                    <div class="step-desc">Click map to set START point</div>
                </div>
                <div class="map-step" id="step2">
                    <div class="step-icon">🏁</div>
                    <div class="step-title">Step 2</div>
                    <div class="step-desc">Click again to set END point</div>
                </div>
                <div class="map-step" id="step3">
                    <div class="step-icon">📏</div>
                    <div class="step-title">Step 3</div>
                    <div class="step-desc">Distance auto fills below!</div>
                </div>
            </div>

            <!-- Status Bar -->
            <div class="map-status">
                <div class="status-dot searching" id="status-dot"></div>
                <span id="status-text">
                    Detecting your location...
                </span>
            </div>

            <!-- Map -->
            <div id="map"></div>

            <!-- Map Controls -->
            <div class="map-controls">
                <button class="btn-map btn-locate" onclick="locateMe()">
                    📍 Find My Location
                </button>
                <button class="btn-map btn-reset" onclick="resetMap()">
                    🔄 Reset Route
                </button>
            </div>

            <!-- Route Info Cards -->
            <div class="route-info">
                <div class="route-card highlight">
                    <div class="r-label">📏 Route Distance</div>
                    <div class="r-value" id="route-distance">—</div>
                    <div class="r-unit">kilometers</div>
                </div>
                <div class="route-card">
                    <div class="r-label">📍 Start Point</div>
                    <div class="r-value" id="start-coords" 
                         style="font-size:13px; color:var(--secondary);">
                        Not set
                    </div>
                    <div class="r-unit">coordinates</div>
                </div>
                <div class="route-card">
                    <div class="r-label">🏁 End Point</div>
                    <div class="r-value" id="end-coords" 
                         style="font-size:13px; color:var(--primary);">
                        Not set
                    </div>
                    <div class="r-unit">coordinates</div>
                </div>
            </div>

            <div class="tip-box">
                💡 After setting start and end points, the road distance 
                will be calculated automatically and filled into the form below!
            </div>

        </div>

        <!-- FORM CARD -->
        <div class="form-card">

            <p class="section-label">📝 Run Details</p>

            <form action="add_run.php" method="POST">

                <div class="form-row">
                    <div class="form-group">
                        <label for="run_date">Date of Run</label>
                        <input type="date"
                               id="run_date"
                               name="run_date"
                               value="<?php echo date('Y-m-d'); ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label for="distance_km">
                            Distance (km) 
                            <span style="color:var(--secondary); font-size:12px;">
                                ← Auto filled from map
                            </span>
                        </label>
                        <input type="number"
                               id="distance_km"
                               name="distance_km"
                               placeholder="Set route on map above"
                               step="0.01"
                               min="0.01"
                               required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="duration_min">Duration (minutes)</label>
                        <input type="number"
                               id="duration_min"
                               name="duration_min"
                               placeholder="e.g. 30"
                               min="1"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Estimated Pace (auto)</label>
                        <input type="text"
                               id="pace_display"
                               placeholder="Fill duration above"
                               readonly
                               style="background:var(--dark-3);
                                      cursor:not-allowed;
                                      color:var(--primary);">
                    </div>
                </div>

                <!-- Calories Preview -->
                <div style="background:var(--dark-3);
                            border:1px solid var(--card-border);
                            border-radius:var(--radius);
                            padding:14px 16px;
                            margin-bottom:20px;
                            font-size:14px;
                            color:var(--text-muted);">
                    🔥 Estimated Calories:
                    <span id="calories_preview"
                          style="color:var(--primary); font-weight:600;">
                        —
                    </span>
                </div>

                <div class="form-group">
                    <label for="notes">Notes (optional)</label>
                    <textarea id="notes"
                              name="notes"
                              rows="3"
                              placeholder="e.g. Morning run around Ratna Park!">
                    </textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Save Run 🏃
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>

    </div>

    <!-- Leaflet.js -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>

        // =============================================
        // MAP SETUP
        // =============================================

        // Default view: Kathmandu, Nepal
        var map = L.map('map').setView([27.7172, 85.3240], 14);

        // OpenStreetMap tiles (free)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // =============================================
        // VARIABLES
        // =============================================
        var startMarker  = null;
        var endMarker    = null;
        var routeLine    = null;
        var clickCount   = 0;

        // =============================================
        // CUSTOM MARKERS
        // =============================================

        // Green start marker
        var startIcon = L.divIcon({
            className: '',
            html: '<div style="background:#2ECC71; width:18px; height:18px; border-radius:50%; border:3px solid white; box-shadow:0 2px 8px rgba(0,0,0,0.5);"></div>',
            iconSize:   [18, 18],
            iconAnchor: [9, 9]
        });

        // Orange end marker
        var endIcon = L.divIcon({
            className: '',
            html: '<div style="background:#FF6B35; width:18px; height:18px; border-radius:50%; border:3px solid white; box-shadow:0 2px 8px rgba(0,0,0,0.5);"></div>',
            iconSize:   [18, 18],
            iconAnchor: [9, 9]
        });

        // =============================================
        // AUTO DETECT LOCATION ON PAGE LOAD
        // =============================================
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    map.setView([pos.coords.latitude, 
                                 pos.coords.longitude], 15);
                    updateStatus('success', 
                        '✅ Location found! Click on map to set your START point.');
                    setStep(1);
                },
                function() {
                    updateStatus('error',
                        '⚠️ Location access denied. Map shows Kathmandu. Click to set START point.');
                    setStep(1);
                }
            );
        } else {
            updateStatus('error',
                '⚠️ GPS not supported. Click map to set START point.');
            setStep(1);
        }

        // =============================================
        // CLICK ON MAP
        // =============================================
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            if (clickCount === 0) {
                // First click = START point
                if (startMarker) map.removeLayer(startMarker);

                startMarker = L.marker([lat, lng], { icon: startIcon })
                    .addTo(map)
                    .bindPopup('<b>🟢 Start Point</b>')
                    .openPopup();

                document.getElementById('start-coords').textContent =
                    lat.toFixed(4) + ', ' + lng.toFixed(4);

                updateStatus('searching',
                    '🟢 Start set! Now click to set your END point.');
                setStep(2);
                clickCount = 1;

            } else if (clickCount === 1) {
                // Second click = END point
                if (endMarker) map.removeLayer(endMarker);

                endMarker = L.marker([lat, lng], { icon: endIcon })
                    .addTo(map)
                    .bindPopup('<b>🔴 End Point</b>')
                    .openPopup();

                document.getElementById('end-coords').textContent =
                    lat.toFixed(4) + ', ' + lng.toFixed(4);

                updateStatus('searching',
                    '⏳ Calculating road distance...');
                setStep(3);

                // Get road distance from OSRM
                getRouteDistance(
                    startMarker.getLatLng().lat,
                    startMarker.getLatLng().lng,
                    lat,
                    lng
                );

                clickCount = 0; // Reset for next route
            }
        });

        // =============================================
        // GET REAL ROAD DISTANCE FROM OSRM (FREE API)
        // =============================================
        function getRouteDistance(startLat, startLng, endLat, endLng) {

            // OSRM free routing API
            var url = 'https://router.project-osrm.org/route/v1/foot/' +
                      startLng + ',' + startLat + ';' +
                      endLng  + ',' + endLat +
                      '?overview=full&geometries=geojson';

            fetch(url)
                .then(function(response) { return response.json(); })
                .then(function(data) {

                    if (data.code === 'Ok' && data.routes.length > 0) {

                        var route       = data.routes[0];
                        var distanceKm  = (route.distance / 1000).toFixed(2);
                        var durationMin = Math.round(route.duration / 60);

                        // Draw the real road route on map
                        if (routeLine) map.removeLayer(routeLine);

                        routeLine = L.geoJSON(route.geometry, {
                            style: {
                                color:   '#FF6B35',
                                weight:  5,
                                opacity: 0.8
                            }
                        }).addTo(map);

                        // Fit map to show full route
                        map.fitBounds(routeLine.getBounds(), 
                                      { padding: [40, 40] });

                        // Update distance display
                        document.getElementById('route-distance').textContent =
                            distanceKm;

                        // AUTO FILL distance in form!
                        document.getElementById('distance_km').value =
                            distanceKm;

                        // Trigger pace/calorie recalculation
                        calculateStats();

                        updateStatus('success',
                            '✅ Route calculated! Distance: ' + distanceKm + 
                            ' km. Now fill in duration below.');

                    } else {
                        updateStatus('error',
                            '❌ Could not calculate road route. ' +
                            'Please enter distance manually.');
                    }
                })
                .catch(function(error) {
                    updateStatus('error',
                        '❌ Network error. Please enter distance manually.');
                });
        }

        // =============================================
        // FIND MY LOCATION BUTTON
        // =============================================
        function locateMe() {
            if (navigator.geolocation) {
                updateStatus('searching', 'Finding your location...');
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        map.setView([pos.coords.latitude,
                                     pos.coords.longitude], 16);
                        updateStatus('success',
                            '✅ Location found! Click to set START point.');
                    },
                    function() {
                        updateStatus('error',
                            '❌ Could not get location. Allow location access.');
                    }
                );
            }
        }

        // =============================================
        // RESET MAP
        // =============================================
        function resetMap() {
            if (startMarker) map.removeLayer(startMarker);
            if (endMarker)   map.removeLayer(endMarker);
            if (routeLine)   map.removeLayer(routeLine);

            startMarker  = null;
            endMarker    = null;
            routeLine    = null;
            clickCount   = 0;

            document.getElementById('route-distance').textContent = '—';
            document.getElementById('start-coords').textContent   = 'Not set';
            document.getElementById('end-coords').textContent     = 'Not set';
            document.getElementById('distance_km').value          = '';
            document.getElementById('pace_display').value         = '';
            document.getElementById('calories_preview').textContent = '—';

            updateStatus('success',
                '🔄 Map reset! Click to set new START point.');
            setStep(1);
        }

        // =============================================
        // HELPER: UPDATE STATUS BAR
        // =============================================
        function updateStatus(type, message) {
            var dot  = document.getElementById('status-dot');
            var text = document.getElementById('status-text');

            dot.className  = 'status-dot ' + type;
            text.textContent = message;
        }

        // =============================================
        // HELPER: HIGHLIGHT CURRENT STEP
        // =============================================
        function setStep(step) {
            document.getElementById('step1').className = 'map-step';
            document.getElementById('step2').className = 'map-step';
            document.getElementById('step3').className = 'map-step';

            for (var i = 1; i < step; i++) {
                document.getElementById('step' + i).className = 
                    'map-step done';
            }
            if (step <= 3) {
                document.getElementById('step' + step).className = 
                    'map-step active';
            }
        }

        // =============================================
        // PACE AND CALORIES CALCULATOR
        // =============================================
        const distanceInput   = document.getElementById('distance_km');
        const durationInput   = document.getElementById('duration_min');
        const paceDisplay     = document.getElementById('pace_display');
        const caloriesPreview = document.getElementById('calories_preview');

        function calculateStats() {
            const distance = parseFloat(distanceInput.value);
            const duration = parseFloat(durationInput.value);

            // Pace
            if (distance > 0 && duration > 0) {
                const pace        = duration / distance;
                const paceMinutes = Math.floor(pace);
                const paceSeconds = Math.round((pace - paceMinutes) * 60);
                paceDisplay.value = paceMinutes + ':' +
                                   (paceSeconds < 10 ? '0' : '') +
                                    paceSeconds + ' min/km';
            } else {
                paceDisplay.value = '';
            }

            // Calories
            if (distance > 0) {
                caloriesPreview.textContent =
                    Math.round(distance * 60) + ' kcal';
            } else {
                caloriesPreview.textContent = '—';
            }
        }

        distanceInput.addEventListener('input', calculateStats);
        durationInput.addEventListener('input', calculateStats);

    </script>

</body>
</html>