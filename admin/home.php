<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Flight Diary System</title>
</head>
<body>

<h1>Welcome <?php echo $_SESSION['username']; ?>!</h1>

<hr>

<h2>Navigation</h2>

<ul>
    <li><a href="ticket.php">View tickets</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>

<hr>
</body>
</html>
