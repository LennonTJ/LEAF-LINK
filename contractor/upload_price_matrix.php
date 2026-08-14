<?php

session_start();

include("../config/database.php");

require("../vendor/autoload.php");

if(isset($_POST['upload'])){

    $folder = "../price_matrix/";

    // Create folder if it doesn't exist
    if(!is_dir($folder)){
        mkdir($folder, 0777, true);
    }

    $filename = time() . "_" . basename($_FILES['matrix_pdf']['name']);

    $filepath = $folder . $filename;

    if(move_uploaded_file($_FILES['matrix_pdf']['tmp_name'], $filepath)){

    $parser = new Smalot\PdfParser\Parser();

    $pdf = $parser->parseFile($filepath);

    $text = $pdf->getText();
    

    // Detect the matrix date
    if(!preg_match('/\d{2}-[A-Z]{3}-\d{4}/', $text, $dateMatch)){
        die("Could not detect the matrix date.");
    }

    $price_date = date("Y-m-d", strtotime($dateMatch[0]));

    // Delete any existing prices for this date
    $delete = mysqli_prepare(
        $conn,
        "DELETE FROM price_matrix WHERE price_date=?"
    );

    mysqli_stmt_bind_param($delete, "s", $price_date);
    mysqli_stmt_execute($delete);

    // Find every Grade + Price pair
    preg_match_all(
    '/([A-Z0-9]{2,6})\s*([0-9]+\.[0-9]{2})/',
    $text,
    $matches,
    PREG_SET_ORDER
    );

    // Prepare insert statement
    $insert = mysqli_prepare(
        $conn,
        "INSERT INTO price_matrix
        (grade, average_price, price_date)
        VALUES (?, ?, ?)"
    );

    $count = 0;

    $count = 0;

    foreach($matches as $match){

        $grade = trim($match[1]);
        $price = (float)$match[2];

        mysqli_stmt_bind_param(
            $insert,
            "sds",
            $grade,
            $price,
            $price_date
        );

        if(mysqli_stmt_execute($insert)){
            $count++;
        }

    }

    echo "<h2> TIMB Daily Average Price Matrix Imported Successfully</h2>";
    echo "<p><strong>Date:</strong> ".$price_date."</p>";
    echo "<p><strong>Total Grades Imported:</strong> ".$count."</p>";

}else{

    echo " Upload failed.";

}

}

?>

<doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upload Price Matrix</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="header">
    <h1>LeafLink</h1>
    <p> Price Matrix Import</p>
</div>


<div class="layout">

    <div class="sidebar">
        <h3>Contractor</h3>
        <hr>
        <a href="dashboard.php">Dashboard</a>
    </div>

    <div class="content">

        <div class="card">

            <h2>Upload TIMB Daily Average Price Matrix</h2>

            <form method="POST" enctype="multipart/form-data">

                <input
                    type="file"
                    name="matrix_pdf"
                    accept=".pdf"
                    required>

                <button
                    type="submit"
                    name="upload">

                    Upload Matrix

                </button>

            </form>
        </div>

    </div>

</div>           
</html>