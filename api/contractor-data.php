<?php

include("../config/database.php");

session_start();

header('Content-Type: application/json');


if(!isset($_SESSION['user_id'])){
    echo json_encode([
        "error"=>"Unauthorized"
    ]);
    exit();
}


$contractor_id = $_SESSION['contractor_id'] ?? null;


/*
|--------------------------------------------------------------------------
| FIND CONTRACTOR ID
|--------------------------------------------------------------------------
*/


if(!$contractor_id){

    $uid = $_SESSION['user_id'];

    // Try users table first
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



        /*
        |--------------------------------------------------------------------------
        | FALLBACK: username -> contractor_code
        |--------------------------------------------------------------------------
        */


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


            $cres=mysqli_stmt_get_result($cstmt);


            if($crow=mysqli_fetch_assoc($cres)){

                $contractor_id=$crow['contractor_id'];

            }


        }

    }

}



if(!$contractor_id){

    echo json_encode([
        "error"=>"No contractor linked"
    ]);

    exit();

}

if(!$contractor_id){

    $uid=$_SESSION['user_id'];

    $stmt=mysqli_prepare(
        $conn,
        "SELECT contractor_id FROM users WHERE user_id=? LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt,"i",$uid);

    mysqli_stmt_execute($stmt);

    $result=mysqli_stmt_get_result($stmt);

    $row=mysqli_fetch_assoc($result);

    $contractor_id=$row['contractor_id'] ?? null;

}


if(!$contractor_id){

    echo json_encode([
        "error"=>"No contractor linked"
    ]);

    exit();

}



$action=$_GET['action'] ?? '';

function getRecoverySummary($contractor_id){

global $conn;


$sql="
SELECT

g.grower_id,

COALESCE(SUM(s.gross_value),0) AS revenue,

COALESCE(g.total_debt,0) AS debt


FROM contracts c


JOIN growers g

ON c.grower_id = g.grower_id


LEFT JOIN sales s

ON g.grower_no = s.grower_no


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



$high=0;
$medium=0;
$low=0;



while($row=mysqli_fetch_assoc($result)){


$revenue=(float)$row['revenue'];

$debt=(float)$row['debt'];


$recovery=0;


if($debt>0){

    $recovery=
    ($revenue/$debt)*100;

}



if($recovery < 50){

    $high++;

}
elseif($recovery < 100){

    $medium++;

}
else{

    $low++;

}



}



return [

"high"=>$high,

"medium"=>$medium,

"low"=>$low

];


}


function getMetrics($contractor_id){

global $conn;

$sql="
SELECT

COUNT(DISTINCT c.grower_id) AS growers,

COALESCE(SUM(s.sold_mass),0) AS total_kg,

COALESCE(SUM(s.gross_value),0) AS revenue,

COALESCE(SUM(s.sold_bales),0) AS bales,

COALESCE(SUM(s.rejected_bales),0) AS rejected


FROM contracts c

JOIN growers g
ON c.grower_id = g.grower_id


LEFT JOIN sales s
ON g.grower_no = s.grower_no


WHERE c.contractor_id=?

";


$stmt=mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
$stmt,
"i",
$contractor_id
);

mysqli_stmt_execute($stmt);


$result=mysqli_stmt_get_result($stmt);


$data=mysqli_fetch_assoc($result);



$price=0;

if($data['total_kg']>0){

    $price=
    round(
        $data['revenue']/$data['total_kg'],
        2
    );

}



return [

"active_growers"=>(int)$data['growers'],

"total_kg"=>(float)$data['total_kg'],

"total_revenue"=>(float)$data['revenue'],

"average_price"=>$price,

"total_bales"=>(int)$data['bales'],

"rejected_bales"=>(int)$data['rejected']

];


}



switch($action){


case "metrics":

echo json_encode(
getMetrics($contractor_id)
);

break;

case "recovery_risk":

echo json_encode(
    getRecoveryRisk($contractor_id)
);

break;

case "recovery_summary":

echo json_encode(
    getRecoverySummary($contractor_id)
);

break;


default:

echo json_encode([
"error"=>"Invalid action"
]);

}



?>