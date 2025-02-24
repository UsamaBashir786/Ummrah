<!-- Navbar -->
<nav class="p-5 bg-gradient-to-r from-white to-gray-100 text-gray-800 shadow-lg fixed top-0 w-full z-50">
  <div class="container mx-auto flex items-center justify-between">
    <!-- Logo Section -->
    <div class="flex items-center space-x-4">
      <img id="logo" src="assets/images/logo.png" alt="Logo" style="width: 50px;" class="rounded-full border-2 border-gray-800">
      <h1 class="font-bold text-xl">UMMRAH</h1>
    </div>

    <!-- Navbar Links -->
    <div class="hidden md:flex space-x-8">
      <a href="index.php" class="menu-item text-lg hover:text-teal-500 transition-all duration-300 ease-in-out">
        Home
      </a>
      <a href="packages.php" class="menu-item text-lg hover:text-teal-500 transition-all duration-300 ease-in-out">
        Packages
      </a>
      <a href="about.php" class="menu-item text-lg hover:text-teal-500 transition-all duration-300 ease-in-out">
        About Us
      </a>
      <a href="contact.php" class="menu-item text-lg hover:text-teal-500 transition-all duration-300 ease-in-out">
        Contact
      </a>

      <!-- Dropdown Menu -->
      <div class="relative group">
        <button class="bold menu-item text-lg hover:text-teal-500 transition-all duration-300 ease-in-out">
          More
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"></path>
          </svg>
        </button>
        <div class="absolute hidden group-hover:block bg-white text-gray-800 shadow-lg rounded-lg w-60  right-0">
          <a href="transportation.php" class="block px-4 py-2 text-lg hover:text-teal-500">Transportation</a>
          <a href="flights.php" class="block px-4 py-2 text-lg hover:text-teal-500">Flights</a>
          <a href="hotels.php" class="block px-4 py-2 text-lg hover:text-teal-500">Hotels</a>
        </div>
      </div>
    </div>

    <!-- Login/Register or Dashboard/Logout links -->
    <div class="hidden md:flex space-x-4">
      <?php if(isset($_SESSION['user_id'])): ?>
        <a href="user/index.php" class="text-lg text-teal-500 hover:text-teal-700 transition-all duration-300 ease-in-out">
          Dashboard
        </a>
        <a href="logout.php" class="text-lg text-teal-500 hover:text-teal-700 transition-all duration-300 ease-in-out">
          Logout
        </a>
      <?php else: ?>
        <a href="login.php" class="text-lg text-teal-500 hover:text-teal-700 transition-all duration-300 ease-in-out">
          Login
        </a>
        <a href="register.php" class="text-lg text-teal-500 hover:text-teal-700 transition-all duration-300 ease-in-out">
          Register
        </a>
      <?php endif; ?>
    </div>

    <!-- Hamburger Menu Icon -->
    <button id="hamburger" onclick="toggleMenu()" class="md:hidden text-gray-800 focus:outline-none">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
      </svg>
    </button>
  </div>

  <!-- Mobile Menu -->
  <div id="navbar" class="hidden md:hidden bg-gray-100 p-4 space-y-4">
    <a href="index.php" class="block text-lg hover:text-teal-500 menu-item transition-all duration-300 ease-in-out">
      Home
    </a>
    <a href="about.php" class="block text-lg hover:text-teal-500 menu-item transition-all duration-300 ease-in-out">
      About Us
    </a>
    <a href="contact.php" class="block text-lg hover:text-teal-500 menu-item transition-all duration-300 ease-in-out">
      Contact
    </a>
    <a href="transportation.php" class="block text-lg hover:text-teal-500 menu-item transition-all duration-300 ease-in-out">
      Transportation
    </a>
    <a href="packages.php" class="block text-lg hover:text-teal-500 menu-item transition-all duration-300 ease-in-out">
      Packages
    </a>
    <a href="flights.php" class="block text-lg hover:text-teal-500 menu-item transition-all duration-300 ease-in-out">
      Flights
    </a>
    <a href="hotels.php" class="block text-lg hover:text-teal-500 menu-item transition-all duration-300 ease-in-out">
      Hotels
    </a>
    <!-- Login/Register or Dashboard/Logout in Mobile Menu -->
    <?php if(isset($_SESSION['user_id'])): ?>
      <a href="user/index.php" class="block text-lg hover:text-teal-500 menu-item transition-all duration-300 ease-in-out">
        Dashboard
      </a>
      <a href="logout.php" class="block text-lg hover:text-teal-500 menu-item transition-all duration-300 ease-in-out">
        Logout
      </a>
    <?php else: ?>
      <a href="login.php" class="block text-lg hover:text-teal-500 menu-item transition-all duration-300 ease-in-out">
        Login
      </a>
      <a href="register.php" class="block text-lg hover:text-teal-500 menu-item transition-all duration-300 ease-in-out">
        Register
      </a>
    <?php endif; ?>
  </div>
</nav>

<style>
  .relative:hover .absolute {
    display: block;
  }
</style>

<script>
  window.onload = () => {
    gsap.fromTo("nav", {
      y: "-100%",
      opacity: 0
    }, {
      y: "0%",
      opacity: 1,
      duration: 1,
      ease: "power4.out"
    });

    gsap.fromTo("#logo", {
      scale: 0,
      opacity: 0
    }, {
      scale: 1,
      opacity: 1,
      duration: 1,
      ease: "elastic.out(1, 0.5)"
    });

    gsap.fromTo(".menu-item", {
      opacity: 0,
      y: 20
    }, {
      opacity: 1,
      y: 0,
      duration: 0.2,
      stagger: 0.3,
      ease: "power4.out",
    });

    gsap.fromTo("#hamburger", {
      rotate: -180,
      opacity: 0
    }, {
      rotate: 0,
      opacity: 1,
      duration: 1,
      ease: "back.out(1.7)"
    });
  };

  // Function to toggle the mobile menu
  function toggleMenu() {
    const navbar = document.getElementById('navbar');
    navbar.classList.toggle('hidden');
  }
</script>
