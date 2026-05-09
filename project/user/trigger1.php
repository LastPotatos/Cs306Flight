<?php
session_start();
include '../db.php'; // mysqli connection → $conn

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$result  = null;
$success = null;

// ── Case 1: valid flight times ────────────────────────────────────────────────
if (isset($_POST['case1'])) {

    // Use a clearly temporary flight number that won't clash
    $testFlightNo  = 'TST001';
    $dep           = '2099-12-31 10:00:00';
    $arr           = '2099-12-31 13:00:00'; // arrives AFTER departure → valid

    // Clean up any leftover test row first
    $conn->query("DELETE FROM flight WHERE flightNumber = '$testFlightNo'");

    $stmt = $conn->prepare("
        INSERT INTO flight
            (flightNumber, departedAirport, arrivedAirport,
             scheduledDeparture, scheduledArrival,
             actualDeparture,    actualArrival)
        VALUES (?, 'LTFJ', 'LTFM', ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssss', $testFlightNo, $dep, $arr, $dep, $arr);

    if ($stmt->execute()) {
        $success = true;
        $result  = "✅ Case 1 SUCCESS – Flight <strong>$testFlightNo</strong> inserted correctly.<br>"
                 . "Departure: <code>$dep</code> → Arrival: <code>$arr</code><br>"
                 . "The trigger found no violations and allowed the INSERT.";
        // Clean up the test row
        $conn->query("DELETE FROM flight WHERE flightNumber = '$testFlightNo'");
    } else {
        $success = false;
        $result  = "❌ Unexpected error: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

// ── Case 2: invalid flight times (arrival before departure) ───────────────────
if (isset($_POST['case2'])) {

    $testFlightNo  = 'TST002';
    $dep           = '2099-12-31 13:00:00';
    $arr           = '2099-12-31 10:00:00'; // arrives BEFORE departure → invalid

    $conn->query("DELETE FROM flight WHERE flightNumber = '$testFlightNo'");

    $stmt = $conn->prepare("
        INSERT INTO flight
            (flightNumber, departedAirport, arrivedAirport,
             scheduledDeparture, scheduledArrival,
             actualDeparture,    actualArrival)
        VALUES (?, 'LTFJ', 'LTFM', ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssss', $testFlightNo, $dep, $arr, $dep, $arr);

    if ($stmt->execute()) {
        // Should NOT reach here
        $success = false;
        $result  = "⚠️ Trigger did NOT fire – the invalid row was inserted (unexpected).";
        $conn->query("DELETE FROM flight WHERE flightNumber = '$testFlightNo'");
    } else {
        $success = true; // trigger correctly blocked the insert
        $result  = "✅ Case 2 SUCCESS – Trigger correctly BLOCKED the INSERT.<br>"
                 . "Departure: <code>$dep</code> → Arrival: <code>$arr</code><br>"
                 . "MySQL error: <em>" . htmlspecialchars($stmt->error) . "</em>";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trigger 1 – Validate Flight Times</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f5f7fa; }
        .card { background: #fff; border-radius: 8px; padding: 24px;
                max-width: 700px; margin: auto; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        h2   { margin-top: 0; }
        .desc { background: #eef2ff; border-left: 4px solid #4f6ef7;
                padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; }
        .case { border: 1px solid #ddd; border-radius: 6px; padding: 16px;
                margin-bottom: 16px; }
        .case h3 { margin: 0 0 8px; }
        button { padding: 9px 20px; background: #0057b7; color: #fff;
                 border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        button:hover { background: #003f8a; }
        .output { margin-top: 14px; padding: 12px; border-radius: 5px;
                  background: #f0f4ff; border: 1px solid #c7d4f5; font-size: 14px; }
        a { color: #0057b7; }
    </style>
</head>
<body>
<div class="card">

    <h2>🔁 Trigger 1 – <code>trg_validate_flight_times</code></h2>

    <div class="desc">
        <strong>What it does:</strong> Fires <em>BEFORE INSERT</em> on the
        <code>flight</code> table. It checks that both
        <code>scheduledArrival &gt; scheduledDeparture</code> and
        <code>actualArrival &gt; actualDeparture</code>.
        If either condition fails, the trigger raises a SQL error and the
        INSERT is blocked.
    </div>

    <!-- Case 1 -->
    <form method="POST" class="case">
        <h3>Case 1 – Valid Times (INSERT should succeed)</h3>
        <p>Attempts to insert a test flight departing at <strong>10:00</strong>
           and arriving at <strong>13:00</strong> – arrival is after departure,
           so the trigger should allow it.</p>
        <button type="submit" name="case1">▶ Run Case 1</button>
    </form>

    <!-- Case 2 -->
    <form method="POST" class="case">
        <h3>Case 2 – Invalid Times (INSERT should be blocked)</h3>
        <p>Attempts to insert a test flight departing at <strong>13:00</strong>
           but arriving at <strong>10:00</strong> – arrival is before departure,
           so the trigger should raise an error and block the INSERT.</p>
        <button type="submit" name="case2">▶ Run Case 2</button>
    </form>

    <!-- Output -->
    <?php if ($result !== null): ?>
        <div class="output"><?= $result ?></div>
    <?php endif; ?>

    <br>
    <a href="home.php">← Return to Homepage</a>

</div>
</body>
</html>
