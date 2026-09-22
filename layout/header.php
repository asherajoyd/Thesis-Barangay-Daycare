<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- css -->
    <link href="theme.css" rel="stylesheet">
    <link href="custom.css" rel="stylesheet">

    <!-- bootstrap css -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <!-- bootstrap js and icon -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <!-- js library -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <title><?= $pageTitle ?? 'Barangay Daycare Center' ?></title>
</head>
<body>
  
  <nav class="navbar navbar-expand-lg bg-white py-3 sticky-top border">
    <div class="container">
      <a class="navbar-brand" href="index.php">Navbar</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse py-md-0 py-3" id="navbarSupportedContent">
        <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-3 gap-lg-4">
          <li class="nav-item">
            <a class="nav-link" aria-current="page" href="#">Home</a>
          </li>

          <li class="nav-item">
            <a class="nav-link" aria-current="page" href="#about">About</a>
          </li>
        </ul>
      
        <div class="d-flex gap-3">
          <a href="enrollment.php" class="btn btn-primary">
              Erollment
          </a>
          <a href="login.php" class="btn btn-warning">
              Login
          </a>
        </div>
      </div>
    </div>
  </nav>




