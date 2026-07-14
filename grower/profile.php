<?php

session_start();
include("../config/database.php");

$id = $_GET['id'];

$sql = "SELECT * FROM growers WHERE grower_id=?";

$stmt = mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

$result=mysqli_stmt_get_result($stmt);

$grower=mysqli_fetch_assoc($result);

?>

<!DOCTYPE html>

<html>

<head>

<title>Grower Profile</title>

</head>

<body>

<h1>LeafLink</h1>

<h2>Grower Profile</h2>

<hr>

<p><strong>Grower Number:</strong>
<?php echo $grower['grower_no']; ?></p>

<p><strong>Name:</strong>
<?php echo $grower['first_name']." ".$grower['last_name']; ?></p>

<p><strong>National ID:</strong>
<?php echo $grower['national_id']; ?></p>

<p><strong>Phone:</strong>
<?php echo $grower['phone']; ?></p>

<p><strong>Province:</strong>
<?php echo $grower['province']; ?></p>

<p><strong>District:</strong>
<?php echo $grower['district']; ?></p>

<p><strong>Farm:</strong>
<?php echo $grower['farm_name']; ?></p>

<p><strong>Hectares:</strong>
<?php echo $grower['hectares']; ?></p>

<hr>

<h3>Current Contract</h3>

<p>Smoke Merchant Tobacco</p>

<p>Season 2026</p>

<p>Status:
<b style="color:green;">ACTIVE</b></p>

<hr>

<h3>Financial Summary</h3>

<p>Inputs Received :
$450</p>

<p>Sales :
$1840</p>

<p>Balance :
$1390</p>

<br>

<a href="view.php">

← Back to Growers

</a>

</body>

</html>