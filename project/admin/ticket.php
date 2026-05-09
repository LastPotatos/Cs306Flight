<?php
require __DIR__ . '/../vendor/autoload.php';

$client = new MongoDB\Client("mongodb://localhost:27017");
$collection = $client->support_system->tickets;

/* =========================
   CLOSE TICKET
========================= */
if (isset($_GET['close'])) {
    $id = new MongoDB\BSON\ObjectId($_GET['close']);

    $collection->updateOne(
        ["_id" => $id],
        ['$set' => ["status" => "closed"]]
    );

    header("Location: ticket.php");
    exit();
}

/* =========================
   DELETE TICKET (ONLY CLOSED)
========================= */
if (isset($_GET['delete'])) {
    $id = new MongoDB\BSON\ObjectId($_GET['delete']);

    // safety check: only delete closed tickets
    $ticket = $collection->findOne(["_id" => $id]);

    if ($ticket && $ticket["status"] === "closed") {
        $collection->deleteOne(["_id" => $id]);
    }

    header("Location: ticket.php");
    exit();
}

/* =========================
   FETCH TICKETS
========================= */
$tickets = $collection->find([], [
    "sort" => ["created_at" => -1]
]);
?>

<!DOCTYPE html>
<html>
    <br>
        <a href="home.php">
    <button type="button">⬅ Go Back to Home</button>
</a>
<head>
    <title>Admin - View Tickets</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: auto;
        }
        .ticket {
            background: white;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 5px solid #007bff;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
        }
        .message {
            margin-top: 5px;
        }
        .meta {
            font-size: 12px;
            color: gray;
            margin-top: 5px;
        }
        .actions {
            margin-top: 10px;
        }
        .btn {
            padding: 6px 10px;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            text-decoration: none;
        }
        .close-btn {
            background: red;
            color: white;
        }
        .open {
            color: green;
            font-weight: bold;
        }
        .closed {
            color: gray;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>All Support Tickets</h2>

    <?php foreach ($tickets as $t): ?>
        <div class="ticket">

            <div class="title">
                <?= htmlspecialchars($t["title"]) ?>
            </div>

            <div class="message">
                <?= htmlspecialchars($t["message"]) ?>
            </div>

            <div class="meta">
                📧 User: <?= htmlspecialchars($t["email"] ?? "unknown") ?> <br>
                🆔 ID: <?= $t["_id"] ?> <br>
                Status:
                <?php if ($t["status"] == "open"): ?>
                    <span class="open">OPEN</span>
                <?php else: ?>
                    <span class="closed">CLOSED</span>
                <?php endif; ?>
            </div>

            <div class="actions">

                <?php if ($t["status"] == "open"): ?>
                    <a class="btn close-btn"
                    href="?close=<?= $t["_id"] ?>"
                    onclick="return confirm('Close this ticket?')">
                    Close Ticket
                    </a>

                <?php elseif ($t["status"] == "closed"): ?>
                    <a class="btn close-btn"
                    href="?delete=<?= $t["_id"] ?>"
                    onclick="return confirm('Delete this closed ticket permanently?')">
                    Delete Ticket
                    </a>
                <?php endif; ?>

            </div>

        </div>
    <?php endforeach; ?>

</div>

</body>
</html>