<?php
include 'includes/header.php'; 
include 'includes/db.php'; // Include database connection

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token is valid and not expired
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = :reset_token AND reset_token_expiry > NOW()");
    $stmt->bindParam(':reset_token', $token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Proceed with password reset if the token is valid
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate new password and confirmation
            if (empty($new_password) || empty($confirm_password)) {
                echo "<div class='alert alert-danger'>Both password fields are required.</div>";
            } elseif ($new_password !== $confirm_password) {
                echo "<div class='alert alert-danger'>Passwords do not match. Please try again.</div>";
            } else {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update the password in the database and clear the reset token
                $stmt = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expiry = NULL WHERE email = :email");
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':email', $user['email']); // Update by email instead of reset_token

                if ($stmt->execute()) {
                    echo "<div class='alert alert-success'>Your password has been reset. <a href='login.php'>Login here</a>.</div>";
                } else {
                    echo "<div class='alert alert-danger'>Failed to reset the password. Please try again.</div>";
                }
            }
        }
    } else {
        echo "<div class='alert alert-danger'>Invalid or expired reset token.</div>";
    }
} else {
    echo "<div class='alert alert-danger'>No reset token provided.</div>";
}
?>

<div class="container">
    <h2 class="my-4">Reset Password</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">Reset Password</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
