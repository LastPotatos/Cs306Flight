<?php
session_start();
require "db.php"; // your DB connection file

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (!empty($username) && !empty($email) && !empty($password)) {
        // check if email exists
        $check = $conn->prepare("SELECT email FROM user WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists!";
        } else {

            // insert user
            $stmt = $conn->prepare(
                "INSERT INTO user (username, email, passwd, isAdmin) VALUES (?, ?, ?, 0)"
            );

            $stmt->bind_param("sss", $username, $email, $password);
            $stmt->execute();

            header("Location: login.php");
            exit();
        }

    } else {
        $error = "Please fill all fields";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>

<h2>Register</h2>

<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Register</button>
</form>

<a href="login.php">Already have an account?</a>

</body>
</html>