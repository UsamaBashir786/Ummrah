<?php
// fail.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-red-100 h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded-xl shadow-lg text-center max-w-sm w-full">
        <h1 class="text-4xl font-bold text-red-600 mb-4">Payment Failed</h1>
        <p class="text-gray-700 mb-6">We're sorry, but your payment could not be processed. Please try again later.</p>
        <a href="index.php" class="bg-red-500 text-white py-2 px-4 rounded hover:bg-red-600">Go back to homepage</a>
    </div>

</body>
</html>
