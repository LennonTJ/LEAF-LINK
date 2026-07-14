<?php

session_start();
include("../config/database.php");

function verifyStoredPassword(string $password, string $storedPassword): bool
{
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

if ($user = mysqli_fetch_assoc($result))
{
    if (verifyStoredPassword($password, $user['password_hash']))
    {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['user_type'];

        // Get grower ID if this is a grower account
        if ($user['user_type'] == 'grower')
        {
            $sql2 = "SELECT grower_id
                     FROM growers
                     WHERE user_id = ?";

            $stmt2 = mysqli_prepare($conn, $sql2);

            mysqli_stmt_bind_param(
                $stmt2,
                "i",
                $user['user_id']
            );

            mysqli_stmt_execute($stmt2);

            $result2 = mysqli_stmt_get_result($stmt2);

            if ($grower = mysqli_fetch_assoc($result2))
            {
                $_SESSION['grower_id'] =
                    $grower['grower_id'];
            }
        }

        switch ($user['user_type'])
        {
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