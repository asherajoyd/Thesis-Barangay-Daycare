<?php

require_once('conn.php');

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
| ACTION CHECK
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

$c_first_name     = trim($_POST['c_first_name'] ?? '');
$c_middle_name    = trim($_POST['c_middle_name'] ?? '');
$c_last_name      = trim($_POST['c_last_name'] ?? '');
$c_gender         = trim($_POST['c_gender'] ?? '');
$c_birthdate      = trim($_POST['c_birthdate'] ?? '');
$c_address        = trim($_POST['c_address'] ?? '');
$c_place_of_birth = trim($_POST['c_place_of_birth'] ?? '');


/*
|--------------------------------------------------------------------------
| FATHER
|--------------------------------------------------------------------------
*/

$f_first_name  = trim($_POST['f_first_name'] ?? '');
$f_middle_name = trim($_POST['f_middle_name'] ?? '');
$f_last_name   = trim($_POST['f_last_name'] ?? '');


/*
|--------------------------------------------------------------------------
| MOTHER
|--------------------------------------------------------------------------
*/

$m_first_name  = trim($_POST['m_first_name'] ?? '');
$m_middle_name = trim($_POST['m_middle_name'] ?? '');
$m_last_name   = trim($_POST['m_last_name'] ?? '');


/*
|--------------------------------------------------------------------------
| GUARDIAN
|--------------------------------------------------------------------------
*/

$g_first_name  = trim($_POST['g_first_name'] ?? '');
$g_middle_name = trim($_POST['g_middle_name'] ?? '');
$g_last_name   = trim($_POST['g_last_name'] ?? '');
$g_email       = trim($_POST['g_email'] ?? '');
$g_contact     = trim($_POST['g_contact'] ?? '');


/*
|--------------------------------------------------------------------------
| VALIDATE CHILD
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
| VALIDATE GUARDIAN
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


if (!filter_var($g_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Please provide a valid guardian email address.'
    ]);
    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK IF GUARDIAN EMAIL EXISTS
|--------------------------------------------------------------------------
*/

$checkEmail = $conn->prepare("
    SELECT id FROM users WHERE email = ? LIMIT 1
");

$checkEmail->bind_param("s", $g_email);
$checkEmail->execute();
$emailResult = $checkEmail->get_result();

if ($emailResult->num_rows > 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'This email is already registered. Please use a different email.'
    ]);
    exit;
}

$checkEmail->close();


/*
|--------------------------------------------------------------------------
| GET ACTIVE ACADEMIC YEAR
|--------------------------------------------------------------------------
*/

$academic_id = null;

$academicQuery = $conn->query("
    SELECT id
    FROM academic_year
    WHERE status = 'active'
    ORDER BY id DESC
    LIMIT 1
");

if ($academicQuery && $academicQuery->num_rows > 0) {
    $academicRow = $academicQuery->fetch_assoc();
    $academic_id = (int) $academicRow['id'];
}

if (!$academic_id) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'No active academic year. Please contact the administrator.'
    ]);
    exit;
}


/*
|--------------------------------------------------------------------------
| FILE UPLOAD VALIDATION
|--------------------------------------------------------------------------
*/

$uploads = [
    'birth'   => 'Child Birth Certificate',
    'yellow'  => 'Yellow Card',
    'address' => 'Proof of Address'
];

$uploadedFiles = [];   // storage ng mga na-upload para sa cleanup
$uploadDir = __DIR__ . '/file-image/';


if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Unable to create upload directory.'
        ]);
        exit;
    }
}


$allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
$allowedMimeTypes  = ['image/jpeg', 'image/png', 'application/pdf'];
$maxFileSize       = 5 * 1024 * 1024;


foreach ($uploads as $key => $label) {

    $fileField = 'file_image_' . $key;

    if (
        !isset($_FILES[$fileField]) ||
        $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK
    ) {
        echo json_encode([
            'status'  => 'error',
            'message' => "Please upload the {$label}."
        ]);
        exit;
    }

    $file = $_FILES[$fileField];

    /* Size */
    if ($file['size'] > $maxFileSize) {
        echo json_encode([
            'status'  => 'error',
            'message' => "The {$label} file must not exceed 5MB."
        ]);
        exit;
    }

    /* Extension */
    $extension = strtolower(
        pathinfo($file['name'], PATHINFO_EXTENSION)
    );

    if (!in_array($extension, $allowedExtensions, true)) {
        echo json_encode([
            'status'  => 'error',
            'message' => "Invalid file type for {$label}. Only JPG, JPEG, PNG, and PDF are allowed."
        ]);
        exit;
    }

    /* MIME */
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        echo json_encode([
            'status'  => 'error',
            'message' => "The {$label} file content is not valid."
        ]);
        exit;
    }

    /* Move file */
    $uniqueName = 'ENR-' . date('Ymd-His') . '-' .
                  bin2hex(random_bytes(5)) . '.' . $extension;

    $destination = $uploadDir . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        echo json_encode([
            'status'  => 'error',
            'message' => "Failed to upload the {$label}."
        ]);
        exit;
    }

    $uploadedFiles[$key] = [
        'file_type'  => $label,
        'file_image' => $uniqueName
    ];
}


/*
|--------------------------------------------------------------------------
| START TRANSACTION
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | 1. INSERT CHILD
    |--------------------------------------------------------------------------
    */

    $stmtChild = $conn->prepare("
        INSERT INTO children (
            first_name,
            middle_name,
            last_name,
            gender,
            birthdate,
            place_of_birth,
            address
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtChild->bind_param(
        "sssssss",
        $c_first_name,
        $c_middle_name,
        $c_last_name,
        $c_gender,
        $c_birthdate,
        $c_place_of_birth,
        $c_address
    );

    if (!$stmtChild->execute()) {
        throw new Exception('Failed to save child information: ' . $stmtChild->error);
    }

    $child_id = $stmtChild->insert_id;
    $stmtChild->close();


    /*
    |--------------------------------------------------------------------------
    | 2. INSERT FATHER (kung may pangalan)
    |--------------------------------------------------------------------------
    */

    if (!empty($f_first_name) && !empty($f_last_name)) {

        $stmtF = $conn->prepare("
            INSERT INTO parent_info (
                child_id,
                first_name,
                middle_name,
                last_name,
                relationship,
                is_primary
            ) VALUES (?, ?, ?, ?, 'father', 0)
        ");

        $stmtF->bind_param(
            "isss",
            $child_id,
            $f_first_name,
            $f_middle_name,
            $f_last_name
        );

        if (!$stmtF->execute()) {
            throw new Exception('Failed to save father information: ' . $stmtF->error);
        }

        $stmtF->close();
    }


    /*
    |--------------------------------------------------------------------------
    | 3. INSERT MOTHER (kung may pangalan)
    |--------------------------------------------------------------------------
    */

    if (!empty($m_first_name) && !empty($m_last_name)) {

        $stmtM = $conn->prepare("
            INSERT INTO parent_info (
                child_id,
                first_name,
                middle_name,
                last_name,
                relationship,
                is_primary
            ) VALUES (?, ?, ?, ?, 'mother', 0)
        ");

        $stmtM->bind_param(
            "isss",
            $child_id,
            $m_first_name,
            $m_middle_name,
            $m_last_name
        );

        if (!$stmtM->execute()) {
            throw new Exception('Failed to save mother information: ' . $stmtM->error);
        }

        $stmtM->close();
    }


    /*
    |--------------------------------------------------------------------------
    | 4. INSERT GUARDIAN USER ACCOUNT
    |--------------------------------------------------------------------------
    */

    $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
    $role            = 'parent';
    $userStatus      = 'active';

    $stmtUser = $conn->prepare("
        INSERT INTO users (
            first_name,
            middle_name,
            last_name,
            email,
            password,
            contact,
            role,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtUser->bind_param(
        "ssssssss",
        $g_first_name,
        $g_middle_name,
        $g_last_name,
        $g_email,
        $defaultPassword,
        $g_contact,
        $role,
        $userStatus
    );

    if (!$stmtUser->execute()) {
        throw new Exception('Failed to create guardian account: ' . $stmtUser->error);
    }

    $user_id = $stmtUser->insert_id;
    $stmtUser->close();


    /*
    |--------------------------------------------------------------------------
    | 5. UPDATE CHILDREN — LINK PARENT USER
    |--------------------------------------------------------------------------
    */

    $stmtLink = $conn->prepare("
        UPDATE children SET user_id = ? WHERE id = ?
    ");

    $stmtLink->bind_param("ii", $user_id, $child_id);

    if (!$stmtLink->execute()) {
        throw new Exception('Failed to link child to guardian: ' . $stmtLink->error);
    }

    $stmtLink->close();


    /*
    |--------------------------------------------------------------------------
    | 6. INSERT GUARDIAN SA parent_info
    |--------------------------------------------------------------------------
    */

    $stmtG = $conn->prepare("
        INSERT INTO parent_info (
            user_id,
            child_id,
            first_name,
            middle_name,
            last_name,
            relationship,
            is_primary
        ) VALUES (?, ?, ?, ?, ?, 'guardian', 1)
    ");

    $stmtG->bind_param(
        "iisss",
        $user_id,
        $child_id,
        $g_first_name,
        $g_middle_name,
        $g_last_name
    );

    if (!$stmtG->execute()) {
        throw new Exception('Failed to save guardian information: ' . $stmtG->error);
    }

    $stmtG->close();


    /*
    |--------------------------------------------------------------------------
    | 7. INSERT ENROLLMENT
    |--------------------------------------------------------------------------
    */

    $enrollmentStatus = 'pending';

    $stmtEnroll = $conn->prepare("
        INSERT INTO enrollment (
            child_id,
            academic_id,
            status
        ) VALUES (?, ?, ?)
    ");

    $stmtEnroll->bind_param(
        "iis",
        $child_id,
        $academic_id,
        $enrollmentStatus
    );

    if (!$stmtEnroll->execute()) {
        throw new Exception('Failed to create enrollment: ' . $stmtEnroll->error);
    }

    $enrollment_id = $stmtEnroll->insert_id;
    $stmtEnroll->close();


    /*
    |--------------------------------------------------------------------------
    | 8. INSERT ENROLLMENT DOCUMENTS
    |--------------------------------------------------------------------------
    */

    $stmtDoc = $conn->prepare("
        INSERT INTO enrollment_document (
            enrollment_id,
            file_type,
            file_image
        ) VALUES (?, ?, ?)
    ");

    foreach ($uploadedFiles as $doc) {

        $stmtDoc->bind_param(
            "iss",
            $enrollment_id,
            $doc['file_type'],
            $doc['file_image']
        );

        if (!$stmtDoc->execute()) {
            throw new Exception('Failed to save document: ' . $stmtDoc->error);
        }
    }

    $stmtDoc->close();


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $conn->commit();

    echo json_encode([
        'status'  => 'success',
        'message' => 'Enrollment application submitted successfully. Your guardian account has been created — you may now log in using your email and default password "password123".'
    ]);


} catch (Exception $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    $conn->rollback();


    /*
    |--------------------------------------------------------------------------
    | CLEANUP UPLOADED FILES
    |--------------------------------------------------------------------------
    */

    foreach ($uploadedFiles as $doc) {
        $path = $uploadDir . $doc['file_image'];
        if (file_exists($path)) {
            unlink($path);
        }
    }


    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}


$conn->close();

?>