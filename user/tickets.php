<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$host   = "127.0.0.1";
$dbname = "flightdiary306";
$dbuser = "root";
$dbpass = "";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("
        SELECT
            t.flightNumber,
            t.scheduledDeparture,
            t.seat,
            t.class,
            t.addOns,
            t.price,
            t.currency,
            t.ticketAirlineICAO,
            t.pointsUsed,
            t.pointsReceived,
            t.pointsReceivedXP,
            t.purpose,
            t.comments,
            dep.pname    AS depName,
            dep.city     AS depCity,
            dep.codeIATA AS depIATA,
            arr.pname    AS arrName,
            arr.city     AS arrCity,
            arr.codeIATA AS arrIATA,
            f.scheduledArrival,
            f.actualDeparture,
            f.actualArrival
        FROM ticket t
        JOIN user u      ON u.email             = t.email
        JOIN flight f    ON f.flightNumber       = t.flightNumber
                       AND f.scheduledDeparture  = t.scheduledDeparture
        JOIN airport dep ON dep.codeICAO         = f.departedAirport
        JOIN airport arr ON arr.codeICAO         = f.arrivedAirport
        WHERE u.username = ?
        ORDER BY t.scheduledDeparture DESC
    ");
    $stmt->execute([$_SESSION['username']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tickets = [];
    $error = "Could not load flights: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Flight Diary</title>
    <style>
        body  { font-family: Arial, sans-serif; padding: 24px; background: #f5f7fa; }
        h1    { margin-bottom: 4px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; font-size: 14px; background: #fff; }
        th, td { border: 1px solid #ddd; padding: 9px 12px; text-align: left; }
        th { background: #eef1f5; }
        tr:nth-child(even) { background: #fafbfc; }
        .route { font-weight: bold; }
        .btn-path {
            display: inline-block;
            background: #0057b7; color: #fff;
            border: none; padding: 5px 13px;
            border-radius: 4px; cursor: pointer;
            text-decoration: none; font-size: 13px;
        }
        .btn-path:hover { background: #003f8a; }
        .error { color: red; }
    </style>
</head>
<body>

<h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
<p>You are logged in.<a href="airport.php">View Airports Map</a></p>

<h2>My Flights</h2>

<?php if (!empty($error)): ?>
    <p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php elseif (empty($tickets)): ?>
    <p>No flights logged yet.</p>
<?php else: ?>
<table>
    <tr>
        <th>Flight</th>
        <th>Airline (ICAO)</th>
        <th>Route</th>
        <th>Scheduled Departure</th>
        <th>Scheduled Arrival</th>
        <th>Actual Departure</th>
        <th>Actual Arrival</th>
        <th>Seat</th>
        <th>Class</th>
        <th>Add-ons</th>
        <th>Price</th>
        <th>Points Used</th>
        <th>Points Earned</th>
        <th>XP Earned</th>
        <th>Purpose</th>
        <th>Comments</th>
        <th>Flight Path</th>
    </tr>
    <?php foreach ($tickets as $t): ?>
    <tr>
        <td><?php echo htmlspecialchars($t['flightNumber']); ?></td>
        <td><?php echo htmlspecialchars($t['ticketAirlineICAO']); ?></td>
        <td class="route">
            <?php echo htmlspecialchars($t['depIATA'] . ' (' . $t['depCity'] . ')'); ?>
            &rarr;
            <?php echo htmlspecialchars($t['arrIATA'] . ' (' . $t['arrCity'] . ')'); ?>
        </td>
        <td><?php echo htmlspecialchars($t['scheduledDeparture']); ?></td>
        <td><?php echo htmlspecialchars($t['scheduledArrival']); ?></td>
        <td><?php echo htmlspecialchars($t['actualDeparture']); ?></td>
        <td><?php echo htmlspecialchars($t['actualArrival']); ?></td>
        <td><?php echo htmlspecialchars($t['seat']); ?></td>
        <td><?php echo htmlspecialchars($t['class']); ?></td>
        <td><?php echo htmlspecialchars($t['addOns']); ?></td>
        <td><?php echo $t['price'] ? htmlspecialchars($t['price'] . ' ' . $t['currency']) : '—'; ?></td>
        <td><?php echo htmlspecialchars($t['pointsUsed'] ?? '—'); ?></td>
        <td><?php echo htmlspecialchars($t['pointsReceived'] ?? '—'); ?></td>
        <td><?php echo htmlspecialchars($t['pointsReceivedXP'] ?? '—'); ?></td>
        <td><?php echo htmlspecialchars($t['purpose']); ?></td>
        <td><?php echo htmlspecialchars($t['comments']); ?></td>
        <td>
            <a class="btn-path"
               href="flight_path.php?flightNumber=<?php echo urlencode($t['flightNumber']); ?>&scheduledDeparture=<?php echo urlencode($t['scheduledDeparture']); ?>">
                View Path
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

</body>
</html>