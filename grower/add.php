<?php
session_start();
include("../config/database.php");
?>

<!DOCTYPE html>
<html>

<head>

<title>Add Grower</title>

</head>

<body>

<h2>Register New Grower</h2>

<form action="save.php" method="POST">

Grower Number

<br>

<input
type="text"
name="grower_no"
required>

<br><br>

National ID

<br>

<input
type="text"
name="national_id">

<br><br>

First Name

<br>

<input
type="text"
name="first_name"
required>

<br><br>

Last Name

<br>

<input
type="text"
name="last_name"
required>

<br><br>

Phone Number

<br>

<input
type="text"
name="phone">

<br><br>

Province

<br>

<input
type="text"
name="province">

<br><br>

District

<br>

<input
type="text"
name="district">

<br><br>

Ward

<br>

<input
type="text"
name="ward">

<br><br>

Village

<br>

<input
type="text"
name="village">

<br><br>

Farm Name

<br>

<input
type="text"
name="farm_name">

<br><br>

Hectares

<br>

<input
type="number"
step="0.01"
name="hectares">

<br><br>

<button type="submit">

Save Grower

</button>

</form>

</body>

</html>