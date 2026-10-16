<?php

$pageTitle = 'Enrollment - Barangay Daycare Center';
include('./layout/header.php');

$parents = [
    'f' => 'Father',
    'm' => 'Mother',
    'g' => 'Guardian'
];

?>

<section class="py-5">
    <div class="container">

        <!-- Header -->
        <div class="card p-4">
            <div class="card-body">

                <span class="fw-bold text-primary ls3">
                    ENROLLMENT
                </span>

                <h1 class="fw-bold">
                    Child Enrollment
                </h1>

                <p class="text-muted small mb-0">
                    Complete the form below to submit your child's daycare enrollment application.
                </p>

            </div>
        </div>


        <!-- Enrollment Form -->
        <form
            id="enrollmentForm"
            enctype="multipart/form-data"
            novalidate
        >

            <div class="card mt-3">

                <!-- Form Header -->
                <div class="card-header bg-white rounded-top-4 p-4">

                    <h3 id="stepTitle" class="fs-5 fw-bold mb-1">
                        Child Information
                    </h3>

                    <p id="stepDescription" class="mb-0 text-muted small">
                        Provide your child's basic personal information.
                    </p>

                </div>


                <div class="card-body p-4">

                    <!-- =====================================================
                         STEP 1 — CHILD INFORMATION
                    ====================================================== -->
                    <div id="step1">

                        <div class="row g-3">

                            <!-- Last Name -->
                            <div class="col-md-4">
                                <label class="form-label small">
                                    Last Name <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="c_last_name"
                                    class="form-control"
                                    placeholder="Last name"
                                    required
                                >
                            </div>

                            <!-- First Name -->
                            <div class="col-md-4">
                                <label class="form-label small">
                                    First Name <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="c_first_name"
                                    class="form-control"
                                    placeholder="First name"
                                    required
                                >
                            </div>

                            <!-- Middle Name -->
                            <div class="col-md-4">
                                <label class="form-label small">
                                    Middle Name
                                </label>
                                <input
                                    type="text"
                                    name="c_middle_name"
                                    class="form-control"
                                    placeholder="Middle name"
                                >
                            </div>

                            <!-- Gender -->
                            <div class="col-md-6">
                                <label class="form-label small">
                                    Gender <span class="text-danger">*</span>
                                </label>
                                <select name="c_gender" class="form-select" required>
                                    <option value="" selected disabled>Select gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>

                            <!-- Date of Birth -->
                            <div class="col-md-6">
                                <label class="form-label small">
                                    Date of Birth <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="date"
                                    name="c_birthdate"
                                    class="form-control"
                                    required
                                >
                            </div>

                            <!-- Address -->
                            <div class="col-12">
                                <label class="form-label small">
                                    Complete Address <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    name="c_address"
                                    class="form-control"
                                    rows="2"
                                    placeholder="House No., Street, Barangay, Municipality/City, Province"
                                    required
                                ></textarea>
                            </div>

                            <!-- Place of Birth -->
                            <div class="col-12">
                                <label class="form-label small">
                                    Place of Birth <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="c_place_of_birth"
                                    class="form-control"
                                    placeholder="Place of birth"
                                    required
                                >
                            </div>

                        </div>


                        <!-- ==============================
                             BIRTH CERTIFICATE
                        =============================== -->
                        <div class="border-top mt-4 pt-4">

                            <h6 class="fw-bold mb-1">Birth Certificate</h6>

                            <p class="small text-muted mb-3">
                                Upload Birth Certificate for the enrollment application.
                            </p>

                            <div class="mb-3">
                                <label class="form-label small">
                                    Document Type <span class="text-danger">*</span>
                                </label>
                                <select
                                    name="file_type_birth"
                                    class="form-select"
                                    required
                                >
                                    <option value="Child Birth Certificate" selected>
                                        Child Birth Certificate
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label small">
                                    Upload Document <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="file"
                                    name="file_image_birth"
                                    class="form-control enrollment-file"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    required
                                >
                                <div class="form-text">
                                    Accepted files: JPG, JPEG, PNG, PDF. Maximum file size: 5MB.
                                </div>
                            </div>

                        </div>


                        <!-- ==============================
                             YELLOW CARD
                        =============================== -->
                        <div class="border-top mt-4 pt-4">

                            <h6 class="fw-bold mb-1">Yellow Card</h6>

                            <p class="small text-muted mb-3">
                                Upload Yellow Card for the enrollment application.
                            </p>

                            <div class="mb-3">
                                <label class="form-label small">
                                    Document Type <span class="text-danger">*</span>
                                </label>
                                <select
                                    name="file_type_yellow"
                                    class="form-select"
                                    required
                                >
                                    <option value="Yellow Card" selected>
                                        Yellow Card
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label small">
                                    Upload Document <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="file"
                                    name="file_image_yellow"
                                    class="form-control enrollment-file"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    required
                                >
                                <div class="form-text">
                                    Accepted files: JPG, JPEG, PNG, PDF. Maximum file size: 5MB.
                                </div>
                            </div>

                        </div>

                    </div>


                    <!-- =====================================================
                         STEP 2 — PARENT / GUARDIAN
                    ====================================================== -->
                    <div id="step2" class="d-none">

                        <?php foreach ($parents as $prefix => $title): ?>

                            <h6 class="fw-bold mb-3 <?= $prefix !== 'f' ? 'mt-4' : '' ?>">
                                <?= $title ?> Information
                            </h6>

                            <div class="row g-3">

                                <?php foreach ([
                                    'first_name'  => 'First Name',
                                    'middle_name' => 'Middle Name (optional)',
                                    'last_name'   => 'Last Name'
                                ] as $field => $label): ?>

                                    <?php
                                    $required =
                                        $prefix === 'g' &&
                                        in_array($field, ['first_name', 'last_name']);
                                    ?>

                                    <div class="col-md-4">
                                        <label class="form-label small">
                                            <?= $label ?>
                                            <?php if ($required): ?>
                                                <span class="text-danger">*</span>
                                            <?php endif; ?>
                                        </label>
                                        <input
                                            type="text"
                                            name="<?= $prefix ?>_<?= $field ?>"
                                            class="form-control"
                                            placeholder="<?= $label ?>"
                                            <?= $required ? 'required' : '' ?>
                                        >
                                    </div>

                                <?php endforeach; ?>


                                <!-- Guardian: Email + Contact -->
                                <?php if ($prefix === 'g'): ?>

                                    <div class="col-md-6">
                                        <label class="form-label small">
                                            Email <span class="text-danger">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            name="g_email"
                                            class="form-control"
                                            placeholder="name@example.com"
                                            required
                                        >
                                        <div class="form-text">
                                            This will be your login account.
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label small">
                                            Contact Number
                                        </label>
                                        <input
                                            type="text"
                                            name="g_contact"
                                            class="form-control"
                                            placeholder="09XX XXX XXXX"
                                        >
                                    </div>

                                <?php endif; ?>

                            </div>

                        <?php endforeach; ?>


                        <!-- Proof of Address -->
                        <div class="border-top mt-4 pt-4">

                            <h6 class="fw-bold mb-1">Proof of Address</h6>

                            <p class="small text-muted mb-3">
                                Upload one supporting document of address for the enrollment application.
                            </p>

                            <div class="mb-3">
                                <label class="form-label small">
                                    Document Type <span class="text-danger">*</span>
                                </label>
                                <select
                                    name="file_type_address"
                                    class="form-select"
                                    required
                                >
                                    <option value="Proof of Address" selected>
                                        Proof of Address / Utility Bill
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label small">
                                    Upload Document <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="file"
                                    name="file_image_address"
                                    class="form-control enrollment-file"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    required
                                >
                                <div class="form-text">
                                    Accepted files: JPG, JPEG, PNG, PDF. Maximum file size: 5MB.
                                </div>
                            </div>

                        </div>


                        <p class="small text-muted mt-4 mb-0">
                            Guardian information and one supporting document are required.
                        </p>

                    </div>

                </div>

            </div>


            <!-- Alert -->
            <div
                id="formAlert"
                class="alert d-none mt-3"
                role="alert"
            ></div>


            <!-- Step 1 Buttons -->
            <div id="step1Buttons" class="text-end mt-4">
                <button
                    type="button"
                    id="continueBtn"
                    class="btn btn-primary px-4"
                >
                    Continue
                </button>
            </div>


            <!-- Step 2 Buttons -->
            <div id="step2Buttons" class="d-none justify-content-between mt-4">
                <button
                    type="button"
                    id="backBtn"
                    class="btn btn-warning px-4"
                >
                    Back
                </button>
                <button
                    type="submit"
                    id="submitBtn"
                    class="btn btn-primary px-4"
                >
                    Submit Application
                </button>
            </div>

        </form>

    </div>
</section>


<script>

$(function () {

    const form = $('#enrollmentForm');
    const step1 = $('#step1');
    const step2 = $('#step2');
    const step1Buttons = $('#step1Buttons');
    const step2Buttons = $('#step2Buttons');
    const title = $('#stepTitle');
    const description = $('#stepDescription');
    const submitBtn = $('#submitBtn');
    const formAlert = $('#formAlert');

    const childTitle = 'Child Information';
    const childDescription = "Provide your child's basic personal information.";
    const parentTitle = 'Parent / Guardian Information';
    const parentDescription = "Provide the contact information of the child's parent or guardian.";


    function showAlert(type, message) {
        formAlert
            .removeClass('d-none alert-success alert-danger alert-warning')
            .addClass('alert-' + type)
            .html(message);

        $('html, body').animate({
            scrollTop: formAlert.offset().top - 20
        }, 300);
    }


    function hideAlert() {
        formAlert
            .addClass('d-none')
            .removeClass('alert-success alert-danger alert-warning')
            .html('');
    }


    function showStep(step) {

        const isChild = step === 1;

        step1.toggleClass('d-none', !isChild);
        step2.toggleClass('d-none', isChild);

        step1Buttons.toggleClass('d-none', !isChild);

        step2Buttons
            .toggleClass('d-none', isChild)
            .toggleClass('d-flex', !isChild);

        title.text(isChild ? childTitle : parentTitle);
        description.text(isChild ? childDescription : parentDescription);
    }


    function validateFields(container) {

        let valid = true;

        container.find('[required]').each(function () {

            const field = $(this);

            if (!this.checkValidity()) {
                field.addClass('is-invalid');
                valid = false;
            } else {
                field.removeClass('is-invalid');
            }
        });

        return valid;
    }


    $('#continueBtn').on('click', function () {

        hideAlert();

        if (validateFields(step1)) {
            showStep(2);
        } else {
            showAlert(
                'danger',
                'Please complete all required child information.'
            );
        }
    });


    form.on('input change', '[required]', function () {
        if (this.checkValidity()) {
            $(this).removeClass('is-invalid');
        }
    });


    $('#backBtn').on('click', function () {
        hideAlert();
        showStep(1);
    });


    form.on('submit', function (e) {

        e.preventDefault();
        hideAlert();

        if (!validateFields(form)) {
            showAlert(
                'danger',
                'Please complete all required fields before submitting.'
            );
            return;
        }


        /* Validate all file sizes */
        let fileError = false;

        $('.enrollment-file').each(function () {

            if (
                this.files.length > 0 &&
                this.files[0].size > 5 * 1024 * 1024
            ) {
                fileError = true;
            }
        });

        if (fileError) {
            showAlert(
                'danger',
                'Each uploaded file must not exceed 5MB.'
            );
            return;
        }


        submitBtn
            .prop('disabled', true)
            .text('Submitting...');


        const formData = new FormData(
            document.getElementById('enrollmentForm')
        );

        formData.append('action', 'enrollment');


        $.ajax({

            url: './api/req-enrollment.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,

            success: function (response) {

                if (response.status === 'success') {

                    showAlert(
                        'success',
                        response.message ||
                        'Enrollment application submitted successfully.'
                    );

                    form[0].reset();
                    form.find('.is-invalid').removeClass('is-invalid');
                    showStep(1);

                } else {

                    showAlert(
                        'danger',
                        response.message ||
                        'Unable to submit enrollment.'
                    );
                }
            },

            error: function (xhr) {

                console.log(xhr.responseText);

                let message =
                    'Something went wrong while submitting the enrollment.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                showAlert('danger', message);
            },

            complete: function () {

                submitBtn
                    .prop('disabled', false)
                    .text('Submit Application');
            }

        });

    });

});

</script>


<?php include('./layout/footer.php'); ?>