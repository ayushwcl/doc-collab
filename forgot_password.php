<?php
include 'includes/header.php'; 
include 'includes/db.php'; // Include database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure this path is correct

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    if (!empty($email)) {
        // Check if the email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
			
			// Set the correct timezone
			date_default_timezone_set('Asia/Kolkata');
			
            // Generate a unique reset token
            $token = bin2hex(random_bytes(50));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expiry time

            // Store the token and expiry in the database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = :reset_token, reset_token_expiry = :expiry WHERE email = :email");
            $stmt->bindParam(':reset_token', $token);
            $stmt->bindParam(':expiry', $expiry);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            // Send reset link to user's email using PHPMailer
            $resetLink = "http://localhost/demo/reset_password.php?token=" . $token;
            $subject = "Password Reset Request";
            $message = "Click this link to reset your password: <a href='$resetLink'>$resetLink</a>";

            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();                                            // Send using SMTP
                $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
                $mail->SMTPAuth   = true;                               // Enable SMTP authentication
                $mail->Username   = 'ayushofficial651@gmail.com';                // SMTP username
                $mail->Password   = 'xtpi pukt zwxg qqmm';                 // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;    // Enable TLS encryption
                $mail->Port       = 587;                                  // TCP port to connect to

                // Recipients
                $mail->setFrom('ayushofficial651@gmail.com', 'Ayush Mishra');
                $mail->addAddress($email);     // Add a recipient

                // Content
                $mail->isHTML(true);                                  // Set email format to HTML
                $mail->Subject = $subject;
                $mail->Body    = $message;

                // Send the email
                $mail->send();
                echo "<div class='alert alert-success'>A password reset link has been sent to your email address.</div>";
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Failed to send the reset email. Error: {$mail->ErrorInfo}</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>No account found with that email address.</div>";
        }
    }
}
?>

<div class="container">
    <h2 class="my-4">Forgot Password</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Enter your email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <button type="submit" class="btn btn-primary">Send Reset Link</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
