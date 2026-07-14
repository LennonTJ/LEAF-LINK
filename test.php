<?php
session_start();

echo "User ID: " . $_SESSION['user_id'];
echo "<br>";
echo "Grower ID: " . $_SESSION['grower_id'];
?>