<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION["isAdmin"]) || $_SESSION["isAdmin"] != 1) {
    die("Not allowed");
}

$client = new MongoDB\Client(
    "mongodb://localhost:27017"
);

$collection = $client->support_system->tickets;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $ticketId = new MongoDB\BSON\ObjectId($_POST["ticket_id"]);
    $comment = trim($_POST["comment"]);

    if (!empty($comment)) {

        $collection->updateOne(
            ["_id" => $ticketId],
            ['$push' => [
                "comments" => [
                    "text" => $comment,
                    "by" => $_SESSION["username"],
                    "role" => "admin",
                    "time" => new MongoDB\BSON\UTCDateTime()
                ]
            ]]
        );
    }
}

header("Location: ticket.php");
exit();