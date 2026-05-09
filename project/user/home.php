
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
    <li><a href="airport.php">View Airports</a></li>
    <li><a href="tickets.php">View Tickets</a></li>
    <li><a href="insert.php">Insert Flight</a></li>
    <li><a href="support_tickets.php">Ask for help</a></li>
    <li><a href="logout.php">Logout</a></li>
    <a> - </a>
    <li><a href="trigger1.php">Demo trigger 1</a></li>
    <li><a href="trigger2.php">Demo trigger 2</a></li>
    <li><a href="procedure1.php">Demo procedure 1</a></li>
    <li><a href="procedure2.php">Demo procedure 2</a></li>
</ul>

<hr>
</body>
</html>

