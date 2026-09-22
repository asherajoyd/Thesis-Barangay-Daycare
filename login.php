<?php
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard/index.php');
    exit;
}

$pageTitle = 'Login - Barangay Daycare Center';

?>

<?php include('./layout/header.php') ?>

<section class="d-flex align-items-center justify-content-center py-5">

    <div class="container col-12 col-sm-8 col-lg-5 col-xxl-3 py-5">

        <div class="card py-3">

            <div class="card-body p-4">

                <h1 class="fw-bold fs-3 text-center mb-3">
                    Barangay Daycare Center
                </h1>

                <!-- Login Alert -->
                <div id="loginAlert"></div>

                <form id="loginForm">

                    <!-- Email -->
                    <div class="mb-3">

                        <label for="email" class="form-label">
                            Email address
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            placeholder="Enter your email"
                            autocomplete="email"
                            required
                        >

                    </div>

                    <!-- Password -->
                    <div class="mb-3">

                        <label for="password" class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                    </div>

                    <!-- Login Button -->
                    <button
                        type="submit"
                        id="loginButton"
                        class="btn btn-primary w-100"
                    >
                        Sign in
                    </button>

                </form>

            </div>

        </div>

    </div>

</section>

<script>

    $(document).ready(function () {

        $('#loginForm').on('submit', function (e) {

            e.preventDefault();

            const button = $('#loginButton');
            const alertBox = $('#loginAlert');

            const email = $('#email').val().trim();
            const password = $('#password').val();

            // Clear previous alert
            alertBox.html('');

            // Disable button
            button.prop('disabled', true);
            button.text('Signing in...');

            $.ajax({

                type: 'POST',

                url: './api/auth.php',

                data: {
                    email: email,
                    password: password
                },

                dataType: 'json',

                success: function (response) {

                    if (response.success) {

                        alertBox.html(`
                            <div class="alert alert-success fw-bold">
                                ${response.message}
                            </div>
                        `);

                        setTimeout(function () {

                            window.location.href = response.redirect;

                        }, 2500);

                    } else {

                        alertBox.html(`
                            <div class="alert alert-danger">
                                ${response.message}
                            </div>
                        `);

                        button.prop('disabled', false);
                        button.text('Sign in');

                    }

                },

                error: function (xhr) {

                    console.log(xhr.responseText);

                    alertBox.html(`
                        <div class="alert alert-danger">
                            Something went wrong. Please try again.
                        </div>
                    `);

                    button.prop('disabled', false);
                    button.text('Sign in');

                }

            });

        });

    });

</script>

<?php include('./layout/footer.php') ?>

