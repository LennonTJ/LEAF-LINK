<?php

session_start();

include "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'grower') {
    header("Location: ../auth/login.php");
    exit();
}

$grower_id = $_SESSION['user_id'];

?>

<form method="POST">

<label>Plant Position</label>

<select name="plant_position">
    <option value="P">Primings</option>
    <option value="X">Lugs</option>
    <option value="C">Cutters</option>
    <option value="L">Leaf</option>
    <option value="T">Tips</option>
</select>

<br><br>

<label>Quality</label>

<select name="quality">
    <option value="Very Poor">Very Poor</option>
    <option value="Poor">Poor</option>
    <option value="Fair">Fair</option>
    <option value="Good">Good</option>
    <option value="Very Good">Very Good</option>
</select>

<br><br>

<label>Estimated Kilograms</label>

<input
type="number"
step="0.01"
name="estimated_kg"
required>

<br><br>

<button type="submit" name="project">
Generate Projection
</button>

</form>