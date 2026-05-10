<?php
session_start();
include '../db.php'; // mysqli connection → $conn

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$result  = null;
$success = null;

// Helper: clean up a test airline row
function cleanup($conn, $icao) {
    $conn->query("DELETE FROM airline WHERE codeICAO = '" . strtoupper($icao) . "'");
}

// ── Case 1: codes already uppercase ──────────────────────────────────────────
if (isset($_POST['case1'])) {

    $icao = 'TST';  // already uppercase
    $iata = 'TX';

    cleanup($conn, $icao);

    $stmt = $conn->prepare("
        INSERT INTO airline (aname, codeICAO, codeIATA, destinationSize, hubAirports)
        VALUES ('Test Airways', ?, ?, 10, 'LTFM')
    ");
    $stmt->bind_param('ss', $icao, $iata);

    if ($stmt->execute()) {
        // Read back what was actually stored
        $row = $conn->query("SELECT codeICAO, codeIATA FROM airline WHERE codeICAO='TST'")->fetch_assoc();
        $success = true;
        $result  = "✅ Case 1 SUCCESS – Airline inserted.<br>"
                 . "Supplied  → ICAO: <code>$icao</code> / IATA: <code>$iata</code><br>"
                 . "Stored as → ICAO: <code>{$row['codeICAO']}</code> / IATA: <code>{$row['codeIATA']}</code><br>"
                 . "Already uppercase; trigger stored them unchanged.";
        cleanup($conn, $icao);
    } else {
        $success = false;
        $result  = "❌ Unexpected error: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

// ── Case 2: codes in lowercase – trigger must uppercase them ─────────────────
if (isset($_POST['case2'])) {

    $icaoLower = 'lwr';   // lowercase
    $iataLower = 'lw';

    cleanup($conn, $icaoLower);

    $stmt = $conn->prepare("
        INSERT INTO airline (aname, codeICAO, codeIATA, destinationSize, hubAirports)
        VALUES ('Lowercase Airways', ?, ?, 5, 'LTFJ')
    ");
    $stmt->bind_param('ss', $icaoLower, $iataLower);

    if ($stmt->execute()) {
        // Read back – the trigger should have uppercased the codes
        $stored = $conn->query("SELECT codeICAO, codeIATA FROM airline WHERE codeICAO='LWR'")->fetch_assoc();
        $success = true;
        $result  = "✅ Case 2 SUCCESS – Airline inserted with lowercase codes.<br>"
                 . "Supplied  → ICAO: <code>$icaoLower</code> / IATA: <code>$iataLower</code><br>"
                 . "Stored as → ICAO: <code>{$stored['codeICAO']}</code> / IATA: <code>{$stored['codeIATA']}</code><br>"
                 . "Trigger automatically converted codes to uppercase before storing.";
        cleanup($conn, $icaoLower);
    } else {
        $success = false;
        $result  = "❌ Unexpected error: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trigger 2 – Airline Code Uppercase</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f5f7fa; }
        .card { background: #fff; border-radius: 8px; padding: 24px;
                max-width: 700px; margin: auto; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        h2   { margin-top: 0; }
        .desc { background: #fff8e1; border-left: 4px solid #f59e0b;
                padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; }
        .case { border: 1px solid #ddd; border-radius: 6px; padding: 16px;
                margin-bottom: 16px; }
        .case h3 { margin: 0 0 8px; }
        button { padding: 9px 20px; background: #0057b7; color: #fff;
                 border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        button:hover { background: #003f8a; }
        .output { margin-top: 14px; padding: 12px; border-radius: 5px;
                  background: #fffbf0; border: 1px solid #f5d87a; font-size: 14px; }
        a { color: #0057b7; }
    </style>
</head>
<body>
<div class="card">

    <h2>🔡 Trigger 2 – <code>trg_airline_uppercase</code></h2>

    <div class="desc">
        <strong>What it does:</strong> Fires <em>BEFORE INSERT</em> on the
        <code>airline</code> table. It automatically converts
        <code>codeICAO</code> and <code>codeIATA</code> to uppercase using
        <code>UPPER()</code>, ensuring the database stays consistent regardless
        of how the caller supplies the codes.
    </div>

    <!-- Case 1 -->
    <form method="POST" class="case">
        <h3>Case 1 – Codes Already Uppercase</h3>
        <p>Inserts a test airline with codes <strong>TST</strong> / <strong>TX</strong>
           (already uppercase). The trigger fires but makes no visible change –
           the codes are stored exactly as supplied.</p>
        <button type="submit" name="case1">▶ Run Case 1</button>
    </form>

    <!-- Case 2 -->
    <form method="POST" class="case">
        <h3>Case 2 – Codes Supplied in Lowercase</h3>
        <p>Inserts a test airline with codes <strong>lwr</strong> / <strong>lw</strong>
           (all lowercase). The trigger intercepts the INSERT and converts them
           to <strong>LWR</strong> / <strong>LW</strong> before they hit the table.</p>
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
