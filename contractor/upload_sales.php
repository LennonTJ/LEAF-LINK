<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include("../config/database.php");

$message = "";
$type = "";

// Resolve contractor_id
$contractor_id = $_SESSION['contractor_id'] ?? null;

if (!$contractor_id) {
    $uid = $_SESSION['user_id'];

    $u_stmt = mysqli_prepare(
        $conn,
        "SELECT contractor_id FROM users WHERE user_id = ? LIMIT 1"
    );

    if ($u_stmt) {
        mysqli_stmt_bind_param($u_stmt, "i", $uid);
        mysqli_stmt_execute($u_stmt);
        $ures = mysqli_stmt_get_result($u_stmt);
        $uro = mysqli_fetch_assoc($ures);

        $contractor_id = $uro['contractor_id'] ?? null;
    }
}

// Fallback: match contractor_code to username
if (!$contractor_id) {
    $uid = $_SESSION['user_id'];

    $u_stmt2 = mysqli_prepare(
        $conn,
        "SELECT username FROM users WHERE user_id = ? LIMIT 1"
    );

    if ($u_stmt2) {
        mysqli_stmt_bind_param($u_stmt2, "i", $uid);
        mysqli_stmt_execute($u_stmt2);
        $ures2 = mysqli_stmt_get_result($u_stmt2);
        $uro2 = mysqli_fetch_assoc($ures2);

        $uname = $uro2['username'] ?? null;

        if ($uname) {
            $cstmt2 = mysqli_prepare(
                $conn,
                "SELECT contractor_id FROM contractors WHERE contractor_code = ? LIMIT 1"
            );

            if ($cstmt2) {
                mysqli_stmt_bind_param($cstmt2, "s", $uname);
                mysqli_stmt_execute($cstmt2);
                $cres2 = mysqli_stmt_get_result($cstmt2);
                $crow2 = mysqli_fetch_assoc($cres2);

                $contractor_id = $crow2['contractor_id'] ?? null;
            }
        }
    }
}

// Stop if contractor_id still missing
if (!$contractor_id) {
    die("Contractor account is not linked correctly. Please contact administrator.");
}

if (isset($_POST['upload']) && isset($_FILES['sale_pdf'])) {

    if ($_FILES['sale_pdf']['error'] === UPLOAD_ERR_OK) {

        $ext = strtolower(
            pathinfo($_FILES['sale_pdf']['name'], PATHINFO_EXTENSION)
        );

        if ($ext != "pdf") {

            $message = "Only PDF files are allowed.";
            $type = "error";

        } else {

            $upload_dir = "../uploads/sales/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_name = "Sales-Sheet_" . date("Ymd_His") . ".pdf";
            $destination = $upload_dir . $new_name;

            if (move_uploaded_file($_FILES['sale_pdf']['tmp_name'], $destination)) {

                $uploaded_by = $_SESSION['user_id'];
                $original_name = $_FILES['sale_pdf']['name'];

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO uploaded_files
                    (contractor_id, filename, original_filename, rows_imported, upload_status, uploaded_by, uploaded_at)
                    VALUES(?,?,?,0,'Pending',?,NOW())"
                );

                if ($stmt) {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "issi",
                        $contractor_id,
                        $new_name,
                        $original_name,
                        $uploaded_by
                    );

                    if (mysqli_stmt_execute($stmt)) {

                        // Get the ID of the uploaded_files record
                        $file_id = mysqli_insert_id($conn);

                        // Redirect to the preview page
                        header(
                            "Location: confirm_import.php?file=" . urlencode($new_name) . "&file_id=" . $file_id
                        );

                        exit();

                    } else {

                        $message = "Database error: " . mysqli_stmt_error($stmt);
                        $type = "error";
                    }

                } else {

                    $message = "Failed to prepare database statement.";
                    $type = "error";
                }

            } else {

                $message = "Failed to move uploaded file.";
                $type = "error";
            }
        }

    } else {

        $message = "Please choose a PDF file.";
        $type = "error";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upload Sale Sheet</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="header">
    <h1>LeafLink</h1>
    <p> Sale Sheet Import</p>
</div>

<div class="layout">

    <div class="sidebar">
        <h3>Contractor</h3>
        <hr>
        <a href="dashboard.php">Dashboard</a>
    </div>

    <div class="content">

        <div class="card">

            <h2>Upload Official Sales Sheet PDF</h2>

            <?php if ($message != "") { ?>
                <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
            <?php } ?>

            <form method="POST" enctype="multipart/form-data">

                <label>Select PDF</label>
                <br><br>

                <input
                    type="file"
                    name="sale_pdf"
                    accept=".pdf"
                    required
                >

                <br><br>

                <button type="submit" name="upload">
                    Upload & Analyse
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>