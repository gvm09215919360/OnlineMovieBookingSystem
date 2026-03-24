<?php
// header.php
// Do NOT call session_start() here because it is already in connect.php
?>

<!-- Favicons -->
<link href="assets/img/favicon.png" rel="icon">
<link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

<!-- ✅ PWA Manifest -->
<link rel="manifest" href="/OnlineMovieBookingSystem---PHP-MYSQL-main/manifest.json">
<meta name="theme-color" content="#0F173D">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="MovieBook">
<link rel="apple-touch-icon" href="/OnlineMovieBookingSystem---PHP-MYSQL-main/icons/icon-192x192.png">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" rel="stylesheet">

<!-- Vendor CSS Files -->
<link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

<!-- Main CSS -->
<link href="assets/css/style.css" rel="stylesheet">

<style>
  /* BIG BLACK HEADER */
  #header {
    position: sticky;
    top: 0;
    width: 100%;
    background: #1F3B85; /* solid black */
    box-shadow: 0 4px 18px rgba(0,0,0,0.8);
    z-index: 999;
    padding: 1.5rem 0; /* BIGGER HEADER */
  }

  #header .logo img {
    height: 80px; /* bigger logo */
    object-fit: contain;
  }

  /* Navbar layout */
  #navbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    width: 100%;
  }

  /* Right side navigation */
  .nav-right {
    display: flex;
    align-items: center;
    gap: 30px;
  }

  .nav-right a {
    color: #fff;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    position: relative;
    transition: all 0.3s ease;
    font-size: 1.15rem;
    text-decoration: none;
  }

  /* ACTIVE + HOVER = BLUE */
  /* Navbar Links Gradient Hover / Active */
.nav-right a:hover,
.nav-right a.active {
    background: linear-gradient(90deg, #00d4ff, #0096ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text; /* fallback for other browsers */
}

/* Guest button hover gradient */
.guest-link:hover {
    background: linear-gradient(90deg, #00d4ff, #0096ff);
    color: #fff !important;
}

/* Optional: Make Logout icon match gradient on hover */
.nav-right a[href="logout.php"]:hover i {
    background: linear-gradient(90deg, #00d4ff, #0096ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}


  .nav-right a.active::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    right: 0;
    height: 3px;
    background: #0d6efd;
    border-radius: 3px;
  }

  /* Guest button */
  .guest-link {
    background: #0d6efd;
    color: #fff !important;
    padding: 10px 18px;
    border-radius: 30px;
    font-weight: 600;
    transition: 0.3s;
  }

  .guest-link:hover {
    background: #fff;
    color: #0d6efd !important;
  }

  /* Mobile view */
  @media (max-width: 992px) {
    #navbar {
      flex-direction: column;
      background: #0b0f1a;
      padding: 30px;
      display: none;
    }

    #navbar.active {
      display: flex;
      align-items: flex-start;
    }

    .nav-right {
      flex-direction: column;
      align-items: flex-start;
      gap: 22px;
    }

    .mobile-nav-toggle {
      display: block;
      color: #fff;
      font-size: 2rem;
      cursor: pointer;
    }
  }

  .mobile-nav-toggle {
    display: none;
  }
</style>

<header id="header" class="d-flex align-items-center">
  <div class="container d-flex align-items-center justify-content-between">

    <!-- Logo -->
    <a href="index.php" class="logo">
      <img src="assets/img/logo.png" alt="logo">
    </a>

    <?php
      $current = basename($_SERVER['PHP_SELF']);
      function nav_active($target, $current) {
        return $current === $target ? 'active' : '';
      }
    ?>

    <!-- Navbar -->
    <nav id="navbar" class="navbar">
      <div class="nav-right">
        <a href="index.php" class="<?= nav_active('index.php', $current); ?>">Home</a>
        <a href="alltheater.php" class="<?= nav_active('alltheater.php', $current); ?>">Theater</a>

        <?php if(isset($_SESSION['uid']) && ($_SESSION['type'] ?? 0) == 2): ?>
          <a href="viewuserbooking.php" class="<?= nav_active('viewuserbooking.php', $current); ?>">Booking</a>
        <?php endif; ?>

        <?php if(!isset($_SESSION['uid'])): ?>
          <a href="login.php" class="<?= nav_active('login.php', $current); ?>">
            <i class="bi bi-box-arrow-in-right"></i> Login
          </a>
          <a href="register.php" class="<?= nav_active('register.php', $current); ?>">
            <i class="bi bi-person-plus-fill"></i> Register
          </a>
          <a href="index.php" class="guest-link">Continue as Guest</a>
        <?php else: ?>
          <a href="viewprofile.php" class="<?= nav_active('viewprofile.php', $current); ?>">Profile</a>
          <a href="logout.php">Logout</a>
        <?php endif; ?>
      </div>

      <!-- Mobile Toggle -->
      <i class="bi bi-list mobile-nav-toggle" id="mobileToggle"></i>
    </nav>
  </div>
</header>

<script>
  const toggle = document.getElementById('mobileToggle');
  const navbar = document.getElementById('navbar');

  if (toggle) {
    toggle.addEventListener('click', () => navbar.classList.toggle('active'));
  }

  // Ensure active link highlight
  document.querySelectorAll('#navbar a').forEach(link => {
    if (link.href === location.href) {
      link.classList.add('active');
    }
  });
</script>