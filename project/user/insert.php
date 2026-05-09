
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
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $departure_delay = $_POST['departure_delay'];
    $arrival_delay = $_POST['arrival_delay'];
    $registration_no = $_POST['registration_no'];

    // CHANGE TABLE/COLUMN NAMES IF NEEDED
    $sql = "INSERT INTO flight (flight_no, departure_airport, arrival_airport, departure_time, arrival_time, departure_delay, arrival_delay, registration_no)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

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

    <h2>About your flight</h2>

    <label>Flight Number:</label><br>
    <input type="text" name="flight_no" required><br><br>

    <label>Departure Airport:</label><br>
    <input type="text" name="departure" required><br><br>

    <label>Arrival Airport:</label><br>
    <input type="text" name="arrival" required><br><br>

    <label>Scheduled Departure:</label><br>
    <input type="text" name="departure_time" required><br><br>

    <label>Scheduled Arrival:</label><br>
    <input type="time" name="arrival_time" required><br><br>

    <label>Scheduled Departure:</label><br>
    <input type="time" name="departure_time" required><br><br>

    <label>Departure Delay:</label><br>
    <label>In minutes</label><br>
    <input type="number" name="departure_delay" required default="0"><br><br>

    <label>Arrival Delay:</label><br>
    <label>In minutes</label><br>
    <input type="number" name="arrival_delay" required default="0"><br><br>

    <label>Registration Number:</label><br>
    <label>Hint: Look at the letters on the tail of the aircraft!</label><br>
    <input type="text" name="registration_no"><br><br>

    <h2>About your ticket</h2>

    <label>What airline did you book this ticket with?</label><br>
    <label>Despite your flight being one airline, you might have booked it with another.</label><br>
    <input type="text" name="ticket_airline"><br><br>

    <label>Your seat:</label><br>
    <input type="text" name="seat"><br><br>

    <label>Your class:</label><br>
    <input type="text" name="class" default="Standard"><br><br>

    <label>What add-ons did you have for this flight?</label><br>
    <label>Examples:<br>
    XBAG for extra baggage<br>
    BDML for complementary meal due to bundling<br>
    MEAL for meals ordered on demand<br>
    FLEX for flexible booking<br>
    SFLX for semi-flexible booking<br>
    SEAT for selected seat<br>
    ACCS for lounge access<br>
    </label><br>
    <input type="text" name="add_ons"><br><br>

    <label>How much real money did your ticket cost?</label><br>
    <input type="number" name="price" default="0"><br><br>

    <label>What is the currency?</label><br>
    <input type="text" name="currency" default="TRY"><br><br>

    <label>Did you spend any airline points for this ticket?</label><br>
    <input type="number" name="price_pts" default="0"><br><br>

    <label>How many airline points did you earn?</label><br>
    <label>Hint: It's regular miles for TK and VF. BolPoints for PC flights.</label><br>
    <input type="number" name="award_pts" default="0"><br><br>

    <label>How many airline experience points did you earn?</label><br>
    <label>Hint: It's status miles for TK and VF. PC flights don't give experience.</label><br>
    <input type="number" name="exp_pts" default="0"><br><br>

    <label>Any comments on your flight?</label><br>
    <input type="text" name="comments"><br><br>

    <label>Briefly describe why are you taking this flight</label><br>
    <input type="text" name="purpose"><br><br>

    <button type="submit">Insert Flight</button>

</form>

<br>

<p>
<?php echo $message; ?>
</p>

</body>
</html>


// not finished yet