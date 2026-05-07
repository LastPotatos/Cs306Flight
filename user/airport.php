<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("mysql:host=127.0.0.1;dbname=flightdiary306;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// LANDED (arrivals)
$stmtArr = $pdo->prepare("
    SELECT DISTINCT
        arr.codeICAO,
        arr.codeIATA,
        arr.pname,
        arr.city,
        arr.country,
        arr.latitude,
        arr.longitude,
        arr.runways
    FROM ticket t
    JOIN flight f ON f.flightNumber = t.flightNumber
                 AND f.scheduledDeparture = t.scheduledDeparture
    JOIN airport arr ON arr.codeICAO = f.arrivedAirport
    JOIN user u ON u.email = t.email
    WHERE u.username = ?
");
$stmtArr->execute([$_SESSION['username']]);
$arrivals = $stmtArr->fetchAll(PDO::FETCH_ASSOC);

// TAKEOFF (departures)
$stmtDep = $pdo->prepare("
    SELECT DISTINCT
        dep.codeICAO,
        dep.codeIATA,
        dep.pname,
        dep.city,
        dep.country,
        dep.latitude,
        dep.longitude,
        dep.runways
    FROM ticket t
    JOIN flight f ON f.flightNumber = t.flightNumber
                 AND f.scheduledDeparture = t.scheduledDeparture
    JOIN airport dep ON dep.codeICAO = f.departedAirport
    JOIN user u ON u.email = t.email
    WHERE u.username = ?
");
$stmtDep->execute([$_SESSION['username']]);
$departures = $stmtDep->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Airports</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        body { font-family: Arial; margin: 0; }
        #map { height: 90vh; }

        .switch {
            padding: 10px;
            background: #f5f5f5;
        }

        .switch label {
            margin-right: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="switch">
    <label>
        <input type="radio" name="mode" value="landed" checked>
        🟦 Landed Airports
    </label>

    <label>
        <input type="radio" name="mode" value="takeoff">
        🟥 Takeoff Airports
    </label>
</div>

<div id="map"></div>

<script>
var map = L.map('map').setView([39, 35], 3);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18
}).addTo(map);

// DATA FROM PHP
var arrivals = <?php echo json_encode($arrivals); ?>;
var departures = <?php echo json_encode($departures); ?>;

let layer = L.layerGroup().addTo(map);

// render function
function showAirports(data, color) {
    layer.clearLayers();

    data.forEach(a => {
        if (!a.latitude || !a.longitude) return;

        let popup = `
            <b>${a.pname}</b><br>
            ${a.city}, ${a.country}<br><br>
            ICAO: ${a.codeICAO}<br>
            IATA: ${a.codeIATA}<br>
            Runways: ${a.runways}
        `;

        L.circleMarker([a.latitude, a.longitude], {
            radius: 6,
            color: color,
            fillColor: color,
            fillOpacity: 0.8
        })
        .addTo(layer)
        .bindPopup(popup);
    });
}

// default = landed
showAirports(arrivals, "blue");

// switch handler
document.querySelectorAll('input[name="mode"]').forEach(el => {
    el.addEventListener('change', function () {
        if (this.value === "landed") {
            showAirports(arrivals, "blue");
        } else {
            showAirports(departures, "red");
        }
    });
});
</script>

</body>
</html>