<?php

$pageTitle = "Add Walk-in Enrollment";

require_once '../guard.php';
include "../../../api/conn.php";


function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


// ==========================================================
// FORM STATE
// ==========================================================

$c_first_name     = "";
$c_middle_name    = "";
$c_last_name      = "";
$c_gender         = "";
$c_birthdate      = "";
$c_address        = "";
$c_place_of_birth = "";

$f_first_name  = "";
$f_middle_name = "";
$f_last_name   = "";

$m_first_name  = "";
$m_middle_name = "";
$m_last_name   = "";

$g_first_name  = "";
$g_middle_name = "";
$g_last_name   = "";
$g_email       = "";
$g_contact     = "";

$errorMessage = "";


// ==========================================================
// HANDLE POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Child
    $c_first_name     = trim($_POST['c_first_name'] ?? '');
    $c_middle_name    = trim($_POST['c_middle_name'] ?? '');
    $c_last_name      = trim($_POST['c_last_name'] ?? '');
    $c_gender         = trim($_POST['c_gender'] ?? '');
    $c_birthdate      = trim($_POST['c_birthdate'] ?? '');
    $c_address        = trim($_POST['c_address'] ?? '');
    $c_place_of_birth = trim($_POST['c_place_of_birth'] ?? '');

    // Father
    $f_first_name  = trim($_POST['f_first_name'] ?? '');
    $f_middle_name = trim($_POST['f_middle_name'] ?? '');
    $f_last_name   = trim($_POST['f_last_name'] ?? '');

    // Mother
    $m_first_name  = trim($_POST['m_first_name'] ?? '');
    $m_middle_name = trim($_POST['m_middle_name'] ?? '');
    $m_last_name   = trim($_POST['m_last_name'] ?? '');

    // Guardian
    $g_first_name  = trim($_POST['g_first_name'] ?? '');
    $g_middle_name = trim($_POST['g_middle_name'] ?? '');
    $g_last_name   = trim($_POST['g_last_name'] ?? '');
    $g_email       = trim($_POST['g_email'] ?? '');
    $g_contact     = trim($_POST['g_contact'] ?? '');


    // ==========================================================
    // VALIDATE
    // ==========================================================

    if (
        empty($c_first_name) ||
        empty($c_last_name) ||
        empty($c_gender) ||
        empty($c_birthdate) ||
        empty($c_address) ||
        empty($c_place_of_birth)
    ) {
        $errorMessage = "Please complete all required child information.";

    } elseif (
        empty($g_first_name) ||
        empty($g_last_name) ||
        empty($g_email)
    ) {
        $errorMessage = "Please complete all required guardian information.";

    } elseif (!filter_var($g_email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please provide a valid guardian email address.";

    } else {

        // Check duplicate email
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkEmail->bind_param("s", $g_email);
        $checkEmail->execute();
        $emailResult = $checkEmail->get_result();

        if ($emailResult->num_rows > 0) {
            $checkEmail->close();
            $errorMessage = "This email is already registered.";
        } else {

            $checkEmail->close();

            // Get active academic year
            $academic_id = null;
            $academicQuery = $conn->query("
                SELECT id FROM academic_year
                WHERE status = 'active'
                ORDER BY id DESC LIMIT 1
            ");

            if ($academicQuery && $academicQuery->num_rows > 0) {
                $academic_id = (int) $academicQuery->fetch_assoc()['id'];
            }

            if (!$academic_id) {
                $errorMessage = "No active academic year. Please set one in Settings first.";
            } else {

                // ==========================================================
                // HANDLE FILE UPLOADS (3 documents)
                // ==========================================================

                $uploads = [
                    'birth'   => 'Child Birth Certificate',
                    'yellow'  => 'Yellow Card',
                    'address' => 'Proof of Address'
                ];

                $uploadedFiles = [];
                $uploadDir     = __DIR__ . '/../../../api/file-image/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                $allowedMimeTypes  = ['image/jpeg', 'image/png', 'application/pdf'];
                $maxFileSize       = 5 * 1024 * 1024;

                $fileError = '';

                foreach ($uploads as $key => $label) {

                    $fileField = 'file_image_' . $key;

                    if (
                        !isset($_FILES[$fileField]) ||
                        $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK
                    ) {
                        $fileError = "Please upload the {$label}.";
                        break;
                    }

                    $file = $_FILES[$fileField];

                    if ($file['size'] > $maxFileSize) {
                        $fileError = "The {$label} file must not exceed 5MB.";
                        break;
                    }

                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    if (!in_array($extension, $allowedExtensions, true)) {
                        $fileError = "Invalid file type for {$label}.";
                        break;
                    }

                    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    if (!in_array($mimeType, $allowedMimeTypes, true)) {
                        $fileError = "The {$label} file content is not valid.";
                        break;
                    }

                    $uniqueName = 'ENR-' . date('Ymd-His') . '-' . bin2hex(random_bytes(5)) . '.' . $extension;
                    $destination = $uploadDir . $uniqueName;

                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                        $fileError = "Failed to upload the {$label}.";
                        break;
                    }

                    $uploadedFiles[$key] = [
                        'file_type'  => $label,
                        'file_image' => $uniqueName
                    ];
                }

                if (!empty($fileError)) {
                    $errorMessage = $fileError;
                } else {

                    try {

                        $conn->begin_transaction();

                        // 1. Insert child
                        $stmt = $conn->prepare("
                            INSERT INTO children (
                                first_name, middle_name, last_name,
                                gender, birthdate, place_of_birth, address
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->bind_param(
                            "sssssss",
                            $c_first_name, $c_middle_name, $c_last_name,
                            $c_gender, $c_birthdate, $c_place_of_birth, $c_address
                        );
                        $stmt->execute();
                        $child_id = $stmt->insert_id;
                        $stmt->close();

                        // 2. Insert father
                        if (!empty($f_first_name) && !empty($f_last_name)) {
                            $stmt = $conn->prepare("
                                INSERT INTO parent_info (
                                    child_id, first_name, middle_name, last_name,
                                    relationship, is_primary
                                ) VALUES (?, ?, ?, ?, 'father', 0)
                            ");
                            $stmt->bind_param("isss", $child_id, $f_first_name, $f_middle_name, $f_last_name);
                            $stmt->execute();
                            $stmt->close();
                        }

                        // 3. Insert mother
                        if (!empty($m_first_name) && !empty($m_last_name)) {
                            $stmt = $conn->prepare("
                                INSERT INTO parent_info (
                                    child_id, first_name, middle_name, last_name,
                                    relationship, is_primary
                                ) VALUES (?, ?, ?, ?, 'mother', 0)
                            ");
                            $stmt->bind_param("isss", $child_id, $m_first_name, $m_middle_name, $m_last_name);
                            $stmt->execute();
                            $stmt->close();
                        }

                        // 4. Insert guardian
                        $stmt = $conn->prepare("
                            INSERT INTO parent_info (
                                child_id, first_name, middle_name, last_name,
                                email, contact, relationship, is_primary
                            ) VALUES (?, ?, ?, ?, ?, ?, 'guardian', 1)
                        ");
                        $stmt->bind_param(
                            "isssss",
                            $child_id, $g_first_name, $g_middle_name, $g_last_name,
                            $g_email, $g_contact
                        );
                        $stmt->execute();
                        $stmt->close();

                        // 5. Insert enrollment (pending)
                        $enrollmentStatus = 'pending';

                        $stmt = $conn->prepare("
                            INSERT INTO enrollment (child_id, academic_id, status)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->bind_param("iis", $child_id, $academic_id, $enrollmentStatus);
                        $stmt->execute();
                        $enrollment_id = $stmt->insert_id;
                        $stmt->close();

                        // 6. Insert enrollment documents
                        $stmt = $conn->prepare("
                            INSERT INTO enrollment_document (
                                enrollment_id, file_type, file_image
                            ) VALUES (?, ?, ?)
                        ");

                        foreach ($uploadedFiles as $doc) {
                            $stmt->bind_param(
                                "iss",
                                $enrollment_id,
                                $doc['file_type'],
                                $doc['file_image']
                            );
                            $stmt->execute();
                        }

                        $stmt->close();

                        $conn->commit();

                        $_SESSION['status']  = "success";
                        $_SESSION['message'] = "Walk-in enrollment created successfully. Pending for review.";
                        header("Location: index.php");
                        exit;

                    } catch (Exception $ex) {

                        $conn->rollback();

                        // Cleanup uploaded files
                        foreach ($uploadedFiles as $doc) {
                            $path = $uploadDir . $doc['file_image'];
                            if (file_exists($path)) {
                                unlink($path);
                            }
                        }

                        $errorMessage = "Failed to create enrollment: " . $ex->getMessage();
                    }
                }
            }
        }
    }
}


include '../layout/header.php';

?>

<div>

    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h2 class="fw-bold fs-4 mb-0">Add Walk-in Enrollment</h2>
            <small class="text-muted">Enroll a child who walked in to the daycare</small>
        </div>
        <a href="index.php" class="btn btn-sm btn-light border">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>


    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small mb-3" role="alert">
            <i class="bi bi-exclamation-circle me-1"></i>
            <?= e($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>


    <div class="alert alert-info py-2 px-3 small mb-3">
        <i class="bi bi-info-circle me-1"></i>
        This enrollment will be saved as <strong>pending</strong>. You can review and approve it from the enrollment list.
    </div>


    <form method="POST" action="create-walkin.php" enctype="multipart/form-data" autocomplete="off">

        <!-- CHILD INFO -->
        <div class="card mb-3">
            <div class="card-header bg-paper border-bottom p-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-person-hearts text-primary me-2"></i>
                    Child Information
                </h6>
            </div>
            <div class="card-body p-4">

                <div class="row g-3">

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="c_last_name" class="form-control"
                               value="<?= e($c_last_name) ?>" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="c_first_name" class="form-control"
                               value="<?= e($c_first_name) ?>" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Middle Name</label>
                        <input type="text" name="c_middle_name" class="form-control"
                               value="<?= e($c_middle_name) ?>">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">Gender <span class="text-danger">*</span></label>
                        <select name="c_gender" class="form-select" required>
                            <option value="">Select gender</option>
                            <option value="male" <?= $c_gender === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= $c_gender === 'female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">Birthdate <span class="text-danger">*</span></label>
                        <input type="date" name="c_birthdate" class="form-control"
                               value="<?= e($c_birthdate) ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small mb-1">Complete Address <span class="text-danger">*</span></label>
                        <textarea name="c_address" class="form-control" rows="2" required><?= e($c_address) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small mb-1">Place of Birth <span class="text-danger">*</span></label>
                        <input type="text" name="c_place_of_birth" class="form-control"
                               value="<?= e($c_place_of_birth) ?>" required>
                    </div>

                </div>

            </div>
        </div>


        <!-- DOCUMENTS -->
        <div class="card mb-3">
            <div class="card-header bg-paper border-bottom p-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-paperclip text-primary me-2"></i>
                    Required Documents
                </h6>
            </div>
            <div class="card-body p-4">

                <!-- Birth Certificate -->
                <div class="mb-4">
                    <h6 class="fw-bold small mb-2">Birth Certificate <span class="text-danger">*</span></h6>
                    <input type="hidden" name="file_type_birth" value="Child Birth Certificate">
                    <input type="file" name="file_image_birth"
                           class="form-control enrollment-file"
                           accept=".jpg,.jpeg,.png,.pdf" required>
                    <div class="form-text">Accepted: JPG, JPEG, PNG, PDF. Max: 5MB.</div>
                </div>

                <!-- Yellow Card -->
                <div class="mb-4">
                    <h6 class="fw-bold small mb-2">Yellow Card <span class="text-danger">*</span></h6>
                    <input type="hidden" name="file_type_yellow" value="Yellow Card">
                    <input type="file" name="file_image_yellow"
                           class="form-control enrollment-file"
                           accept=".jpg,.jpeg,.png,.pdf" required>
                    <div class="form-text">Accepted: JPG, JPEG, PNG, PDF. Max: 5MB.</div>
                </div>

                <!-- Proof of Address -->
                <div>
                    <h6 class="fw-bold small mb-2">Proof of Address <span class="text-danger">*</span></h6>
                    <input type="hidden" name="file_type_address" value="Proof of Address">
                    <input type="file" name="file_image_address"
                           class="form-control enrollment-file"
                           accept=".jpg,.jpeg,.png,.pdf" required>
                    <div class="form-text">Accepted: JPG, JPEG, PNG, PDF. Max: 5MB.</div>
                </div>

            </div>
        </div>


        <!-- PARENT INFO -->
        <div class="card mb-3">
            <div class="card-header bg-paper border-bottom p-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-people text-primary me-2"></i>
                    Parent Information
                </h6>
            </div>
            <div class="card-body p-4">

                <!-- Father -->
                <h6 class="fw-bold text-uppercase small text-muted mb-2">Father</h6>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">First Name</label>
                        <input type="text" name="f_first_name" class="form-control" value="<?= e($f_first_name) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Middle Name</label>
                        <input type="text" name="f_middle_name" class="form-control" value="<?= e($f_middle_name) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Last Name</label>
                        <input type="text" name="f_last_name" class="form-control" value="<?= e($f_last_name) ?>">
                    </div>
                </div>

                <!-- Mother -->
                <h6 class="fw-bold text-uppercase small text-muted mb-2">Mother</h6>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">First Name</label>
                        <input type="text" name="m_first_name" class="form-control" value="<?= e($m_first_name) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Middle Name</label>
                        <input type="text" name="m_middle_name" class="form-control" value="<?= e($m_middle_name) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Last Name</label>
                        <input type="text" name="m_last_name" class="form-control" value="<?= e($m_last_name) ?>">
                    </div>
                </div>

                <!-- Guardian -->
                <h6 class="fw-bold text-uppercase small text-muted mb-2">Guardian <span class="text-danger">*</span></h6>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="g_first_name" class="form-control"
                               value="<?= e($g_first_name) ?>" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Middle Name</label>
                        <input type="text" name="g_middle_name" class="form-control" value="<?= e($g_middle_name) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-1">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="g_last_name" class="form-control"
                               value="<?= e($g_last_name) ?>" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">Email <span class="text-danger">*</span></label>
                        <input type="email" name="g_email" class="form-control"
                               value="<?= e($g_email) ?>" required>
                        <div class="form-text">This will be the parent's login account (created on approval).</div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">Contact Number</label>
                        <input type="text" name="g_contact" class="form-control"
                               value="<?= e($g_contact) ?>">
                    </div>
                </div>

            </div>
        </div>


        <!-- FOOTER -->
        <div class="d-flex justify-content-end gap-2">
            <a href="index.php" class="btn btn-light border">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle me-1"></i> Save Walk-in Enrollment
            </button>
        </div>

    </form>

</div>

<?php include '../layout/footer.php'; ?>