<?php

$pageTitle = "Schedule";

require_once '../guard.php';
include "../../../api/conn.php";

// ==========================================================
// PAGE
// ==========================================================

include '../layout/header.php';

?>

<section>

   <!-- ======================================================
         PAGE HEADER
         ====================================================== -->

    <div class="d-flex align-items-center justify-content-between mb-3">

        <h2 class="fw-bold fs-4 mb-0">
            <?= $pageTitle ?>
        </h2>


        <a
            href="#"
            class="btn btn-primary">

            <i class="bi bi-plus-lg me-1"></i>

            Add Schedule

        </a>

    </div>

    



</section>

<?php include '../layout/footer.php'; ?>