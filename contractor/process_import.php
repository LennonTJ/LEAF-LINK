<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");


// Check import data exists

if (!isset($_SESSION['import_data'])) {

    die("No import data found.");

}


$data = $_SESSION['import_data'];


// Extract session data

$file_id        = $data['file_id'];
$file           = $data['file'];

$grower_no      = $data['grower_no'];
$sale_date      = $data['sale_date'];

$total_bales    = $data['total_bales'];

$presented_mass = $data['presented_mass'];

$sold_bales     = $data['sold_bales'];
$rejected_bales = $data['rejected_bales'];

$sold_mass      = $data['sold_mass'];
$rejected_mass  = $data['rejected_mass'];

$gross_value    = $data['gross_value'];
$net_payment    = $data['net_payment'];

$season         = $data['season'];

$grades         = $data['grades'];



// Start database transaction

mysqli_begin_transaction($conn);



try {


    /*
    ------------------------------------
    INSERT SALE RECORD
    ------------------------------------
    */


    $stmt = mysqli_prepare(
        $conn,

        "INSERT INTO sales

        (
        grower_no,
        sale_date,
        total_bales,
        delivered_mass,
        gross_value,
        net_payment,
        pdf_file,
        season,
        sold_bales,
        rejected_bales,
        sold_mass,
        rejected_mass
        )

        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)

        "

    );


    mysqli_stmt_bind_param(

    $stmt,

    "ssiddsssiidd",

    $grower_no,
    $sale_date,
    $total_bales,
    $presented_mass,
    $gross_value,
    $net_payment,
    $file,
    $season,
    $sold_bales,
    $rejected_bales,
    $sold_mass,
    $rejected_mass

    );


    if(!mysqli_stmt_execute($stmt)){

        throw new Exception(mysqli_error($conn));

    }



    // Get new sale id

    $sale_id = mysqli_insert_id($conn);





    /*
    ------------------------------------
    INSERT BALE DETAILS
    ------------------------------------
    */


    $grade_stmt = mysqli_prepare(

        $conn,

        "INSERT INTO sale_grades

        (
        sale_id,
        grade,
        mass,
        value,
        barcode,
        lot,
        price,
        status
        )

        VALUES (?,?,?,?,?,?,?,?)

        "

    );



    foreach($grades as $g){



        mysqli_stmt_bind_param(

            $grade_stmt,

            "isddssds",

            $sale_id,
            $g['grade'],
            $g['mass'],
            $g['value'],
            $g['barcode'],
            $g['lot'],
            $g['price'],
            $g['status']

        );



        if(!mysqli_stmt_execute($grade_stmt)){

            throw new Exception(mysqli_error($conn));

        }


    }




    /*
    ------------------------------------
    UPDATE UPLOAD STATUS
    ------------------------------------
    */


    $update = mysqli_prepare(

        $conn,

        "UPDATE uploaded_files

        SET

        upload_status='Imported',

        rows_imported=?

        WHERE file_id=?

        "

    );



    mysqli_stmt_bind_param(

        $update,

        "ii",

        $total_bales,
        $file_id

    );



    if(!mysqli_stmt_execute($update)){

        throw new Exception(mysqli_error($conn));

    }




    /*
    ------------------------------------
    COMPLETE TRANSACTION
    ------------------------------------
    */


    mysqli_commit($conn);



    unset($_SESSION['import_data']);



}

catch(Exception $e){


    mysqli_rollback($conn);


    die(
        "Import failed: ".$e->getMessage()
    );


}



?>


<!DOCTYPE html>

<html>

<head>

<title>LeafLink Import</title>

<link rel="stylesheet" href="../assets/css/style.css">

</head>


<body>


<div class="card">


<h2>✅ Import Successful</h2>


<p>
sale sheet has been imported successfully.
</p>


<p>
<strong>Grower:</strong>
<?php echo htmlspecialchars($grower_no); ?>
</p>


<p>
<strong>Total Bales:</strong>
<?php echo $total_bales; ?>
</p>


<p>
<strong>Gross Value:</strong>
$
<?php echo number_format($gross_value,2); ?>
</p>


<p>
<strong>Net Payment:</strong>
$
<?php echo number_format($net_payment,2); ?>
</p>


<br>


<a href="dashboard.php">
Return to Dashboard
</a>


</div>


</body>

</html>