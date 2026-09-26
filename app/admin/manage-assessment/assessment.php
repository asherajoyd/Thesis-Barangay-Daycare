<?php

session_start();

/* =========================================================
   DATABASE CONNECTION
========================================================= */

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "barangaydaycare"
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


/* =========================================================
   HELPER FUNCTION
========================================================= */

function clean($value)
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   DELETE ASSESSMENT
========================================================= */

if (isset($_POST['delete'])) {

    $id = intval($_POST['id']);

    $stmt = $conn->prepare("
        DELETE FROM assessment
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: assessment.php");
    exit();
}


/* =========================================================
   ADD / UPDATE ASSESSMENT
========================================================= */

if (isset($_POST['save_assessment'])) {

    $id = intval($_POST['id'] ?? 0);

    $child_id = intval($_POST['child_id']);
    $teacher_id = intval($_POST['teacher_id']);

    $assessment_date = $_POST['assessment_date'];
    $assessment_type = trim($_POST['assessment_type']);
    $domain = trim($_POST['domain']);

    $score = $_POST['score'];

    if ($score === '') {
        $score = null;
    } else {
        $score = floatval($score);
    }

    $remarks = trim($_POST['remarks']);


    /* =====================================================
       UPDATE
    ===================================================== */

    if ($id > 0) {

        $stmt = $conn->prepare("
            UPDATE assessment
            SET
                child_id = ?,
                teacher_id = ?,
                assessment_date = ?,
                assessment_type = ?,
                domain = ?,
                score = ?,
                remarks = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "iisssdsi",
            $child_id,
            $teacher_id,
            $assessment_date,
            $assessment_type,
            $domain,
            $score,
            $remarks,
            $id
        );

        $stmt->execute();
        $stmt->close();

    }

    /* =====================================================
       INSERT
    ===================================================== */

    else {

        $stmt = $conn->prepare("
            INSERT INTO assessment
            (
                child_id,
                teacher_id,
                assessment_date,
                assessment_type,
                domain,
                score,
                remarks
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iisssds",
            $child_id,
            $teacher_id,
            $assessment_date,
            $assessment_type,
            $domain,
            $score,
            $remarks
        );

        $stmt->execute();
        $stmt->close();
    }

    header("Location: assessment.php");
    exit();
}


/* =========================================================
   GET ASSESSMENT FOR EDIT
========================================================= */

$editAssessment = null;

if (isset($_GET['edit'])) {

    $id = intval($_GET['edit']);

    $stmt = $conn->prepare("
        SELECT *
        FROM assessment
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $editAssessment = $result->fetch_assoc();
    }

    $stmt->close();
}


/* =========================================================
   GET CHILDREN
========================================================= */

$children = [];

$result = $conn->query("
    SELECT
        id,
        enrollment_id,
        user_id
    FROM children
    ORDER BY id DESC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $children[] = $row;
    }
}


/* =========================================================
   GET TEACHERS
========================================================= */

$teachers = [];

$result = $conn->query("
    SELECT
        id,
        first_name,
        middle_name,
        last_name
    FROM teacher
    ORDER BY first_name ASC, last_name ASC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}


/* =========================================================
   GET ASSESSMENTS
========================================================= */

$assessments = [];

$result = $conn->query("
    SELECT
        a.id,
        a.child_id,
        a.teacher_id,
        a.assessment_date,
        a.assessment_type,
        a.domain,
        a.score,
        a.remarks,

        t.first_name,
        t.middle_name,
        t.last_name

    FROM assessment a

    LEFT JOIN teacher t
        ON a.teacher_id = t.id

    ORDER BY a.assessment_date DESC, a.id DESC
");

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $assessments[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Assessment</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #333;
        }

        .page-container {
            width: 100%;
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
            color: #222;
        }

        .page-header p {
            margin-top: 6px;
            color: #777;
            font-size: 14px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .card-title {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
            color: #222;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            margin-bottom: 7px;
            font-size: 14px;
            color: #444;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d7d7d7;
            border-radius: 7px;
            font-size: 14px;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #4f46e5;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .button-area {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            border: none;
            border-radius: 7px;
            padding: 11px 20px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #4f46e5;
            color: white;
        }

        .btn-primary:hover {
            background: #4338ca;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .btn-edit {
            background: #f59e0b;
            color: white;
            padding: 7px 12px;
        }

        .btn-delete {
            background: #dc2626;
            color: white;
            padding: 7px 12px;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        table thead {
            background: #f3f4f6;
        }

        th,
        td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        th {
            font-weight: 700;
            color: #374151;
        }

        td {
            color: #4b5563;
        }

        tr:hover td {
            background: #fafafa;
        }

        .actions {
            display: flex;
            gap: 7px;
        }

        .actions form {
            margin: 0;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #eef2ff;
            color: #4338ca;
            font-size: 12px;
            font-weight: 600;
        }

        .score {
            font-weight: 700;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #888;
        }

        @media (max-width: 768px) {

            .page-container {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

    </style>

</head>


<body>

<div class="page-container">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="page-header">

        <div>

            <h1>Manage Assessment</h1>

            <p>
                Create and manage child assessment records.
            </p>

        </div>

    </div>



    <!-- =====================================================
         ASSESSMENT FORM
    ====================================================== -->

    <div class="card">

        <h2 class="card-title">

            <?php
            if ($editAssessment) {
                echo "Edit Assessment";
            } else {
                echo "Add Assessment";
            }
            ?>

        </h2>


        <form method="POST">

            <input
                type="hidden"
                name="id"
                value="<?= clean($editAssessment['id'] ?? '') ?>"
            >


            <div class="form-grid">


                <!-- CHILD -->

                <div class="form-group">

                    <label for="child_id">
                        Child
                    </label>

                    <select
                        name="child_id"
                        id="child_id"
                        required
                    >

                        <option value="">
                            Select Child
                        </option>

                        <?php foreach ($children as $child): ?>

                            <option
                                value="<?= $child['id'] ?>"
                                <?= (
                                    isset($editAssessment['child_id']) &&
                                    $editAssessment['child_id'] == $child['id']
                                ) ? 'selected' : '' ?>
                            >

                                Child #<?= $child['id'] ?>

                                <?php if (!empty($child['enrollment_id'])): ?>
                                    - Enrollment #<?= clean($child['enrollment_id']) ?>
                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <!-- TEACHER -->

                <div class="form-group">

                    <label for="teacher_id">
                        Teacher
                    </label>

                    <select
                        name="teacher_id"
                        id="teacher_id"
                        required
                    >

                        <option value="">
                            Select Teacher
                        </option>

                        <?php foreach ($teachers as $teacher): ?>

                            <?php

                            $teacherName = trim(
                                $teacher['first_name'] . ' ' .
                                $teacher['middle_name'] . ' ' .
                                $teacher['last_name']
                            );

                            ?>

                            <option
                                value="<?= $teacher['id'] ?>"
                                <?= (
                                    isset($editAssessment['teacher_id']) &&
                                    $editAssessment['teacher_id'] == $teacher['id']
                                ) ? 'selected' : '' ?>
                            >

                                <?= clean($teacherName) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <!-- ASSESSMENT DATE -->

                <div class="form-group">

                    <label for="assessment_date">
                        Assessment Date
                    </label>

                    <input
                        type="date"
                        name="assessment_date"
                        id="assessment_date"
                        value="<?= clean($editAssessment['assessment_date'] ?? date('Y-m-d')) ?>"
                        required
                    >

                </div>



                <!-- ASSESSMENT TYPE -->

                <div class="form-group">

                    <label for="assessment_type">
                        Assessment Type
                    </label>

                    <select
                        name="assessment_type"
                        id="assessment_type"
                        required
                    >

                        <option value="">
                            Select Assessment Type
                        </option>

                        <?php

                        $assessmentTypes = [
                            'Initial',
                            'Monthly',
                            'Quarterly',
                            'Midyear',
                            'Year-end'
                        ];

                        ?>

                        <?php foreach ($assessmentTypes as $type): ?>

                            <option
                                value="<?= clean($type) ?>"
                                <?= (
                                    isset($editAssessment['assessment_type']) &&
                                    $editAssessment['assessment_type'] === $type
                                ) ? 'selected' : '' ?>
                            >

                                <?= clean($type) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <!-- DOMAIN -->

                <div class="form-group">

                    <label for="domain">
                        Developmental Domain
                    </label>

                    <select
                        name="domain"
                        id="domain"
                        required
                    >

                        <option value="">
                            Select Domain
                        </option>

                        <?php

                        $domains = [
                            'Physical',
                            'Cognitive',
                            'Language',
                            'Social-Emotional',
                            'Self-Help'
                        ];

                        ?>

                        <?php foreach ($domains as $domain): ?>

                            <option
                                value="<?= clean($domain) ?>"
                                <?= (
                                    isset($editAssessment['domain']) &&
                                    $editAssessment['domain'] === $domain
                                ) ? 'selected' : '' ?>
                            >

                                <?= clean($domain) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <!-- SCORE -->

                <div class="form-group">

                    <label for="score">
                        Score
                    </label>

                    <input
                        type="number"
                        name="score"
                        id="score"
                        min="0"
                        max="100"
                        step="0.01"
                        placeholder="Enter score"
                        value="<?= clean($editAssessment['score'] ?? '') ?>"
                    >

                </div>



                <!-- REMARKS -->

                <div class="form-group full">

                    <label for="remarks">
                        Remarks
                    </label>

                    <textarea
                        name="remarks"
                        id="remarks"
                        placeholder="Enter assessment remarks..."
                    ><?= clean($editAssessment['remarks'] ?? '') ?></textarea>

                </div>


            </div>



            <!-- BUTTONS -->

            <div class="button-area">

                <button
                    type="submit"
                    name="save_assessment"
                    class="btn btn-primary"
                >

                    <?php
                    echo $editAssessment
                        ? 'Update Assessment'
                        : 'Save Assessment';
                    ?>

                </button>


                <?php if ($editAssessment): ?>

                    <a
                        href="assessment.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>



    <!-- =====================================================
         ASSESSMENT LIST
    ====================================================== -->

    <div class="card">

        <h2 class="card-title">
            Assessment Records
        </h2>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Child
                        </th>

                        <th>
                            Teacher
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Domain
                        </th>

                        <th>
                            Score
                        </th>

                        <th>
                            Remarks
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php if (count($assessments) > 0): ?>

                        <?php foreach ($assessments as $assessment): ?>

                            <?php

                            $teacherName = trim(
                                ($assessment['first_name'] ?? '') . ' ' .
                                ($assessment['middle_name'] ?? '') . ' ' .
                                ($assessment['last_name'] ?? '')
                            );

                            ?>

                            <tr>

                                <!-- ID -->

                                <td>
                                    <?= clean($assessment['id']) ?>
                                </td>


                                <!-- CHILD -->

                                <td>

                                    Child #
                                    <?= clean($assessment['child_id']) ?>

                                </td>


                                <!-- TEACHER -->

                                <td>

                                    <?php if ($teacherName !== ''): ?>

                                        <?= clean($teacherName) ?>

                                    <?php else: ?>

                                        Teacher #<?= clean($assessment['teacher_id']) ?>

                                    <?php endif; ?>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?= date(
                                        'M d, Y',
                                        strtotime($assessment['assessment_date'])
                                    ) ?>

                                </td>


                                <!-- TYPE -->

                                <td>

                                    <span class="badge">

                                        <?= clean(
                                            $assessment['assessment_type']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- DOMAIN -->

                                <td>

                                    <?= clean(
                                        $assessment['domain']
                                    ) ?>

                                </td>


                                <!-- SCORE -->

                                <td>

                                    <?php if ($assessment['score'] !== null): ?>

                                        <span class="score">

                                            <?= number_format(
                                                (float)$assessment['score'],
                                                2
                                            ) ?>

                                        </span>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                                <!-- REMARKS -->

                                <td>

                                    <?= nl2br(
                                        clean($assessment['remarks'])
                                    ) ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <div class="actions">


                                        <!-- EDIT -->

                                        <a
                                            href="assessment.php?edit=<?= $assessment['id'] ?>"
                                            class="btn btn-edit"
                                        >

                                            Edit

                                        </a>


                                        <!-- DELETE -->

                                        <form
                                            method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this assessment?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= $assessment['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                name="delete"
                                                class="btn btn-delete"
                                            >

                                                Delete

                                            </button>

                                        </form>


                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="9"
                                class="no-data"
                            >

                                No assessment records found.

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


</div>

</body>

</html>