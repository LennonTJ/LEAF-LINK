<?php

session_start();

include("../config/database.php");

require("../vendor/autoload.php");


/*
|--------------------------------------------------------------------------
| CONFIG
|--------------------------------------------------------------------------
*/

$TIMB_DAILY_MATRIX_URL =
    "https://www.timb.co.zw/grading-oci/automatrix_daily.php";

/*
| TIMB normally has 100+ grades.
| Anything dramatically below this is treated as an invalid scrape.
*/
$MIN_VALID_RECORDS = 5;
$message = "";
$message_type = "";


/*
|--------------------------------------------------------------------------
| HELPER: Get latest stored matrix date
|--------------------------------------------------------------------------
*/

function getLatestStoredMatrixDate($conn)
{
    $result = mysqli_query(
        $conn,
        "SELECT MAX(price_date) AS latest_date
         FROM price_matrix"
    );

    if ($result) {

        $row = mysqli_fetch_assoc($result);

        return $row['latest_date'] ?? null;
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| HELPER: Fetch URL
|--------------------------------------------------------------------------
*/

function fetchUrl($url)
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_FOLLOWLOCATION => true,

        CURLOPT_MAXREDIRS => 5,

        CURLOPT_CONNECTTIMEOUT => 15,

        CURLOPT_TIMEOUT => 30,

        CURLOPT_USERAGENT =>
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) " .
            "AppleWebKit/537.36 (KHTML, like Gecko) " .
            "Chrome/151.0 Safari/537.36",

        CURLOPT_HTTPHEADER => [

            "Accept: text/html,application/xhtml+xml," .
            "application/xml;q=0.9,*/*;q=0.8",

            "Accept-Language: en-US,en;q=0.9"

        ],

        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,

    ]);


    $data = curl_exec($ch);

    $error = curl_error($ch);

    $http_code =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

    curl_close($ch);


    if (
        $data === false ||
        $http_code >= 400
    ) {

        return [

            "success" => false,

            "data" => "",

            "error" =>
                $error !== ""
                    ? $error
                    : "HTTP error " . $http_code

        ];

    }


    return [

        "success" => true,

        "data" => $data,

        "error" => ""

    ];
}


/*
|--------------------------------------------------------------------------
| HELPER: Convert database date to TIMB date
|--------------------------------------------------------------------------
|
| Database:
|
| 2026-08-16
|
| TIMB:
|
| 16-Aug-2026
|
|--------------------------------------------------------------------------
*/

function formatTIMBDate($date)
{
    return date(
        "d-M-Y",
        strtotime($date)
    );
}


/*
|--------------------------------------------------------------------------
| HELPER: Parse TIMB HTML
|--------------------------------------------------------------------------
|
| TIMB daily page contains pairs such as:
|
| <td>C2L</td>
| <td>3.60</td>
|
| <td>L2OA</td>
| <td>4.50</td>
|
|--------------------------------------------------------------------------
*/

function parseTIMBMatrix($html)
{
    if (trim($html) === "") {
        return [];
    }

    libxml_use_internal_errors(true);

    $dom = new DOMDocument();

    @$dom->loadHTML(
        '<?xml encoding="UTF-8">' . $html
    );

    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    /*
    |--------------------------------------------------------------------------
    | Find table rows
    |--------------------------------------------------------------------------
    */

    $rows = $xpath->query("//tr");

    if (!$rows || $rows->length === 0) {
        return [];
    }

    $records = [];

    $seen = [];

    foreach ($rows as $row) {

        /*
        |--------------------------------------------------------------------------
        | Get cells from this row only
        |--------------------------------------------------------------------------
        */

        $cells = $xpath->query("./td", $row);

        if (!$cells || $cells->length < 2) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Read first two cells
        |--------------------------------------------------------------------------
        */

        $grade = trim(
            preg_replace(
                '/\s+/',
                ' ',
                $cells->item(0)->textContent
            )
        );

        $priceText = trim(
            preg_replace(
                '/\s+/',
                ' ',
                $cells->item(1)->textContent
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Ignore table headers
        |--------------------------------------------------------------------------
        */

        if (
            strtoupper($grade) === "GRADE" ||
            strtoupper($priceText) === "AVERAGE PRICE"
        ) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Validate grade
        |--------------------------------------------------------------------------
        */

        if (
            !preg_match(
                '/^[A-Z0-9]{2,10}$/i',
                $grade
            )
        ) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Validate price
        |--------------------------------------------------------------------------
        */

        $priceText = str_replace(
            ',',
            '',
            $priceText
        );

        if (!is_numeric($priceText)) {
            continue;
        }

        $price = (float)$priceText;

        if ($price < 0) {
            continue;
        }

        $grade = strtoupper($grade);

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate grades
        |--------------------------------------------------------------------------
        */

        if (isset($seen[$grade])) {
            continue;
        }

        $seen[$grade] = true;

        /*
        |--------------------------------------------------------------------------
        | Store record
        |--------------------------------------------------------------------------
        */

        $records[] = [
            "grade" => $grade,
            "price" => $price
        ];
    }

    return $records;
}



/*
|--------------------------------------------------------------------------
| HELPER: Validate TIMB matrix
|--------------------------------------------------------------------------
*/

function validateTIMBMatrix(
    $records,
    $minimumRecords
) {

    if (
        !is_array($records)
    ) {

        return false;

    }


    if (
        count($records) < $minimumRecords
    ) {

        return false;

    }


    foreach (
        $records as $record
    ) {

        if (
            !isset($record['grade']) ||
            !isset($record['price'])
        ) {

            return false;

        }


        if (
            $record['grade'] === "" ||
            !is_numeric($record['price']) ||
            $record['price'] < 0
        ) {

            return false;

        }

    }


    return true;
}


/*
|--------------------------------------------------------------------------
| HELPER: Fetch one TIMB daily matrix
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| HELPER: Fetch one TIMB daily matrix
|--------------------------------------------------------------------------
*/

function fetchTIMBMatrixForDate($date, $minimumRecords)
{
    global $TIMB_DAILY_MATRIX_URL;

    /*
    |--------------------------------------------------------------------------
    | Convert date to TIMB format
    |--------------------------------------------------------------------------
    |
    | Example:
    | 2026-08-14
    | becomes:
    | 14-Aug-2026
    |
    |--------------------------------------------------------------------------
    */

    $timbDate = formatTIMBDate($date);

    $url =
        $TIMB_DAILY_MATRIX_URL .
        "?selDate=" .
        urlencode($timbDate);


    /*
    |--------------------------------------------------------------------------
    | Request TIMB
    |--------------------------------------------------------------------------
    */

    $response = fetchUrl($url);


    /*
    |--------------------------------------------------------------------------
    | Request failed
    |--------------------------------------------------------------------------
    */

    if (!$response['success']) {

        return [

            "success" => false,

            "records" => [],

            "url" => $url,

            "error" => $response['error']

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | Parse the returned HTML
    |--------------------------------------------------------------------------
    */

    $records =
        parseTIMBMatrix(
            $response['data']
        );


    /*
    |--------------------------------------------------------------------------
    | Validate the matrix
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | We do NOT require 100+ grades.
    |
    | Near the end of the tobacco season TIMB can publish
    | matrices containing only a small number of grades.
    |
    | Therefore MIN_VALID_RECORDS is currently 5.
    |
    |--------------------------------------------------------------------------
    */

    if (
        !validateTIMBMatrix(
            $records,
            $minimumRecords
        )
    ) {

        return [

            "success" => false,

            "records" => [],

            "url" => $url,

            "error" =>
                "TIMB page did not contain a valid price matrix. " .
                "Extracted " .
                count($records) .
                " valid grade(s)."

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | Valid matrix
    |--------------------------------------------------------------------------
    */

    return [

        "success" => true,

        "records" => $records,

        "url" => $url,

        "error" => ""

    ];
}


/*
|--------------------------------------------------------------------------
| HELPER: Save TIMB matrix safely
|--------------------------------------------------------------------------
*/

function saveTIMBMatrix(
    $conn,
    $records,
    $priceDate
) {

    if (
        !$records ||
        count($records) === 0
    ) {

        return [

            "success" => false,

            "count" => 0,

            "error" =>
                "No records supplied."

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | Start transaction
    |--------------------------------------------------------------------------
    */

    mysqli_begin_transaction($conn);


    try {


        /*
        |--------------------------------------------------------------------------
        | Delete only the matrix being replaced.
        |
        | IMPORTANT:
        | This happens INSIDE the transaction.
        |--------------------------------------------------------------------------
        */

        $delete =
            mysqli_prepare(
                $conn,
                "DELETE FROM price_matrix
                 WHERE price_date=?"
            );


        if (!$delete) {

            throw new Exception(
                "Could not prepare matrix replacement."
            );

        }


        mysqli_stmt_bind_param(
            $delete,
            "s",
            $priceDate
        );


        if (
            !mysqli_stmt_execute($delete)
        ) {

            throw new Exception(
                "Could not replace matrix."
            );

        }


        mysqli_stmt_close($delete);


        /*
        |--------------------------------------------------------------------------
        | Insert new matrix
        |--------------------------------------------------------------------------
        */

        $insert =
            mysqli_prepare(
                $conn,
                "INSERT INTO price_matrix
                (grade, average_price, price_date)
                VALUES (?, ?, ?)"
            );


        if (!$insert) {

            throw new Exception(
                "Could not prepare matrix insert."
            );

        }


        $count = 0;


        foreach (
            $records as $record
        ) {

            $grade =
                $record['grade'];


            $price =
                (float)$record['price'];


            mysqli_stmt_bind_param(
                $insert,
                "sds",
                $grade,
                $price,
                $priceDate
            );


            if (
                !mysqli_stmt_execute($insert)
            ) {

                throw new Exception(
                    "A grade could not be inserted."
                );

            }


            $count++;

        }


        mysqli_stmt_close($insert);


        /*
        |--------------------------------------------------------------------------
        | Verify imported record count
        |--------------------------------------------------------------------------
        */

        $verify =
            mysqli_prepare(
                $conn,
                "SELECT COUNT(*) AS total
                 FROM price_matrix
                 WHERE price_date=?"
            );


        if (!$verify) {

            throw new Exception(
                "Could not verify imported matrix."
            );

        }


        mysqli_stmt_bind_param(
            $verify,
            "s",
            $priceDate
        );


        mysqli_stmt_execute(
            $verify
        );


        $result =
            mysqli_stmt_get_result(
                $verify
            );


        $row =
            mysqli_fetch_assoc(
                $result
            );


        mysqli_stmt_close(
            $verify
        );


        $savedCount =
            (int)(
                $row['total'] ?? 0
            );


        if (
            $savedCount < count($records)
        ) {

            throw new Exception(
                "Matrix verification failed."
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Everything succeeded
        |--------------------------------------------------------------------------
        */

        mysqli_commit($conn);


        return [

            "success" => true,

            "count" => $savedCount,

            "error" => ""

        ];


    } catch (Throwable $e) {


        /*
        |--------------------------------------------------------------------------
        | Anything failed → restore previous database state
        |--------------------------------------------------------------------------
        */

        mysqli_rollback($conn);


        return [

            "success" => false,

            "count" => 0,

            "error" =>
                $e->getMessage()

        ];

    }

}


/*
|--------------------------------------------------------------------------
| AUTOMATIC TIMB UPDATE
|--------------------------------------------------------------------------
*/

if (
    isset($_POST['update_timb'])
) {


    /*
    |--------------------------------------------------------------------------
    | Find latest matrix already stored
    |--------------------------------------------------------------------------
    */

    $latest_stored =
        getLatestStoredMatrixDate(
            $conn
        );


    /*
    |--------------------------------------------------------------------------
    | Start at today's date
    |--------------------------------------------------------------------------
    */

    $today =
        date("Y-m-d");


    $currentTimestamp =
        strtotime($today);


    /*
    |--------------------------------------------------------------------------
    | If we already have a matrix, stop once we reach it.
    |
    | If we have no matrix yet, check the last 30 days.
    |--------------------------------------------------------------------------
    */

    if (
        $latest_stored !== null
    ) {

        $stopTimestamp =
            strtotime(
                $latest_stored
            );

    } else {

        $stopTimestamp =
            strtotime(
                "-30 days",
                $currentTimestamp
            );

    }


    $found_matrix =
        null;


    /*
    |--------------------------------------------------------------------------
    | Search newest → oldest
    |--------------------------------------------------------------------------
    */

    for (
        $timestamp = $currentTimestamp;

        $timestamp >= $stopTimestamp;

        $timestamp = strtotime(
            "-1 day",
            $timestamp
        )
    ) {


        $candidateDate =
            date(
                "Y-m-d",
                $timestamp
            );


        /*
        |--------------------------------------------------------------------------
        | Never re-import an already stored date
        |--------------------------------------------------------------------------
        */

        if (
            $latest_stored !== null &&
            $candidateDate <= $latest_stored
        ) {

            break;

        }


        /*
        |--------------------------------------------------------------------------
        | Ask TIMB for this date
        |--------------------------------------------------------------------------
        */

        $result =
            fetchTIMBMatrixForDate(
                $candidateDate,
                $MIN_VALID_RECORDS
            );


        /*
        |--------------------------------------------------------------------------
        | Valid matrix found
        |--------------------------------------------------------------------------
        */

        if (
            $result['success']
        ) {

            $found_matrix = [

                "date" =>
                    $candidateDate,

                "records" =>
                    $result['records'],

                "url" =>
                    $result['url']

            ];


            /*
            | We search newest → oldest,
            | so the first valid result is the newest.
            */

            break;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | No newer matrix
    |--------------------------------------------------------------------------
    */

    if (
        $found_matrix === null
    ) {


        $message_type =
            "warning";


        if (
            $latest_stored
        ) {

            $message =
                "⚠️ No newer valid TIMB price matrix " .
                "is currently available. " .
                "LeafLink will continue using the latest " .
                "stored matrix dated " .
                date(
                    "d-M-Y",
                    strtotime(
                        $latest_stored
                    )
                ) .
                ".";

        } else {

            $message =
                "⚠️ No valid TIMB price matrix could be found. " .
                "No stored matrix was changed.";

        }


    } else {


        /*
        |--------------------------------------------------------------------------
        | Save validated matrix
        |--------------------------------------------------------------------------
        */

        $save =
            saveTIMBMatrix(
                $conn,
                $found_matrix['records'],
                $found_matrix['date']
            );


        if (
            !$save['success']
        ) {


            $message_type =
                "warning";


            $message =
                "⚠️ TIMB returned a valid matrix, " .
                "but LeafLink could not save it safely. " .
                "Existing prices were not changed. " .
                "Error: " .
                $save['error'];


        } else {


            $message_type =
                "success";


            $message =
                "✅ TIMB price matrix updated successfully. " .
                "Matrix date: " .
                date(
                    "d-M-Y",
                    strtotime(
                        $found_matrix['date']
                    )
                ) .
                ". " .
                $save['count'] .
                " grades imported.";

        }

    }

}


/*
|--------------------------------------------------------------------------
| MANUAL PDF UPLOAD
|--------------------------------------------------------------------------
|
| Existing fallback functionality retained.
|--------------------------------------------------------------------------
*/

if (
    isset($_POST['upload'])
) {


    $folder =
        "../price_matrix/";


    if (
        !is_dir($folder)
    ) {

        mkdir(
            $folder,
            0777,
            true
        );

    }


    if (
        !isset(
            $_FILES['matrix_pdf']
        ) ||
        $_FILES['matrix_pdf']['error']
            !== UPLOAD_ERR_OK
    ) {


        $message_type =
            "warning";


        $message =
            "⚠️ No valid PDF was uploaded.";


    } else {


        $filename =
            time() .
            "_" .
            basename(
                $_FILES['matrix_pdf']['name']
            );


        $filepath =
            $folder .
            $filename;


        if (
            move_uploaded_file(
                $_FILES['matrix_pdf']['tmp_name'],
                $filepath
            )
        ) {


            try {


                $parser =
                    new Smalot\PdfParser\Parser();


                $pdf =
                    $parser->parseFile(
                        $filepath
                    );


                $text =
                    $pdf->getText();


                /*
                |--------------------------------------------------------------------------
                | Detect matrix date
                |--------------------------------------------------------------------------
                */

                if (
                    !preg_match(
                        '/\d{2}-[A-Z]{3}-\d{4}/',
                        strtoupper($text),
                        $dateMatch
                    )
                ) {

                    throw new Exception(
                        "Could not detect the matrix date."
                    );

                }


                $price_date =
                    date(
                        "Y-m-d",
                        strtotime(
                            $dateMatch[0]
                        )
                    );


                /*
                |--------------------------------------------------------------------------
                | Find grade + price pairs
                |--------------------------------------------------------------------------
                */

                preg_match_all(
                    '/([A-Z0-9]{2,6})\s*([0-9]+\.[0-9]{2})/',
                    strtoupper($text),
                    $matches,
                    PREG_SET_ORDER
                );


                if (
                    count($matches) === 0
                ) {

                    throw new Exception(
                        "No grade/price records could be identified."
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Delete existing prices for this date
                |--------------------------------------------------------------------------
                */

                $delete =
                    mysqli_prepare(
                        $conn,
                        "DELETE FROM price_matrix
                         WHERE price_date=?"
                    );


                mysqli_stmt_bind_param(
                    $delete,
                    "s",
                    $price_date
                );


                mysqli_stmt_execute(
                    $delete
                );


                /*
                |--------------------------------------------------------------------------
                | Insert
                |--------------------------------------------------------------------------
                */

                $insert =
                    mysqli_prepare(
                        $conn,
                        "INSERT INTO price_matrix
                        (grade, average_price, price_date)
                        VALUES (?, ?, ?)"
                    );


                $count = 0;

                $seenGrades = [];


                foreach (
                    $matches as $match
                ) {


                    $grade =
                        trim(
                            $match[1]
                        );


                    $price =
                        (float)$match[2];


                    /*
                    | Prevent duplicate grades
                    */

                    if (
                        isset(
                            $seenGrades[$grade]
                        )
                    ) {

                        continue;

                    }


                    $seenGrades[$grade] =
                        true;


                    mysqli_stmt_bind_param(
                        $insert,
                        "sds",
                        $grade,
                        $price,
                        $price_date
                    );


                    if (
                        mysqli_stmt_execute(
                            $insert
                        )
                    ) {

                        $count++;

                    }

                }


                $message_type =
                    "success";


                $message =
                    "✅ TIMB Daily Average Price Matrix " .
                    "imported successfully. " .
                    "Date: " .
                    $price_date .
                    ". " .
                    $count .
                    " grades imported.";


            } catch (
                Exception $e
            ) {


                $message_type =
                    "warning";


                $message =
                    "⚠️ Import failed: " .
                    htmlspecialchars(
                        $e->getMessage()
                    );

            }


        } else {


            $message_type =
                "warning";


            $message =
                "⚠️ Upload failed.";

        }

    }

}

?>


<!doctype html>

<html>

<head>

    <meta charset="UTF-8">

    <title>Price Matrix</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <style>

        .matrix-message {

            padding: 15px;

            margin-bottom: 20px;

            border-radius: 8px;

            font-weight: 600;

        }


        .matrix-success {

            background: #e8f7ee;

            color: #176b3a;

            border: 1px solid #b9e4c8;

        }


        .matrix-warning {

            background: #fff7df;

            color: #795b00;

            border: 1px solid #efd98a;

        }


        .matrix-actions {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(280px, 1fr)
                );

            gap: 20px;

            margin-top: 20px;

        }


        .matrix-action-card {

            padding: 20px;

            border: 1px solid #ddd;

            border-radius: 10px;

            background: #fff;

        }


        .matrix-action-card h3 {

            margin-top: 0;

        }


        .matrix-action-card button {

            margin-top: 15px;

        }


        .matrix-source-note {

            margin-top: 12px;

            font-size: 13px;

            color: #666;

        }

    </style>

</head>


<body>


<div class="header">

    <h1>LeafLink</h1>

    <p>Price Matrix</p>

</div>


<div class="layout">


    <div class="sidebar">

        <h3>Contractor</h3>

        <hr>

        <a href="dashboard.php">
            Dashboard
        </a>

    </div>


    <div class="content">


        <div class="card">


            <h2>
                TIMB Daily Price Matrix
            </h2>


            <?php if (
                $message !== ""
            ): ?>

                <div
                    class="matrix-message
                    <?php
                    echo
                        $message_type === "success"
                        ? "matrix-success"
                        : "matrix-warning";
                    ?>"
                >

                    <?php
                    echo htmlspecialchars(
                        $message
                    );
                    ?>

                </div>

            <?php endif; ?>


            <div class="matrix-actions">


                <!--
                ============================================================
                AUTOMATIC TIMB UPDATE
                ============================================================
                -->

                <div class="matrix-action-card">


                    <h3>
                        🔄 Update from TIMB
                    </h3>


                    <p>

                        Check TIMB's daily price
                        matrix, find the newest
                        valid published matrix,
                        read the grade and price
                        values directly from the
                        page, validate them, and
                        update LeafLink.

                    </p>


                    <form
                        method="POST"
                    >

                        <button
                            type="submit"
                            name="update_timb"
                        >

                            Update from TIMB

                        </button>

                    </form>


                    <div
                        class="matrix-source-note"
                    >

                        Source:
                        TIMB Daily Average
                        Price Matrix

                    </div>


                </div>


                <!--
                ============================================================
                MANUAL FALLBACK
                ============================================================
                -->

                <div class="matrix-action-card">


                    <h3>
                        📄 Manual Upload
                    </h3>


                    <p>

                        Use this option if the
                        TIMB matrix cannot be
                        retrieved automatically.

                    </p>


                    <form
                        method="POST"
                        enctype="multipart/form-data"
                    >


                        <input
                            type="file"
                            name="matrix_pdf"
                            accept=".pdf"
                            required
                        >


                        <button
                            type="submit"
                            name="upload"
                        >

                            Upload Matrix

                        </button>


                    </form>


                </div>


            </div>


        </div>


    </div>


</div>


</body>

</html>