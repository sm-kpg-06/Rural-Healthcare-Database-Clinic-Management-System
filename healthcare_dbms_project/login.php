<?php
session_start();

if(isset($_SESSION['user'])){
    header("Location: dashboard.php");
    exit();
}

include 'db.php';

$error = '';

if(isset($_POST['login'])){
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if(empty($username) || empty($password)){
        $error = "Username and password are required!";
    } else {
        $user = $conn->query("SELECT User_ID, Username, Password FROM APP_USER WHERE Username='$username'");
        
        if($user && $user->num_rows > 0){
            $row = $user->fetch_assoc();
            if(password_verify($password, $row['Password'])){
                $_SESSION['user'] = $row['Username'];
                $_SESSION['user_id'] = $row['User_ID'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password!";
            }
        } else {
            $error = "Invalid username or password!";
        }
    }
}

include 'header.php';
?>

<div class="card" style="margin-top: 10vh;">
    <h1>🔐 Sign In</h1>
    <p class="subtitle">Enter your credentials to access the system</p>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login" class="btn" style="width: 100%;">Sign In</button>
    </form>

    <?php if(isset($error) && !empty($error)): ?>
        <p style="color: var(--danger-color); margin-top: 15px; font-weight: 600;"><?php echo $error; ?></p>
    <?php endif; ?>

    <p style="color: var(--text-secondary); margin-top: 20px; text-align: center;">
        Don't have an account? <a href="signup.php" style="color: var(--accent-color); text-decoration: none; font-weight: 600;">Sign Up</a>
    </p>

    <div style="margin-top: 24px; padding: 16px; background: rgba(79, 172, 254, 0.08); border: 1px solid rgba(79, 172, 254, 0.2); border-radius: 12px;">
        <p style="color: #4facfe; font-size: 13px; margin: 0;">
            <strong>Demo Account:</strong> Use <code>admin</code> / <code>1234</code> to test, or create a new account above.
        </p>
    </div>
</div>

<?php include 'footer.php'; ?>