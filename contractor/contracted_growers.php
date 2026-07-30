<?php

session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");


/*
GET CONTRACTOR ID
*/
$contractor_id = $_SESSION['contractor_id'] ?? null;


/*
FIND CONTRACTOR ID
*/

if(!$contractor_id){

    $uid = $_SESSION['user_id'];


    $stmt = mysqli_prepare(
        $conn,
        "SELECT contractor_id, username
         FROM users
         WHERE user_id=?
         LIMIT 1"
    );


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $uid
    );


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result($stmt);


    $user = mysqli_fetch_assoc($result);



    if($user){

        $contractor_id = $user['contractor_id'] ?? null;



        // fallback username -> contractor_code

        if(!$contractor_id && !empty($user['username'])){


            $cstmt=mysqli_prepare(
                $conn,
                "SELECT contractor_id
                 FROM contractors
                 WHERE contractor_code=?
                 LIMIT 1"
            );


            mysqli_stmt_bind_param(
                $cstmt,
                "s",
                $user['username']
            );


            mysqli_stmt_execute($cstmt);


            $cres=mysqli_stmt_get_result($cstmt);


            if($crow=mysqli_fetch_assoc($cres)){

                $contractor_id=$crow['contractor_id'];

            }

        }

    }

}



if(!$contractor_id){

    die("Contractor not linked");

}



/*
SEARCH
*/

$search=$_GET['search'] ?? '';



$sql="
SELECT

g.grower_no,
g.first_name,
g.last_name,

c.status,

COALESCE(SUM(s.sold_mass),0) kg,

COALESCE(SUM(s.gross_value),0) revenue


FROM contracts c

JOIN growers g
ON c.grower_id=g.grower_id


LEFT JOIN sales s
ON g.grower_no=s.grower_no


WHERE c.contractor_id=?


AND (
g.grower_no LIKE ?
OR g.first_name LIKE ?
OR g.last_name LIKE ?
)


GROUP BY g.grower_id

ORDER BY kg DESC

";


$stmt=mysqli_prepare($conn,$sql);


$searchTerm="%".$search."%";


mysqli_stmt_bind_param(
$stmt,
"isss",
$contractor_id,
$searchTerm,
$searchTerm,
$searchTerm
);


mysqli_stmt_execute($stmt);


$result=mysqli_stmt_get_result($stmt);



?>

<!DOCTYPE html>
<html>

<head>

<title>Contracted Growers</title>

<link rel="stylesheet" href="../assets/css/style.css">

</head>


<body>


<div class="header">

<h1>LeafLink</h1>

<h2>Contracted Growers</h2>

</div>



<div class="content">


<div class="card">


<h2>Grower List</h2>


<form method="GET">

<input 
type="text"
name="search"
placeholder="Search grower..."
value="<?php echo htmlspecialchars($search); ?>"
>


<button>
Search
</button>


</form>



<table>


<tr>

<th>Grower No</th>
<th>Name</th>
<th>Status</th>
<th>Total Kg</th>
<th>Revenue</th>

</tr>



<?php while($g=mysqli_fetch_assoc($result)){ ?>


<tr>

<td>
<?php echo $g['grower_no']; ?>
</td>


<td>
<?php echo $g['first_name']." ".$g['last_name']; ?>
</td>


<td>
<?php echo $g['status']; ?>
</td>


<td>
<?php echo number_format($g['kg'],2); ?> kg
</td>


<td>
$<?php echo number_format($g['revenue'],2); ?>
</td>


</tr>


<?php } ?>


</table>


</div>


</div>


</body>

</html>