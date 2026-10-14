<?php

require_once('conn.php');


/*
|--------------------------------------------------------------------------
| RESPONSE HEADER
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid request method.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| ENROLLMENT ACTION
|--------------------------------------------------------------------------
*/

if (
    !isset($_POST['action']) ||
    $_POST['action'] !== 'enrollment'
) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid enrollment request.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CHILD INFORMATION
|--------------------------------------------------------------------------
*/

$c_first_name = trim(
    $_POST['c_first_name'] ?? ''
);

$c_middle_name = trim(
    $_POST['c_middle_name'] ?? ''
);

$c_last_name = trim(
    $_POST['c_last_name'] ?? ''
);

$c_gender = trim(
    $_POST['c_gender'] ?? ''
);

$c_birthdate = trim(
    $_POST['c_birthdate'] ?? ''
);

$c_address = trim(
    $_POST['c_address'] ?? ''
);

$c_place_of_birth = trim(
    $_POST['c_place_of_birth'] ?? ''
);


/*
|--------------------------------------------------------------------------
| FATHER INFORMATION
|--------------------------------------------------------------------------
*/

$f_first_name = trim(
    $_POST['f_first_name'] ?? ''
);

$f_middle_name = trim(
    $_POST['f_middle_name'] ?? ''
);

$f_last_name = trim(
    $_POST['f_last_name'] ?? ''
);

$f_email = '';


/*
|--------------------------------------------------------------------------
| MOTHER INFORMATION
|--------------------------------------------------------------------------
*/

$m_first_name = trim(
    $_POST['m_first_name'] ?? ''
);

$m_middle_name = trim(
    $_POST['m_middle_name'] ?? ''
);

$m_last_name = trim(
    $_POST['m_last_name'] ?? ''
);

$m_email = '';


/*
|--------------------------------------------------------------------------
| GUARDIAN INFORMATION
|--------------------------------------------------------------------------
*/

$g_first_name = trim(
    $_POST['g_first_name'] ?? ''
);

$g_middle_name = trim(
    $_POST['g_middle_name'] ?? ''
);

$g_last_name = trim(
    $_POST['g_last_name'] ?? ''
);

$g_email = trim(
    $_POST['g_email'] ?? ''
);


/*
|--------------------------------------------------------------------------
| DOCUMENT INFORMATION
|--------------------------------------------------------------------------
*/

$file_type = trim(
    $_POST['file_type'] ?? ''
);


/*
|--------------------------------------------------------------------------
| VALIDATE CHILD INFORMATION
|--------------------------------------------------------------------------
*/

if (
    empty($c_first_name) ||
    empty($c_last_name) ||
    empty($c_gender) ||
    empty($c_birthdate) ||
    empty($c_address) ||
    empty($c_place_of_birth)
) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Please complete all required child information.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE GUARDIAN INFORMATION
|--------------------------------------------------------------------------
*/

if (
    empty($g_first_name) ||
    empty($g_last_name) ||
    empty($g_email)
) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Please complete all required guardian information.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDATE GUARDIAN EMAIL
|--------------------------------------------------------------------------
*/

if (!filter_var($g_email, FILTER_VALIDATE_EMAIL)) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Please provide a valid guardian email address.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| ALLOWED DOCUMENT TYPES
|--------------------------------------------------------------------------
*/

$allowedFileTypes = [
    'Proof of Address',
    'Child Birth Certificate',
    'Guardian Valid ID',
    'Barangay Certificate',
    'Other'
];


/*
|--------------------------------------------------------------------------
| VALIDATE DOCUMENT TYPE
|--------------------------------------------------------------------------
*/

if (
    empty($file_type) ||
    !in_array(
        $file_type,
        $allowedFileTypes,
        true
    )
) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Please select a valid document type.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK UPLOADED FILE
|--------------------------------------------------------------------------
*/

if (
    !isset($_FILES['file_image']) ||
    $_FILES['file_image']['error'] !== UPLOAD_ERR_OK
) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Please upload a supporting document.'
    ]);

    exit;
}


$file = $_FILES['file_image'];


/*
|--------------------------------------------------------------------------
| MAXIMUM FILE SIZE
|--------------------------------------------------------------------------
|
| 5 MB
|
*/

$maxFileSize = 5 * 1024 * 1024;


if ($file['size'] > $maxFileSize) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'The uploaded file must not exceed 5MB.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| FILE EXTENSION
|--------------------------------------------------------------------------
*/

$originalName = $file['name'];

$extension = strtolower(
    pathinfo(
        $originalName,
        PATHINFO_EXTENSION
    )
);


$allowedExtensions = [
    'jpg',
    'jpeg',
    'png',
    'pdf'
];


if (
    !in_array(
        $extension,
        $allowedExtensions,
        true
    )
) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid file type. Only JPG, JPEG, PNG, and PDF files are allowed.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| MIME TYPE VALIDATION
|--------------------------------------------------------------------------
*/

$finfo = finfo_open(
    FILEINFO_MIME_TYPE
);

$mimeType = finfo_file(
    $finfo,
    $file['tmp_name']
);

finfo_close($finfo);


$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'application/pdf'
];


if (
    !in_array(
        $mimeType,
        $allowedMimeTypes,
        true
    )
) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'The uploaded file content is not valid.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| UPLOAD DIRECTORY
|--------------------------------------------------------------------------
|
| Current backend:
|
| api/
| ├── req-enrollment.php
| └── file-image/
|
*/

$uploadDirectory = __DIR__ . '/file-image/';


/*
|--------------------------------------------------------------------------
| CREATE DIRECTORY IF IT DOES NOT EXIST
|--------------------------------------------------------------------------
*/

if (!is_dir($uploadDirectory)) {

    if (!mkdir(
        $uploadDirectory,
        0755,
        true
    )) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Unable to create upload directory.'
        ]);

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| GENERATE UNIQUE FILE NAME
|--------------------------------------------------------------------------
*/

$uniqueName =
    'ENR-' .
    date('Ymd-His') .
    '-' .
    bin2hex(
        random_bytes(5)
    ) .
    '.' .
    $extension;


/*
|--------------------------------------------------------------------------
| FULL FILE PATH
|--------------------------------------------------------------------------
*/

$filePath =
    $uploadDirectory .
    $uniqueName;


/*
|--------------------------------------------------------------------------
| MOVE FILE
|--------------------------------------------------------------------------
*/

if (
    !move_uploaded_file(
        $file['tmp_name'],
        $filePath
    )
) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to upload the supporting document.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| GET ACTIVE ACADEMIC YEAR
|--------------------------------------------------------------------------
*/

$academic_id = null;


$academicQuery = $conn->query("
    SELECT id
    FROM academicyear
    WHERE status = 1
    ORDER BY id DESC
    LIMIT 1
");


if (
    $academicQuery &&
    $academicQuery->num_rows > 0
) {

    $academicRow =
        $academicQuery->fetch_assoc();

    $academic_id =
        (int) $academicRow['id'];
}


/*
|--------------------------------------------------------------------------
| NO ACTIVE ACADEMIC YEAR
|--------------------------------------------------------------------------
*/

if (!$academic_id) {

    /*
    |--------------------------------------------------------------------------
    | Remove Uploaded File
    |--------------------------------------------------------------------------
    */

    if (file_exists($filePath)) {

        unlink($filePath);

    }


    echo json_encode([
        'status'  => 'error',
        'message' => 'No active academic year. Please contact the administrator.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| INSERT ENROLLMENT
|--------------------------------------------------------------------------
*/

$sql = "INSERT INTO enrollment (

            academic_id,

            c_first_name,
            c_middle_name,
            c_last_name,
            c_gender,
            c_birthdate,
            c_address,
            c_place_of_birth,

            f_first_name,
            f_middle_name,
            f_last_name,
            f_email,

            m_first_name,
            m_middle_name,
            m_last_name,
            m_email,

            g_first_name,
            g_middle_name,
            g_last_name,
            g_email,

            status,

            file_image,
            file_type

        )

        VALUES (

            ?,

            ?, ?, ?, ?, ?, ?, ?,

            ?, ?, ?, ?,

            ?, ?, ?, ?,

            ?, ?, ?, ?,

            ?,

            ?,
            ?

        )";


/*
|--------------------------------------------------------------------------
| PREPARE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare($sql);


if (!$stmt) {

    /*
    |--------------------------------------------------------------------------
    | Remove Uploaded File
    |--------------------------------------------------------------------------
    */

    if (file_exists($filePath)) {

        unlink($filePath);

    }


    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to prepare enrollment request.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| STATUS
|--------------------------------------------------------------------------
*/

$status = 0;


/*
|--------------------------------------------------------------------------
| BIND PARAMETERS
|--------------------------------------------------------------------------
|
| Total parameters: 23
|
| 1  = academic_id     → i
|
| 2  = c_first_name    → s
| 3  = c_middle_name   → s
| 4  = c_last_name     → s
| 5  = c_gender        → s
| 6  = c_birthdate     → s
| 7  = c_address       → s
| 8  = c_place_of_birth→ s
|
| 9  = f_first_name    → s
| 10 = f_middle_name   → s
| 11 = f_last_name     → s
|
| 12 = m_first_name    → s
| 13 = m_middle_name   → s
| 14 = m_last_name     → s
|
| 15 = g_first_name    → s
| 16 = g_middle_name   → s
| 17 = g_last_name     → s
| 18 = g_email         → s
|
| 21 = status          → i
|
| 22 = file_image      → s
| 23 = file_type       → s
|
|--------------------------------------------------------------------------
*/

$stmt->bind_param(
    "isssssssssssssssssssiss",

    $academic_id,

    $c_first_name,
    $c_middle_name,
    $c_last_name,
    $c_gender,
    $c_birthdate,
    $c_address,
    $c_place_of_birth,

    $f_first_name,
    $f_middle_name,
    $f_last_name,
    $f_email,

    $m_first_name,
    $m_middle_name,
    $m_last_name,
    $m_email,

    $g_first_name,
    $g_middle_name,
    $g_last_name,
    $g_email,

    $status,

    $uniqueName,
    $file_type
);


/*
|--------------------------------------------------------------------------
| EXECUTE
|--------------------------------------------------------------------------
*/

if ($stmt->execute()) {

    echo json_encode([
        'status'  => 'success',
        'message' => 'Enrollment application submitted successfully.'
    ]);

} else {

    /*
    |--------------------------------------------------------------------------
    | DELETE FILE IF DATABASE INSERT FAILED
    |--------------------------------------------------------------------------
    */

    if (file_exists($filePath)) {

        unlink($filePath);

    }


    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to submit enrollment application: ' .
                     $stmt->error
    ]);
}


/*
|--------------------------------------------------------------------------
| CLOSE
|--------------------------------------------------------------------------
*/

$stmt->close();

$conn->close();

?>
