<?php $pageTitle = 'Login - Barangay Daycare Center'; ?>
<?php include('./layout/header.php') ?>

<section class="d-flex align-items-center justify-content-center py-5">
    <div class="container col-12 col-sm-8 col-lg-3 py-5">
        <div class="card rounded-4 shadow">

        <div class="card-body p-4">
            <h1 class="fw-bold fs-3 text-center mb-3">Barangay Daycare Center</h1>
    
            <form class="">
                <div class="mb-3">
                    <label for="exampleInputEmail1" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
                    <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                </div>
                <div class="mb-3">
                    <label for="exampleInputPassword1" class="form-label">Password</label>
                    <input type="password" class="form-control" id="exampleInputPassword1">
                </div>
                <button type="submit" class="btn btn-primary w-100">Sign in</button>
            </form>

        </div>
        </div>
    </div>


</section>



<?php include('./layout/footer.php') ?>

