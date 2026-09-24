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
                <span class="fw-bold text-primary ls3">ENROLLMENT</span>

                <h1 class="fw-bold">
                    Child Enrollment
                </h1>

                <p class="text-muted small mb-0">
                    Complete the form below to submit your child's daycare enrollment application.
                </p>
            </div>
        </div>



        <!-- Enrollment Form -->
        <form id="enrollmentForm" novalidate>

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

                    <!-- STEP 1 -->
                    <div id="step1">

                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label small">
                                    Last Name
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="c_last_name"
                                    class="form-control"
                                    placeholder="Last name"
                                    required
                                >
                            </div>


                            <div class="col-md-4">
                                <label class="form-label small">
                                    First Name
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="c_first_name"
                                    class="form-control"
                                    placeholder="First name"
                                    required
                                >
                            </div>


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


                            <div class="col-md-6">
                                <label class="form-label small">
                                    Gender
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="c_gender"
                                    class="form-select"
                                    required
                                >
                                    <option value="" selected disabled>
                                        Select gender
                                    </option>

                                    <option value="male">
                                        Male
                                    </option>

                                    <option value="female">
                                        Female
                                    </option>
                                </select>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label small">
                                    Date of Birth
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="date"
                                    name="c_birthdate"
                                    class="form-control"
                                    required
                                >
                            </div>


                            <div class="col-12">
                                <label class="form-label small">
                                    Complete Address
                                    <span class="text-danger">*</span>
                                </label>

                                <textarea
                                    name="c_address"
                                    class="form-control"
                                    rows="2"
                                    placeholder="House No., Street, Barangay, Municipality/City, Province"
                                    required
                                ></textarea>
                            </div>


                            <div class="col-12">
                                <label class="form-label small">
                                    Place of Birth
                                    <span class="text-danger">*</span>
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

                    </div>


                    <!-- STEP 2 -->
                    <div id="step2" class="d-none">

                        <?php foreach ($parents as $prefix => $title): ?>

                            <h6 class="fw-bold mb-3 <?= $prefix !== 'f' ? 'mt-4' : '' ?>">
                                <?= $title ?> Information
                            </h6>

                            <div class="row g-3">

                                <?php foreach ([
                                    'first_name'  => 'First Name',
                                    'middle_name' => 'Middle Name',
                                    'last_name'   => 'Last Name'
                                ] as $field => $label): ?>

                                    <?php
                                    $required =
                                        $prefix === 'g' &&
                                        in_array($field, [
                                            'first_name',
                                            'last_name'
                                        ]);
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


                                <?php $required = $prefix === 'g'; ?>

                                <div class="col-12">

                                    <label class="form-label small">
                                        Email

                                        <?php if ($required): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>

                                    <input
                                        type="email"
                                        name="<?= $prefix ?>_email"
                                        class="form-control"
                                        placeholder="name@example.com"
                                        <?= $required ? 'required' : '' ?>
                                    >

                                </div>

                            </div>

                        <?php endforeach; ?>


                        <p class="small text-muted mt-4 mb-0">
                            Guardian information is required.
                        </p>

                    </div>

                </div>
            </div>

            <div id="formAlert" class="alert d-none mt-3" role="alert"></div>
            

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
                <button type="button" id="backBtn" class="btn btn-warning px-4">
                    Back
                </button>

                <button type="submit" id="submitBtn" class="btn btn-primary px-4">
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

    const childDescription =
        "Provide your child's basic personal information.";

    const parentTitle = 'Parent / Guardian Information';

    const parentDescription =
        "Provide the contact information of the child's parent or guardian.";

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

        step1Buttons.toggleClass(
            'd-none',
            !isChild
        );

        step2Buttons
            .toggleClass('d-none', isChild)
            .toggleClass('d-flex', !isChild);

        title.text(
            isChild
                ? childTitle
                : parentTitle
        );

        description.text(
            isChild
                ? childDescription
                : parentDescription
        );

    }

    function validateFields(container) {

        let valid = true;

        container
            .find('[required]')
            .each(function () {

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

    form.on(
        'input change',
        '[required]',
        function () {

            if (this.checkValidity()) {

                $(this).removeClass('is-invalid');

            }

        }
    );

    $('#backBtn').on('click', function () {
        hideAlert();
        showStep(1);
    });

    form.on('submit', function (e) {

        e.preventDefault();

        hideAlert();

        if (!validateFields(form)) {
            showAlert( 'danger', 'Please complete all required fields before submitting.' );
            return;
        }

        submitBtn
            .prop('disabled', true)
            .text('Submitting...');
        $.ajax({
            url: './api/req-enrollment.php',
            type: 'POST',
            data:
                form.serialize() +
                '&action=enrollment',
            dataType: 'json',
            success: function (response) {

                if (response.status === 'success') {
                    showAlert(
                        'success',
                        response.message ||
                        'Enrollment submitted successfully.'
                    );
                    form[0].reset();
                    form
                        .find('.is-invalid')
                        .removeClass('is-invalid');
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
                showAlert(
                    'danger',
                    'Something went wrong while submitting the enrollment.'
                );

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
