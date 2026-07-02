<?php

include("../config/database.php");

$role = $_GET['role'] ?? '';

?>

<!DOCTYPE html>

<html>

<head>

    <title>LeafLink Login</title>

</head>

<body>

<h1>🍃 LeafLink</h1>

<h3>

<?php

echo ucfirst($role);

?>

 Login

</h3>

<form action="authenticate.php" method="POST">

<input
type="hidden"
name="role"
value="<?php echo $role; ?>">

Username

<br>

<input
type="text"
name="username"
required>

<br><br>

Password

<br>

<input
type="password"
name="password"
required>

<br><br>

<button type="submit">

Login

</button>

</form>

</body>

</html>