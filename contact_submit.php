<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure PHPMailer is installed via Composer

// Database configuration
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "ericon_db";

// Admin email to receive contact form data
$admin_email = "mogeniyvonne20@gmail.com";

// Gmail SMTP configuration
$smtp_host = 'smtp.gmail.com';
$smtp_username = 'mogeniyvonne20@gmail.com'; // Your Gmail address
$smtp_password = 'mswr wswt avlw gtus'; // Gmail app password or your Gmail password if less secure apps enabled
$smtp_port = 587; // TLS port

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create table if it doesn't exist
$tableSql = "CREATE TABLE IF NOT EXISTS contact_messages (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($tableSql) === FALSE) {
    die("Error creating table: " . $conn->error);
}

// Function to sanitize input data
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST["name"] ?? '');
    $email = sanitize_input($_POST["email"] ?? '');
    $subject = sanitize_input($_POST["subject"] ?? '');
    $message = sanitize_input($_POST["message"] ?? '');

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo "All fields are required.";
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit;
    }

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $message);

    if ($stmt->execute()) {
        // Send email to admin using PHPMailer
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_username;
            $mail->Password = $smtp_password;
            $mail->SMTPSecure = 'tls';
            $mail->Port = $smtp_port;

            //Recipients
            $mail->setFrom($smtp_username, 'Website Contact Form');
            $mail->addAddress($admin_email);

            // Content
            $mail->isHTML(false);
            $mail->Subject = "New Contact Form Submission: " . $subject;
            $mail->Body    = "You have received a new message from the contact form on your website.\n\n" .
                             "Name: $name\n" .
                             "Email: $email\n" .
                             "Subject: $subject\n" .
                             "Message:\n$message\n";

            $mail->send();
            echo "Message sent successfully.";
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Invalid request method.";
}

$conn->close();
?>
