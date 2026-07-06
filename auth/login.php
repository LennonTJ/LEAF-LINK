<?php

include("../config/database.php");

$role = $_GET['role'] ?? '';

?>

<!DOCTYPE html>
<html>
<head>
    <title>LeafLink Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="header">
    <h1>LeafLink</h1>
    <p>Contract Farming Management System</p>
</div>

<div class="layout" style="justify-content:center;">
    <div class="content" style="max-width:520px;">
        <div class="card">
            <h2><?php echo ucfirst($role); ?> Login</h2>

            <form action="authenticate.php" method="POST">
                <input type="hidden" name="role" value="<?php echo $role; ?>">

                <label>Username</label>
                <br>
                <input type="text" name="username" required>

                <br><br>

                <label>Password</label>
                <br>
                <input type="password" name="password" required>

                <br><br>

                <button type="submit">Login</button>
            </form>
        </div>

        <div class="card" style="margin-bottom:0;">
            <a class="btn" href="../index.php">← Back to Portals</a>
        </div>
    </div>
</div>

</body>
</html>
