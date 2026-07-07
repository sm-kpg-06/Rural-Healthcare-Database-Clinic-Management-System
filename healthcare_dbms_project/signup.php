<?php
session_start();

if(isset($_SESSION['user'])){
    header("Location: dashboard.php");
    exit();
}

include 'db.php';

$error = '';
$success = '';

if(isset($_POST['signup'])){
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if(empty($username) || empty($email) || empty($password) || empty($confirm_password)){
        $error = "All fields are required!";
    } elseif(strlen($username) < 3){
        $error = "Username must be at least 3 characters!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Please enter a valid email!";
    } elseif(strlen($password) < 6){
        $error = "Password must be at least 6 characters!";
    } elseif($password !== $confirm_password){
        $error = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $safe_username = $conn->real_escape_string($username);
        $safe_email = $conn->real_escape_string($email);
        $safe_password = $conn->real_escape_string($hashed_password);
        
        $check = $conn->query("SELECT * FROM APP_USER WHERE Username='$safe_username' OR Email='$safe_email'");
        
        if($check && $check->num_rows > 0){
            $error = "Username or email already exists!";
        } else {
            $insert_sql = "INSERT INTO APP_USER (Username, Email, Password) VALUES ('$safe_username', '$safe_email', '$safe_password')";
            if($conn->query($insert_sql)){
                $success = "Account created successfully! Please sign in.";
                $_POST = [];
            } else {
                $error = "Error creating account: " . $conn->error;
            }
        }
    }
}

include 'header.php';
?>

<div class="card" style="margin-top: 10vh;">
    <h1>🆕 Create Account</h1>
    <p class="subtitle">Sign up to access the clinic management system</p>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" style="width: 100%;">
        <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" style="width: 100%;">
        <input type="password" name="password" placeholder="Password (min 6 characters)" required style="width: 100%;">
        <input type="password" name="confirm_password" placeholder="Confirm Password" required style="width: 100%;">
        <button type="submit" name="signup" class="btn" style="width: 100%;">Create Account</button>
    </form>

    <?php if(!empty($error)): ?>
        <p style="color: var(--danger-color); margin-top: 15px; font-weight: 600;"><?php echo $error; ?></p>
    <?php endif; ?>

    <?php if(!empty($success)): ?>
        <p style="color: #4facfe; margin-top: 15px; font-weight: 600;"><?php echo $success; ?></p>
        <p style="color: var(--text-secondary); margin-top: 10px;">
            Already have an account? <a href="login.php" style="color: var(--accent-color); text-decoration: none; font-weight: 600;">Sign In</a>
        </p>
    <?php endif; ?>

    <p style="color: var(--text-secondary); margin-top: 20px; text-align: center;">
        Already have an account? <a href="login.php" style="color: var(--accent-color); text-decoration: none; font-weight: 600;">Sign In</a>
    </p>
</div>

<?php include 'footer.php'; ?>
