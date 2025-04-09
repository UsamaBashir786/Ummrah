<?php
session_start();
include('connection/connection.php');

// Initialize a variable to store the alert message
$alert = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "ummrah";

  try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $message = $_POST['message'];
    $created_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO contacts (fullname, email, message, created_at) VALUES (:fullname, :email, :message, :created_at)");
    $stmt->bindParam(':fullname', $fullname);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':created_at', $created_at);
    $stmt->execute();

    // Set success message
    $alert = "
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Message sent successfully!',
                        icon: 'success'
                    }).then((result) => {
                        window.location.href = 'index.php';
                    });
                });
            </script>";
  } catch (PDOException $e) {
    // Set error message
    $alert = "
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Something went wrong!',
                        icon: 'error'
                    });
                });
            </script>";
  }
  $conn = null;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <?php include 'includes/css-links.php' ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <?php echo $alert; // Output the alert script here, after SweetAlert is loaded 
  ?>
</head>

<body class="bg-gray-100 font-sans">
  <?php include 'includes/navbar.php' ?>
  <div class="my-6">&nbsp;</div>

  <section class="min-h-screen bg-cover" style="background-image: url('https://images.unsplash.com/photo-1563986768609-322da13575f3?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1470&q=80')">
    <div class="flex flex-col min-h-screen bg-black/60">
      <div class="container flex flex-col flex-1 px-6 py-12 mx-auto">
        <div class="flex-1 lg:flex lg:items-center lg:-mx-6">
          <div class="text-white lg:w-1/2 lg:mx-6">
            <h1 class="text-2xl font-semibold capitalize lg:text-3xl">Get in Touch</h1>
            <p class="max-w-xl mt-6">
              Have questions? We'd love to hear from you! You can reach us through the contact form on the right or via our contact details below
            </p>

            <div class="mt-6 md:mt-8">
              <h3 class="text-gray-300">Follow us</h3>
              <div class="flex mt-4 -mx-1.5">
                <a class="mx-1.5 text-white transition-colors duration-300 transform hover:text-blue-500" href="#">
                  <svg class="w-10 h-10 fill-current" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.6668 6.67334C18.0002 7.00001 17.3468 7.13268 16.6668 7.33334C15.9195 6.49001 14.8115 6.44334 13.7468 6.84201C12.6822 7.24068 11.9848 8.21534 12.0002 9.33334V10C9.83683 10.0553 7.91016 9.07001 6.66683 7.33334C6.66683 7.33334 3.87883 12.2887 9.3335 14.6667C8.0855 15.498 6.84083 16.0587 5.3335 16C7.53883 17.202 9.94216 17.6153 12.0228 17.0113C14.4095 16.318 16.3708 14.5293 17.1235 11.85C17.348 11.0351 17.4595 10.1932 17.4548 9.34801C17.4535 9.18201 18.4615 7.50001 18.6668 6.67268V6.67334Z" />
                  </svg>
                </a>
              </div>
            </div>
          </div>

          <div class="mt-8 lg:w-1/2 lg:mx-6">
            <div class="w-full px-8 py-10 mx-auto overflow-hidden bg-white shadow-2xl rounded-xl dark:bg-gray-900 lg:max-w-xl">
              <h1 class="text-xl font-medium text-gray-700 dark:text-gray-200">Contact form</h1>

              <form class="mt-6" method="POST">
                <div class="flex-1">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Full Name</label>
                  <input type="text" name="fullname" required placeholder="Type Your Full Name" class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring" />
                </div>

                <div class="flex-1 mt-6">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Email address</label>
                  <input type="email" name="email" required placeholder="Type Your Email" class="block w-full px-5 py-3 mt-2 text-gray-700 bg-white border border-gray-200 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring" />
                </div>

                <div class="w-full mt-6">
                  <label class="block mb-2 text-sm text-gray-600 dark:text-gray-200">Message</label>
                  <textarea name="message" required class="block w-full h-32 px-5 py-3 mt-2 text-gray-700 placeholder-gray-400 bg-white border border-gray-200 rounded-md md:h-48 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-600 focus:border-teal-400 focus:ring-teal-300 focus:ring-opacity-40 dark:focus:border-teal-300 focus:outline-none focus:ring" placeholder="Message"></textarea>
                </div>

                <button type="submit" class="w-full px-6 py-3 mt-6 text-sm font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-teal-600 rounded-md hover:bg-teal-500 focus:outline-none focus:ring focus:ring-teal-400 focus:ring-opacity-50">
                  Get in Touch
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</body>

</html>