<?php
$pageTitle = 'Enrollment - Barangay Daycare Center';
include('./layout/header.php');
?>

<section class="py-5">
    <div class="container">

       <div class="p-4 card">
            <div class="card-body">
                <span class="fw-bold text-primary ls3">ENROLLMENT</span>
                <h1 class="fw-bold">Child Enrollment</h1>
                <p class="text-muted small mb-0">
                    Complete the form below to submit your child's daycare enrollment application.
                </p>
            </div>
       </div>

        <form method="POST">

            <div class="card mt-3">

                <div class="card-header bg-white rounded-top-4 p-4">
                    <h3 id="stepTitle" class="fs-5 fw-bold mb-1">
                        Child Information
                    </h3>
                    <p id="stepDescription" class="mb-0 text-muted small">
                        Provide your child's basic personal information. Fields marked with * are required.
                    </p>
                </div>

                <div class="card-body p-4">

                    <!-- STEP 1 -->
                    <div id="step1">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label small">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" placeholder="Last name">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" placeholder="First name">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" placeholder="Middle name">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small">Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select">
                                    <option value="" selected disabled>Select gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" name="date_of_birth" class="form-control">
                            </div>

                            <div class="col-12">
                                <label class="form-label small">Complete Address <span class="text-danger">*</span></label>
                                <textarea name="address" class="form-control" rows="2" placeholder="House No., Street, Barangay, Municipality/City, Province"></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label small">Place of Birth <span class="text-danger">*</span></label>
                                <input type="text" name="place_of_birth" class="form-control" placeholder="Place of birth">
                            </div>

                        </div>
                    </div>

                    <!-- STEP 2 -->
                    <div id="step2" class="d-none">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label small">Father's Full Name</label>
                                <input type="text" name="father_name" class="form-control" placeholder="Father's name">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small">Father's Email</label>
                                <input type="email" name="father_email" class="form-control" placeholder="name@example.com">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small">Mother's Full Name</label>
                                <input type="text" name="mother_name" class="form-control" placeholder="Mother's name">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small">Mother's Email</label>
                                <input type="email" name="mother_email" class="form-control" placeholder="name@example.com">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small">Guardian's Full Name</label>
                                <input type="text" name="guardian_name" class="form-control" placeholder="Guardian's name">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small">Guardian's Email</label>
                                <input type="email" name="guardian_email" class="form-control" placeholder="name@example.com">
                            </div>

                        </div>

                        <p class="small text-muted small mt-4 mb-0">
                            Please provide at least one parent or guardian contact information.
                        </p>
                    </div>

                </div>
            </div>

            <!-- BUTTONS -->
            <div id="step1Buttons" class="text-end mt-4">
                <button type="button" class="btn btn-primary px-4" onclick="nextStep()">
                    Continue
                </button>
            </div>

            <div id="step2Buttons" class="d-none d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-warning px-4" onclick="prevStep()">
                    Back
                </button>

                <button type="submit" class="btn btn-primary px-4">
                    Submit Application
                </button>
            </div>

        </form>
    </div>
</section>

<script>
function nextStep() {
    $('#step1, #step1Buttons').addClass('d-none');
    $('#step2, #step2Buttons').removeClass('d-none');
    $('#stepTitle').text('Parent / Guardian Information');
    $('#stepDescription').text("Provide the contact information of the child's parent or guardian.");
}

function prevStep() {
    $('#step2, #step2Buttons').addClass('d-none');
    $('#step1, #step1Buttons').removeClass('d-none');
    $('#stepTitle').text('Child Information');
    $('#stepDescription').text("Provide your child's basic personal information. Fields marked with * are required.");
}
</script>

<?php include('./layout/footer.php') ?>
