<?php

session_start();
include("../config/database.php");

$grower_no   = $_POST['grower_no'];
$national_id = $_POST['national_id'];
$first_name  = $_POST['first_name'];
$last_name   = $_POST['last_name'];
$phone       = $_POST['phone'];
$province    = $_POST['province'];
$district    = $_POST['district'];
$ward        = $_POST['ward'];
$village     = $_POST['village'];
$farm_name   = $_POST['farm_name'];
$hectares    = $_POST['hectares'];

$sql = "INSERT INTO growers
(
grower_no,
national_id,
first_name,
last_name,
phone,
province,
district,
ward,
village,
farm_name,
hectares
)

VALUES

(?,?,?,?,?,?,?,?,?,?,?)";

$stmt = mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(

$stmt,

"ssssssssssd",

$grower_no,
$national_id,
$first_name,
$last_name,
$phone,
$province,
$district,
$ward,
$village,
$farm_name,
$hectares

);

if(mysqli_stmt_execute($stmt))
{

    header("Location:view.php?success=1");
    exit();

}
else
{

    echo "Error Saving Grower.";

}