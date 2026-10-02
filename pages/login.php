<?php
require_once('../includes/config.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_email = $_POST['username_email'];
    $password = $_POST['password'];

    // Basic server-side validation
    if (empty($username_email) || empty($password)) {
        die("Please fill in all fields.");
    }

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username_email, $username_email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $username, $hashed_password);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            // Password is correct, start a new session
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;

            // Redirect to a protected page
            header("location: ../index.php"); // Create a welcome.php page
        } else {
            echo "Invalid username/email or password.";
        }
    } else {
        echo "Invalid username/email or password.";
    }

    $stmt->close();
    $conn->close();
}
?>