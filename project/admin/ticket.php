<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';

/* =========================
   SIMPLE ADMIN PROTECTION
========================= */
if (!isset($_SESSION["isAdmin"]) || $_SESSION["isAdmin"] != 1) {
    header("Location: home.php");
    exit();
}

$client = new MongoDB\Client(
    "mongodb://localhost:27017"
);

$collection = $client->support_system->tickets;
$users = $collection->distinct("email");
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
   DELETE TICKET
========================= */
if (isset($_GET['delete'])) {
    $id = new MongoDB\BSON\ObjectId($_GET['delete']);

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

$selectedUser = $_GET['user'] ?? null;

if ($selectedUser) {
    $tickets = $collection->find(
        ["email" => $selectedUser],
        ["sort" => ["created_at" => -1]]
    );
} else {
    $tickets = $collection->find([], [
        "sort" => ["created_at" => -1]
    ]);
}
?>
<!DOCTYPE html>
<html>
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
        .delete-btn {
            background: black;
            color: white;
        }
        .open { color: green; font-weight: bold; }
        .closed { color: gray; font-weight: bold; }
        .comment-box { background:#eee; padding:5px; margin-top:5px; border-radius:5px; }
    </style>
</head>

<body>
<a href="home.php">Return Home</a>
<div class="container">

<h2>All Support Tickets</h2>
<form method="GET" style="margin-bottom:20px;">

    <label>Filter by user:</label>

    <select name="user" onchange="this.form.submit()">

        <option value="">-- All Users --</option>

        <?php foreach ($users as $u): ?>
            <option value="<?= htmlspecialchars($u) ?>"
                <?= ($selectedUser == $u) ? 'selected' : '' ?>>
                <?= htmlspecialchars($u) ?>
            </option>
        <?php endforeach; ?>

    </select>

</form>
<?php foreach ($tickets as $t): ?>

    <div class="ticket">

        <div class="title">
            <?= htmlspecialchars($t["title"]) ?>
        </div>

        <div class="message">
            <?= htmlspecialchars($t["message"]) ?>
        </div>

        <div class="meta">
            📧 User: <?= htmlspecialchars($t["email"] ?? "unknown") ?><br>
            🆔 ID: <?= (string)$t["_id"] ?><br>

            Status:
            <?php if ($t["status"] == "open"): ?>
                <span class="open">OPEN</span>
            <?php else: ?>
                <span class="closed">CLOSED</span>
            <?php endif; ?>
        </div>

        <!-- COMMENTS (ADMIN VIEW) -->
        <div>
            <b>Comments:</b><br>

            <?php if (!empty($t["comments"])): ?>
                <?php foreach ($t["comments"] as $c): ?>
                    <div class="comment-box">
                        <?= htmlspecialchars($c["text"]) ?><br>
                        <small>by <?= htmlspecialchars($c["by"]) ?></small>
                    </div>
                <?php endforeach; ?>
                <form method="POST" action="admin_reply.php">
                    <input type="hidden" name="ticket_id" value="<?= $t["_id"] ?>">
                    <input type="text" name="comment" placeholder="Admin reply..." required>
                    <button type="submit">Reply as Admin</button>
                </form>
            <?php else: ?>
                <i>No comments</i>
            <?php endif; ?>
        </div>

        <div class="actions">

            <?php if ($t["status"] == "open"): ?>
                <a class="btn close-btn"
                   href="?close=<?= $t["_id"] ?>"
                   onclick="return confirm('Close this ticket?')">
                   Close
                </a>

            <?php elseif ($t["status"] == "closed"): ?>
                <a class="btn delete-btn"
                   href="?delete=<?= $t["_id"] ?>"
                   onclick="return confirm('Delete permanently?')">
                   Delete
                </a>
            <?php endif; ?>

        </div>

    </div>

<?php endforeach; ?>

</div>

</body>
</html>