<?php

session_start();

if(!isset($_SESSION['user_id'])){
header("Location: ../auth/login.php");
exit();
}


include("../config/database.php");

$contractor_id = $_SESSION['contractor_id'] ?? null;

if(!$contractor_id){

    $uid = $_SESSION['user_id'];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT contractor_id, username
         FROM users
         WHERE user_id=?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt,"i",$uid);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if($user){

        $contractor_id = $user['contractor_id'];

        // Fallback using contractor_code
        if(!$contractor_id && !empty($user['username'])){

            $cstmt = mysqli_prepare(
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

            $cres = mysqli_stmt_get_result($cstmt);

            if($crow = mysqli_fetch_assoc($cres)){
                $contractor_id = $crow['contractor_id'];
            }
        }
    }
}

if(!$contractor_id){
    die("Contractor not linked.");
}



$sql="
SELECT

g.grower_no,
g.first_name,
g.last_name,

g.total_debt,

COALESCE(SUM(s.net_payment),0) recovered


FROM growers g


JOIN contracts c

ON g.grower_id=c.grower_id


LEFT JOIN sales s

ON g.grower_no=s.grower_no


WHERE c.contractor_id=?


GROUP BY g.grower_id

";



$stmt=mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
$stmt,
"i",
$contractor_id
);


mysqli_stmt_execute($stmt);


$result=mysqli_stmt_get_result($stmt);


?>


<!DOCTYPE html>
<html>

<head>

<title>Risk Analysis</title>

<link rel="stylesheet" href="../assets/css/style.css">

</head>


<body>


<div class="header">

<h1>LeafLink</h1>

<h2>Recovery Risk Analysis</h2>

</div>



<div class="content">


<div class="card">


<table>


<tr>

<th>Grower</th>
<th>Debt</th>
<th>Recovered</th>
<th>Risk</th>

</tr>



<?php while($r=mysqli_fetch_assoc($result)){


$debt=$r['total_debt'];

$paid=$r['recovered'];


$percent=0;

if($debt>0){

$percent=($paid/$debt)*100;

}



if($percent>=70){

$risk="LOW";

}

elseif($percent>=30){

$risk="MEDIUM";

}

else{

$risk="HIGH";

}


?>


<tr>

<td>
<?php echo $r['grower_no']." ".$r['first_name']; ?>
</td>


<td>
$<?php echo number_format($debt,2); ?>
</td>


<td>
$<?php echo number_format($paid,2); ?>
</td>


<td>
<?php echo $risk; ?> 
(<?php echo round($percent); ?>%)
</td>


</tr>


<?php } ?>


</table>


</div>


</div>


</body>

</html>