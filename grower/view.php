
<?php
$search = "";

if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

session_start();
include("../config/database.php");

$sql = "

SELECT *

FROM growers

WHERE

grower_no LIKE ?
OR national_id LIKE ?
OR first_name LIKE ?
OR last_name LIKE ?

ORDER BY first_name

";

$stmt = mysqli_prepare($conn, $sql);

$searchTerm = "%" . $search . "%";

mysqli_stmt_bind_param(
    $stmt,
    "ssss",
    $searchTerm,
    $searchTerm,
    $searchTerm,
    $searchTerm
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html>

<head>

<title>Growers</title>

</head>

<body>

<h2>Registered Growers</h2>

<a href="add.php">

Register Grower

</a>

<br><br>

<form method="GET">

    <input
        type="text"
        name="search"
        placeholder="Search Grower..."
        value="<?php echo $search; ?>">

    <button type="submit">
        Search
    </button>

</form>

<br>

<table border="1"
cellpadding="8">

<tr>

<th>Grower No</th>

<th>Name</th>

<th>Phone</th>

<th>District</th>

<th>Action</th>

</tr>

<?php

while($row=mysqli_fetch_assoc($result))
{

?>

<tr>

<td>

<?php
echo $row['grower_no'];
?>

</td>

<td>

<?php

echo

$row['first_name']

." ".

$row['last_name'];

?>

</td>

<td>

<?php
echo $row['phone'];
?>

</td>

<td>

<?php
echo $row['district'];
?>

</td>

<td>

<a href="profile.php?id=<?php

echo $row['grower_id'];

?>">

View

</a>

</td>

</tr>

<?php

}

?>

</table>

</body>

</html>