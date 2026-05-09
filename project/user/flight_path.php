<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Validate inputs
$flightNumber       = isset($_GET['flightNumber'])       ? trim($_GET['flightNumber'])       : '';
$scheduledDeparture = isset($_GET['scheduledDeparture']) ? trim($_GET['scheduledDeparture']) : '';

if (!$flightNumber || !$scheduledDeparture) {
    die("Missing flight information.");
}

$host   = "127.0.0.1";
$dbname = "flightdiary306";
$dbuser = "root";
$dbpass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Make sure this flight belongs to the logged-in user
    $auth = $pdo->prepare("
        SELECT t.flightNumber
        FROM ticket t
        JOIN user u ON u.email = t.email
        WHERE u.username = ?
          AND t.flightNumber = ?
          AND t.scheduledDeparture = ?
        LIMIT 1
    ");
    $auth->execute([$_SESSION['username'], $flightNumber, $scheduledDeparture]);
    if (!$auth->fetch()) {
        die("Flight not found or access denied.");
    }

    // Get flight + airport details
    $flightStmt = $pdo->prepare("
        SELECT
            f.flightNumber,
            f.scheduledDeparture,
            f.scheduledArrival,
            f.actualDeparture,
            f.actualArrival,
            dep.pname    AS depName,
            dep.city     AS depCity,
            dep.codeIATA AS depIATA,
            dep.latitude AS depLat,
            dep.longitude AS depLng,
            arr.pname    AS arrName,
            arr.city     AS arrCity,
            arr.codeIATA AS arrIATA,
            arr.latitude AS arrLat,
            arr.longitude AS arrLng
        FROM flight f
        JOIN airport dep ON dep.codeICAO = f.departedAirport
        JOIN airport arr ON arr.codeICAO = f.arrivedAirport
        WHERE f.flightNumber = ?
          AND f.scheduledDeparture = ?
        LIMIT 1
    ");
    $flightStmt->execute([$flightNumber, $scheduledDeparture]);
    $flight = $flightStmt->fetch(PDO::FETCH_ASSOC);

    // Get path points ordered by time
    $pathStmt = $pdo->prepare("
        SELECT latitude, longitude, altitude, speed, heading, epochTimestamp
        FROM path
        WHERE flightNumber = ?
          AND epochTimestamp >= ?
          AND epochTimestamp <= ?
        ORDER BY epochTimestamp ASC
    ");
    $pathStmt->execute([$flightNumber, $scheduledDeparture, $flight['actualArrival']]);
    $pathPoints = $pathStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Pass path data to JavaScript as JSON
$pathJson = json_encode($pathPoints);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flight Path – <?php echo htmlspecialchars($flightNumber); ?></title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f7fa; }

        header {
            background: #0057b7; color: #fff;
            padding: 16px 24px;
            display: flex; align-items: center; gap: 16px;
        }
        header a { color: #cde; text-decoration: none; font-size: 14px; }
        header a:hover { text-decoration: underline; }
        header h1 { font-size: 20px; }

        .info-bar {
            display: flex; flex-wrap: wrap; gap: 12px;
            padding: 14px 24px; background: #fff;
            border-bottom: 1px solid #ddd; font-size: 14px;
        }
        .info-item { display: flex; flex-direction: column; }
        .info-item span:first-child { font-size: 11px; color: #888; text-transform: uppercase; }
        .info-item span:last-child  { font-weight: bold; }

        #map { width: 100%; height: calc(100vh - 112px); }

        .no-path {
            text-align: center; padding: 60px; font-size: 16px; color: #666;
        }
    </style>
</head>
<body>

<header>
    <a href="tickets.php">&larr; Back to My Flights</a>
    <h1>
        Flight <?php echo htmlspecialchars($flightNumber); ?> &nbsp;&mdash;&nbsp;
        <?php echo htmlspecialchars($flight['depIATA'] . ' (' . $flight['depCity'] . ')'); ?>
        &rarr;
        <?php echo htmlspecialchars($flight['arrIATA'] . ' (' . $flight['arrCity'] . ')'); ?>
    </h1>
</header>

<div class="info-bar">
    <div class="info-item">
        <span>Departure Airport</span>
        <span><?php echo htmlspecialchars($flight['depName']); ?></span>
    </div>
    <div class="info-item">
        <span>Arrival Airport</span>
        <span><?php echo htmlspecialchars($flight['arrName']); ?></span>
    </div>
    <div class="info-item">
        <span>Scheduled Departure</span>
        <span><?php echo htmlspecialchars($flight['scheduledDeparture']); ?></span>
    </div>
    <div class="info-item">
        <span>Scheduled Arrival</span>
        <span><?php echo htmlspecialchars($flight['scheduledArrival']); ?></span>
    </div>
    <div class="info-item">
        <span>Actual Departure</span>
        <span><?php echo htmlspecialchars($flight['actualDeparture']); ?></span>
    </div>
    <div class="info-item">
        <span>Actual Arrival</span>
        <span><?php echo htmlspecialchars($flight['actualArrival']); ?></span>
    </div>
</div>

<?php if (empty($pathPoints)): ?>
    <div class="no-path">No flight path data available for this flight.</div>
<?php else: ?>
    <div id="map"></div>
    <script>
        const pathData = <?php echo $pathJson; ?>;

        const depLat = <?php echo (float)$flight['depLat']; ?>;
        const depLng = <?php echo (float)$flight['depLng']; ?>;
        const arrLat = <?php echo (float)$flight['arrLat']; ?>;
        const arrLng = <?php echo (float)$flight['arrLng']; ?>;

        // Centre map on midpoint of route
        const midLat = (depLat + arrLat) / 2;
        const midLng = (depLng + arrLng) / 2;

        const map = L.map('map').setView([midLat, midLng], 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        const alts = pathData.map(p => parseFloat(p.altitude)).filter(a => !isNaN(a));
        const minAlt = alts.length ? Math.min(...alts) : 0;
        const maxAlt = alts.length ? Math.max(...alts) : minAlt;

        function altitudeToColor(alt) {
            if (isNaN(alt)) return '#888'; // fallback
            const ratio = maxAlt === minAlt ? 0.5 : (alt - minAlt) / (maxAlt - minAlt);
            // hue from 120 (green) → 60 (yellow) → 0 (red)
            const hue = 120 - (ratio * 120);
            return `hsl(${hue}, 90%, 40%)`;
        }

        // Draw the path polyline

        const lineLayer = L.layerGroup().addTo(map);
        for (let i = 0; i < pathData.length - 1; i++) {
            const p1 = pathData[i];
            const p2 = pathData[i + 1];
            const lat1 = parseFloat(p1.latitude), lng1 = parseFloat(p1.longitude);
            const lat2 = parseFloat(p2.latitude), lng2 = parseFloat(p2.longitude);
            if (isNaN(lat1) || isNaN(lng1) || isNaN(lat2) || isNaN(lng2)) continue;
            const alt1 = parseFloat(p1.altitude);
            const alt2 = parseFloat(p2.altitude);
            const avgAlt = (isNaN(alt1) ? 0 : alt1) + (isNaN(alt2) ? 0 : alt2);
            const count = (isNaN(alt1) ? 0 : 1) + (isNaN(alt2) ? 0 : 1);
            const avg = count ? (avgAlt / count) : NaN;
            const color = altitudeToColor(avg);
            L.polyline([[lat1, lng1], [lat2, lng2]], {
                color: color,
                weight: 3,
                opacity: 0.85,
                interactive: false
            }).addTo(lineLayer);
        }

        const latlngs = pathData.map(p => [parseFloat(p.latitude), parseFloat(p.longitude)]);

        map.fitBounds(L.latLngBounds(latlngs), { padding: [40, 40] });

        // Departure marker (green)
        L.circleMarker([depLat, depLng], {
            radius: 8, color: 'red', fillColor: 'red', fillOpacity: 0.6
        })
        .addTo(map)
        .bindPopup(`<b>Departure</b><br><?php echo htmlspecialchars($flight['depName']); ?> (<?php echo htmlspecialchars($flight['depIATA']); ?>)<br><?php echo htmlspecialchars($flight['scheduledDeparture']); ?>`);

        // Arrival marker (red)
        L.circleMarker([arrLat, arrLng], {
            radius: 8, color: 'blue', fillColor: 'blue', fillOpacity: 0.6
        })
        .addTo(map)
        .bindPopup(`<b>Arrival</b><br><?php echo htmlspecialchars($flight['arrName']); ?> (<?php echo htmlspecialchars($flight['arrIATA']); ?>)<br><?php echo htmlspecialchars($flight['scheduledArrival']); ?>`);

        // Clickable path points showing altitude, speed, heading
        pathData.forEach(p => {
            const alt = parseFloat(p.altitude);
            const color = altitudeToColor(alt);
            L.circleMarker([parseFloat(p.latitude), parseFloat(p.longitude)], {
                radius: 3, color: color, fillColor: '#fff', fillOpacity: 0.8, weight: 1.5
            })
            .addTo(map)
            .bindPopup(
                `<b>${p.epochTimestamp}</b><br>` +
                `Altitude: ${p.altitude} ft<br>` +
                `Speed: ${p.speed} kts<br>` +
                `Heading: ${p.heading}°`
            );
        });
    </script>
<?php endif; ?>

</body>
</html>