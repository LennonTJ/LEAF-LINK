<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

if (!isset($_GET['file_id'])) {
    die("No uploaded file selected.");
}

$file_id = intval($_GET['file_id']);

/*
-----------------------------------------
GET FILE DETAILS
-----------------------------------------
*/

$stmt = mysqli_prepare($conn,"
SELECT *
FROM uploaded_files
WHERE file_id=?
LIMIT 1
");

mysqli_stmt_bind_param($stmt,"i",$file_id);

mysqli_stmt_execute($stmt);

$fileResult = mysqli_stmt_get_result($stmt);

$file = mysqli_fetch_assoc($fileResult);

if(!$file){
    die("Uploaded file not found.");
}

/*
--------------------------------------------------
TEMPORARY DATA

This will later come from the PDF parser.

For now we simulate one TIMB sale sheet.
--------------------------------------------------
*/

$grower_no="G0001";

$sale_date=date("Y-m-d");

$bales=[];

$bales[]=[
"barcode"=>"302163507R",
"lot"=>13,
"mass"=>43,
"grade"=>"X4OK",
"price"=>1.65,
"value"=>70.95,
"rejected"=>0
];

$bales[]=[
"barcode"=>"302163508S",
"lot"=>10,
"mass"=>113,
"grade"=>"P4MD",
"price"=>1.40,
"value"=>158.20,
"rejected"=>0
];

$bales[]=[
"barcode"=>"302163509T",
"lot"=>11,
"mass"=>52,
"grade"=>"X4OK",
"price"=>1.65,
"value"=>85.80,
"rejected"=>0
];

$bales[]=[
"barcode"=>"302163511M",
"lot"=>12,
"mass"=>45,
"grade"=>"P5O",
"price"=>1.00,
"value"=>45.00,
"rejected"=>0
];

/*
-----------------------------------------
TOTALS
-----------------------------------------
*/

$total_bales=0;
$total_mass=0;
$gross_value=0;

foreach($bales as $b){

$total_bales++;

$total_mass+=$b['mass'];

$gross_value+=$b['value'];

}

$average_price=0;

if($total_mass>0){

$average_price=$gross_value/$total_mass;

}

?>
<!DOCTYPE html>

<html>

<head>

<title>LeafLink - Import Preview</title>

<link rel="stylesheet"
href="../assets/css/style.css">

</head>

<body>

<div class="header">

<h1>LeafLink</h1>

<p>TIMB Sale Import Preview</p>

</div>

<div class="layout">

<div class="sidebar">

<h3>Contractor</h3>

<hr>

<a href="dashboard.php">Dashboard</a>

<a href="upload_sales.php">Upload Sales</a>

<hr>

<a href="../logout.php">Logout</a>

</div>

<div class="content">

<div class="card">

<h2>Uploaded File</h2>

<p>

<strong>File Name:</strong>

<?php echo htmlspecialchars($file['original_filename']); ?>

</p>

<p>

<strong>Status:</strong>

<?php echo htmlspecialchars($file['upload_status']); ?>

</p>

<p>

<strong>Uploaded:</strong>

<?php echo $file['uploaded_at']; ?>

</p>

</div>

<div class="card">

<h2>Sale Information</h2>

<p>

<strong>Grower Number:</strong>

<?php echo $grower_no; ?>

</p>

<p>

<strong>Sale Date:</strong>

<?php echo $sale_date; ?>

</p>

</div>

<div class="card">

<h2>Purchased Bales</h2>

<table border="1"
cellpadding="8"
width="100%">

<tr>

<th>Barcode</th>

<th>Lot</th>

<th>Mass</th>

<th>Grade</th>

<th>Price/kg</th>

<th>Value</th>

<th>Rejected</th>

</tr>

<?php

foreach($bales as $b){

?>

<tr>

<td><?php echo $b['barcode']; ?></td>

<td><?php echo $b['lot']; ?></td>

<td><?php echo number_format($b['mass'],2); ?></td>

<td><?php echo $b['grade']; ?></td>

<td>$<?php echo number_format($b['price'],2); ?></td>

<td>$<?php echo number_format($b['value'],2); ?></td>

<td>

<?php

echo $b['rejected']
?
"YES"
:
"NO";

?>

</td>

</tr>

<?php

}

?>

</table>

</div>
<div class="card">

<h2>Sale Summary</h2>

<p>
<strong>Total Bales:</strong>
<?php echo $total_bales; ?>
</p>

<p>
<strong>Total Mass:</strong>
<?php echo number_format($total_mass,2); ?> kg
</p>

<p>
<strong>Gross Value:</strong>
$<?php echo number_format($gross_value,2); ?>
</p>

<p>
<strong>Average Price:</strong>
$<?php echo number_format($average_price,2); ?>/kg
</p>

</div>


<div class="card">

<h2>Ready to Import</h2>

<p>

The information above has not yet been saved into the database.

Click <strong>Confirm Import</strong> to:

</p>

<ul>

<li>Create the Sale record</li>

<li>Create all Purchased Bale records</li>

<li>Create the financial transaction</li>

<li>Recover grower debt</li>

<li>Mark this upload as Imported</li>

</ul>

<form
method="POST"
action="confirm_import.php">

<input
type="hidden"
name="file_id"
value="<?php echo $file_id; ?>">

<button
type="submit"
class="btn">

Confirm Import

</button>

</form>

</div>

</div>

</div>

</body>

</html>