<?php

session_start();
include("../config/database.php");

function verifyStoredPassword(string $password, string $storedPassword): bool {
    if ($storedPassword === '') {
        return false;
    }

    if (password_verify($password, $storedPassword)) {
        return true;
    }

    return hash_equals($storedPassword, $password);
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role = trim($_POST['role'] ?? '');

$sql = "SELECT * FROM users
        WHERE username = ?
        AND user_type = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ss", $username, $role);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {

    if (verifyStoredPassword($password, $user['password_hash'])) {

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['user_type'];

        switch ($user['user_type']) {

            case 'admin':
                header("Location: ../admin/dashboard.php");
                break;

            case 'contractor':
                header("Location: ../contractor/dashboard.php");
                break;

            case 'grower':
                header("Location: ../grower/dashboard.php");
                break;
        }

        exit();

    }

}

echo "Invalid username or password.";

?>