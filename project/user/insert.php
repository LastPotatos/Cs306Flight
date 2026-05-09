
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $flight_no = $_POST['flight_no'];
    $departure = $_POST['departure'];
    $arrival = $_POST['arrival'];

    // CHANGE TABLE/COLUMN NAMES IF NEEDED
    $sql = "INSERT INTO flight (flight_no, departure_airport, arrival_airport)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $flight_no, $departure, $arrival);

        if ($stmt->execute()) {
            $message = "Flight inserted successfully!";
        } else {
            $message = "Insert failed: " . $stmt->error;
        }
    } else {
        $message = "SQL Prepare Failed";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Insert Flight</title>
</head>
<body>

<h1>Insert Flight</h1>

<a href="home.php">Return Home</a>

<hr>

<form method="POST">

    <label>Flight Number:</label><br>
    <input type="text" name="flight_no" required><br><br>

    <label>Departure Airport:</label><br>
    <input type="text" name="departure" required><br><br>

    <label>Arrival Airport:</label><br>
    <input type="text" name="arrival" required><br><br>

    <button type="submit">Insert Flight</button>

</form>

<br>

<p>
<?php echo $message; ?>
</p>

</body>
</html>


// not finished yet