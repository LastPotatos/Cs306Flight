<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

$client = new MongoDB\Client(
    "mongodb+srv://admin:admin@cluster.3dm8sxf.mongodb.net/?retryWrites=true&w=majority&appName=Cluster"
);
$collection = $client->support_system->tickets;

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"]);
    $message = trim($_POST["message"]);

    if (!empty($title) && !empty($message)) {
        $collection->insertOne([
            "title" => $title,
            "message" => $message,
            "email" => $_SESSION["email"],
            "status" => "open",
            "created_at" => new MongoDB\BSON\UTCDateTime()
        ]);

        header("Location: support_tickets.php?success=1");
        exit();
    } else {
        $error = "Please fill all fields.";
    }
}

$userEmail = $_SESSION["email"];

$tickets = $collection->find(
    ["email" => $userEmail],
    ["sort" => ["created_at" => -1]]
);
?>

<!DOCTYPE html>
<html>
    <br>
        <a href="../user/home.php">
    <button type="button">⬅ Go Back to Home</button>
</a>
<head>
    <title>Create Ticket</title>
    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
        }
        button {
            padding: 10px 15px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Create Support Ticket</h2>

    <?php if (!empty($success)): ?>
        <p class="success"><?= $success ?></p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="title" placeholder="Ticket title" required>
        <textarea name="message" placeholder="Describe your issue..." required></textarea>
        <button type="submit">Submit Ticket</button>
    </form>
        <?php if (isset($_GET["success"])): ?>
            <p class="success">Ticket submitted successfully!</p>
        <?php endif; ?>
</div>
<hr>

<h3>My Tickets</h3>

<?php foreach ($tickets as $t): ?>
    <div style="background:#eee; padding:10px; margin-bottom:10px; border-radius:6px;">

        <b><?= htmlspecialchars($t["title"]) ?></b><br>
        <?= htmlspecialchars($t["message"]) ?><br>

        <small>
            Status: <?= $t["status"] ?> 
        </small>

    </div>
<?php endforeach; ?>
</body>
</html>