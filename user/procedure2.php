<?php
session_start();
include '../db.php'; // mysqli connection → $conn

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$stats    = null;
$error    = null;
$queried  = null;

// ── Fetch airlines that actually have flights in the DB ───────────────────────
$airlines = [];
$res = $conn->query("
    SELECT DISTINCT a.codeICAO, a.aname
    FROM   airline  a
    JOIN   aircraft ac ON ac.airlineICAO   = a.codeICAO
    JOIN   flight   f  ON f.aircraftRegistration = ac.registration
    ORDER  BY a.aname
");
while ($row = $res->fetch_assoc()) {
    $airlines[] = $row;
}

// ── Call procedure on form submit ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['icao'])) {

    $icao    = strtoupper(trim($_POST['icao']));
    $queried = $icao;

    try {
        $stmt = $conn->prepare("CALL GetAirlineStats(?)");
        $stmt->bind_param('s', $icao);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $stats = $result->fetch_assoc();
            // If no flights matched, total_flights will be 0
            if ((int)$stats['total_flights'] === 0) {
                $error  = "No flight data found for airline <strong>" . htmlspecialchars($icao) . "</strong>.";
                $stats  = null;
            }
        } else {
            $error = "No data returned for airline <strong>" . htmlspecialchars($icao) . "</strong>.";
        }
        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        if (str_contains($e->getMessage(), 'does not exist')) {
            $error = "⚠️ The stored procedure <code>GetAirlineStats</code> has not been created yet.<br>"
                   . "Please run <strong>procedure_airline_stats.sql</strong> in phpMyAdmin first.";
        } else {
            $error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Helper: format delay nicely
function formatDelay($minutes) {
    if ($minutes === null) return '—';
    $minutes = (float)$minutes;
    $sign    = $minutes >= 0 ? '+' : '';
    $abs     = abs($minutes);
    $h       = floor($abs / 60);
    $m       = round($abs - $h * 60);
    $label   = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    $color   = $minutes <= 0 ? '#2e7d32' : ($minutes <= 15 ? '#f57c00' : '#c62828');
    return "<span style='color:{$color};font-weight:bold'>{$sign}{$minutes} min ({$label})</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stored Procedure – GetAirlineStats</title>
    <style>
        body  { font-family: Arial, sans-serif; padding: 30px; background: #f5f7fa; }
        .card { background: #fff; border-radius: 8px; padding: 24px;
                max-width: 680px; margin: auto; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        h2    { margin-top: 0; }
        .desc { background: #e3f2fd; border-left: 4px solid #1976d2;
                padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
        .desc ul { margin: 6px 0 0 0; padding-left: 18px; }
        label  { font-weight: bold; display: block; margin-bottom: 6px; }
        select { width: 100%; padding: 9px; font-size: 14px;
                 border: 1px solid #ccc; border-radius: 5px; margin-bottom: 14px; }
        button { padding: 9px 24px; background: #1976d2; color: #fff;
                 border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        button:hover { background: #1256a3; }
        .error { color: #c62828; margin-top: 14px; font-size: 14px; }

        /* Result card */
        .result       { margin-top: 22px; border: 1px solid #bbdefb;
                        border-radius: 8px; overflow: hidden; }
        .result-header { background: #1976d2; color: #fff;
                         padding: 12px 18px; font-size: 16px; font-weight: bold; }
        .stat-grid    { display: grid; grid-template-columns: 1fr 1fr;
                        gap: 0; }
        .stat-box     { padding: 16px 18px; border-top: 1px solid #e3f2fd; }
        .stat-box:nth-child(odd)  { border-right: 1px solid #e3f2fd; }
        .stat-box.full { grid-column: 1 / -1; }
        .stat-label   { font-size: 11px; text-transform: uppercase;
                        color: #888; margin-bottom: 4px; }
        .stat-value   { font-size: 22px; font-weight: bold; color: #1a1a1a; }
        .stat-value.delay { font-size: 15px; }
        a { color: #1976d2; }
    </style>
</head>
<body>
<div class="card">

    <h2>✈️ Stored Procedure – <code>GetAirlineStats</code></h2>

    <div class="desc">
        <strong>What it does:</strong> Looks up all flights operated by a given
        airline (matched via aircraft registration → airline ICAO) and returns:
        <ul>
            <li><strong>total_flights</strong> – distinct flight numbers in the diary</li>
            <li><strong>destinations_served</strong> – distinct arrival airports</li>
            <li><strong>avg_departure_delay_min</strong> – average gap between scheduled and actual departure</li>
            <li><strong>avg_arrival_delay_min</strong> – average gap between scheduled and actual arrival</li>
        </ul>
        Negative delay = arrived/departed <em>early</em>.
    </div>

    <!-- Input form -->
    <form method="POST">
        <label for="icao">Select an airline:</label>
        <select name="icao" id="icao">
            <option value="">-- Choose an airline --</option>
            <?php foreach ($airlines as $a): ?>
                <option value="<?= htmlspecialchars($a['codeICAO']) ?>"
                    <?= ($queried === $a['codeICAO']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['aname']) ?>
                    (<?= htmlspecialchars($a['codeICAO']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">▶ Call Procedure</button>
    </form>

    <!-- Error -->
    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <!-- Results -->
    <?php if ($stats): ?>
        <div class="result">
            <div class="result-header">
                <?= htmlspecialchars($stats['airline_name']) ?>
                &nbsp;·&nbsp; ICAO: <?= htmlspecialchars($queried) ?>
            </div>
            <div class="stat-grid">

                <div class="stat-box">
                    <div class="stat-label">Total Flights</div>
                    <div class="stat-value"><?= (int)$stats['total_flights'] ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Destinations Served</div>
                    <div class="stat-value"><?= (int)$stats['destinations_served'] ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Avg Departure Delay</div>
                    <div class="stat-value delay">
                        <?= formatDelay($stats['avg_departure_delay_min']) ?>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Avg Arrival Delay</div>
                    <div class="stat-value delay">
                        <?= formatDelay($stats['avg_arrival_delay_min']) ?>
                    </div>
                </div>

            </div>
        </div>
    <?php endif; ?>

    <br>
    <a href="home.php">← Return to Homepage</a>

</div>
</body>
</html>