<?php

session_start();

if (!isset($_SESSION['user_id'])) {

    header("Location: ../auth/login.php");

    exit();

}

?>

<h1>Welcome Admin</h1>

<p>

Hello,

<?php echo $_SESSION['username']; ?>

</p>