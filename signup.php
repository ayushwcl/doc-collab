<?php
include 'includes/db.php';
include 'includes/functions.php'; // Correct file name

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email']; // Capture the email input
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if (!empty($username) && !empty($email) && !empty($password)) {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            // Username or email already exists
            echo "<div class='alert alert-danger'>Username or email already taken. Please choose another one.</div>";
        } else {
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $password);
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>Signup successful. <a href='login.php'>Login here</a>.</div>";
            } else {
                echo "<div class='alert alert-danger'>Signup failed. Please try again.</div>";
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <h2 class="my-4">Signup</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Signup</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
