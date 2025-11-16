<?php
session_start();

// Only admins can send messages from here
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login_admin.php");
    exit;
}

include('../config.php');
error_reporting(0);

if (isset($_POST["addmsg"])) {
    $feedback = mysqli_real_escape_string($conn, $_POST["feedback"]);
    $fname    = mysqli_real_escape_string($conn, $_POST["fname"]);

    $sql = "INSERT INTO message (fname, feedback) VALUES ('$fname', '$feedback')";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo "<script>alert('Message sent successfully!');</script>";
        header("Location: messages.php");
        exit;
    } else {
        echo "<script>alert('Message sending failed.');</script>";
        header("Location: messages.php");
        exit;
    }
}
?>
