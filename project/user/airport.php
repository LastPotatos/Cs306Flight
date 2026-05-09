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
        #map { height: calc(100vh - 48px); }

        .switch {
        padding: 10px;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        gap: 15px;
        }

        .switch a {
            text-decoration: none;
            background: white;
            border: 1px solid #999;
            padding: 4px 10px;
            border-radius: 4px;
            color: black;
            font-family: Arial;
        }

        .switch a:hover {
            background: #eaeaea;
        }
    </style>
</head>
<body>

<div class="switch">
    <a href="home.php">← Back</a>
    <label>
        🟦 Airports arrived at
    </label>
    <label>
        🟥 Airports departed from
    </label>
    <label>
        🟩 Airports both arrived at and departed from
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
var allAirports = [];
arrivals.forEach(a => {
    a.color = "blue";
    allAirports.push(a);
});
departures.forEach(a => {
    let found = false;
    allAirports = allAirports.map(ap => {
        if (ap.codeICAO === a.codeICAO) {
            found = true;
            ap.color = "green";
        }
        return ap;
    });
    if (!found) {
        a.color = "red";
        allAirports.push(a);
    }
});

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
            color: a.color,
            fillColor: a.color,
            fillOpacity: 0.8
        })
        .addTo(layer)
        .bindPopup(popup);
    });
}

showAirports(allAirports);

</script>

</body>
</html>