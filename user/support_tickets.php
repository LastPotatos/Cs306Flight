<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION["email"])) {
    header("Location: ../login.php");
    exit();
}

$client = new MongoDB\Client(
    "mongodb://localhost:27017"
);

$collection = $client->support_system->tickets;

/* =========================
   CREATE TICKET
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["title"])) {

    $title = trim($_POST["title"]);
    $message = trim($_POST["message"]);

    if (!empty($title) && !empty($message)) {

        $collection->insertOne([
            "title" => $title,
            "message" => $message,
            "email" => $_SESSION["email"],
            "status" => "open",
            "comments" => [],
            "created_at" => new MongoDB\BSON\UTCDateTime()
        ]);

        header("Location: support_tickets.php?success=1");
        exit();
    }
}

/* =========================
   ADD COMMENT
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["comment"])) {

    $ticketId = new MongoDB\BSON\ObjectId($_POST["ticket_id"]);
    $comment = trim($_POST["comment"]);

    if (!empty($comment)) {

        $collection->updateOne(
            ["_id" => $ticketId],
            ['$push' => [
                "comments" => [
                    "text" => $comment,
                    "by" => $_SESSION["email"],
                    "time" => new MongoDB\BSON\UTCDateTime()
                ]
            ]]
        );
    }

    header("Location: support_tickets.php");
    exit();
}

/* =========================
   GET USER TICKETS
========================= */
$tickets = $collection->find(
    ["email" => $_SESSION["email"]],
    ["sort" => ["created_at" => -1]]
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Support Tickets</title>
    <style>
        body { font-family: Arial; background:#f5f5f5; padding:20px; }
        .container { max-width:700px; margin:auto; background:white; padding:20px; border-radius:8px; }
        input, textarea { width:100%; padding:10px; margin:5px 0; }
        button { padding:10px; background:#007bff; color:white; border:none; cursor:pointer; }
        .ticket { background:#eee; padding:10px; margin-bottom:15px; border-radius:6px; }
        .comment { background:white; padding:5px; margin:5px 0; border-radius:5px; }
    </style>
</head>

<body>
<a href="home.php">Return Home</a>
<div class="container">

<h2>Create Ticket</h2>

<?php if (isset($_GET["success"])): ?>
    <p style="color:green;">Ticket created!</p>
<?php endif; ?>

<form method="POST">
    <input type="text" name="title" placeholder="Title" required>
    <textarea name="message" placeholder="Message" required></textarea>
    <button type="submit">Submit</button>
</form>

<hr>

<h3>My Tickets</h3>

<?php foreach ($tickets as $t): ?>
    <div class="ticket">

        <b><?= htmlspecialchars($t["title"]) ?></b><br>
        <?= htmlspecialchars($t["message"]) ?><br>

        <small>Status: <?= $t["status"] ?></small>

        <hr>

        <b>Comments</b><br>

        <?php if (!empty($t["comments"])): ?>
            <?php foreach ($t["comments"] as $c): ?>
                <div class="comment">
                    <?= htmlspecialchars($c["text"]) ?><br>
                    <small>by <?= htmlspecialchars($c["by"]) ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <i>No comments yet</i>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="ticket_id" value="<?= $t["_id"] ?>">
            <input type="text" name="comment" placeholder="Add comment..." required>
            <button type="submit">Reply</button>
        </form>

    </div>
<?php endforeach; ?>

</div>

</body>
</html>