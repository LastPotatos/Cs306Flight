<?php
session_start();
include 'db.php'; // mysqli connection → $conn

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$stats   = null;
$error   = null;
$queried = null;

// ── Fetch list of usernames for the dropdown ──────────────────────────────────
$users = [];
$res = $conn->query("SELECT username FROM user ORDER BY username");
while ($row = $res->fetch_assoc()) {
    $users[] = $row['username'];
}

// ── Call the procedure when the form is submitted ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['username'])) {

    $username = trim($_POST['username']);
    $queried  = $username;

    $stmt = $conn->prepare("CALL GetUserFlightStats(?)");
    $stmt->bind_param('s', $username);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $stats = $result->fetch_assoc();
        } else {
            $error = "No flight data found for user <strong>" . htmlspecialchars($username) . "</strong>.";
        }
    } else {
        $error = "Procedure error: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stored Procedure – GetUserFlightStats</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f5f7fa; }
        .card { background: #fff; border-radius: 8px; padding: 24px;
                max-width: 660px; margin: auto; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        h2 { margin-top: 0; }
        .desc { background: #e8f5e9; border-left: 4px solid #4caf50;
                padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 6px; }
        select, input[type=text] {
            width: 100%; padding: 9px; font-size: 14px;
            border: 1px solid #ccc; border-radius: 5px; margin-bottom: 14px;
        }
        button { padding: 9px 24px; background: #28a745; color: #fff;
                 border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        button:hover { background: #1e7e34; }
        .result { margin-top: 20px; border: 1px solid #c8e6c9;
                  border-radius: 6px; overflow: hidden; }
        .result table { width: 100%; border-collapse: collapse; }
        .result th { background: #4caf50; color: #fff; padding: 10px 14px; text-align: left; }
        .result td { padding: 10px 14px; border-top: 1px solid #e0e0e0; }
        .result tr:nth-child(even) td { background: #f9fffe; }
        .stat-label { color: #555; font-size: 13px; }
        .stat-value { font-weight: bold; font-size: 16px; }
        .error { color: #c0392b; margin-top: 14px; }
        a { color: #0057b7; }
    </style>
</head>
<body>
<div class="card">

    <h2>📊 Stored Procedure – <code>GetUserFlightStats</code></h2>

    <div class="desc">
        <strong>What it does:</strong> Takes a <code>username</code> as input
        and returns a summary of that user's flight history:
        <ul style="margin: 6px 0 0 0;">
            <li><strong>total_flights</strong> – number of tickets logged</li>
            <li><strong>total_spent</strong> – sum of all ticket prices</li>
            <li><strong>avg_price</strong> – average price per ticket</li>
            <li><strong>most_used_class</strong> – the travel class flown most often</li>
        </ul>
    </div>

    <!-- Input form -->
    <form method="POST">
        <label for="username">Select a username:</label>
        <select name="username" id="username">
            <option value="">-- Choose a user --</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= htmlspecialchars($u) ?>"
                    <?= ($queried === $u) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">▶ Call Procedure</button>
    </form>

    <!-- Error -->
    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <!-- Results table -->
    <?php if ($stats): ?>
        <div class="result">
            <table>
                <tr>
                    <th colspan="2">
                        Results for: <?= htmlspecialchars($queried) ?>
                    </th>
                </tr>
                <tr>
                    <td class="stat-label">Total Flights</td>
                    <td class="stat-value"><?= (int)$stats['total_flights'] ?></td>
                </tr>
                <tr>
                    <td class="stat-label">Total Spent</td>
                    <td class="stat-value">
                        <?= $stats['total_spent'] !== null
                            ? number_format((float)$stats['total_spent'], 2) . ' (mixed currencies)'
                            : '—' ?>
                    </td>
                </tr>
                <tr>
                    <td class="stat-label">Average Price per Ticket</td>
                    <td class="stat-value">
                        <?= $stats['avg_price'] !== null
                            ? number_format((float)$stats['avg_price'], 2)
                            : '—' ?>
                    </td>
                </tr>
                <tr>
                    <td class="stat-label">Most Used Travel Class</td>
                    <td class="stat-value">
                        <?= htmlspecialchars($stats['most_used_class'] ?? '—') ?>
                    </td>
                </tr>
            </table>
        </div>
    <?php endif; ?>

    <br>
    <a href="home.php">← Return to Homepage</a>

</div>
</body>
</html>
