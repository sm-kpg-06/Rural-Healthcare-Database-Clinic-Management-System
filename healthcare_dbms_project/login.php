<?php
session_start();

if(isset($_POST['login'])){
    // Placeholder login - in a real app this should check DB
    if($_POST['username']=="admin" && $_POST['password']=="1234"){
        $_SESSION['user']="admin";
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid Login!";
    }
}
include 'header.php';
?>

<div class="card" style="margin-top: 10vh;">
    <h1>🔐 Login</h1>
    <p class="subtitle">Enter your credentials to access the system</p>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login" class="btn" style="width: 100%;">Login</button>
    </form>

    <?php if(isset($error)): ?>
        <p style="color: var(--danger-color); margin-top: 15px;"><?php echo $error; ?></p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>