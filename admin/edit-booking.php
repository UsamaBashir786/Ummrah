<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>No Edits Required</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f9fa;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      padding: 20px;
    }

    .container {
      background-color: white;
      border-radius: 10px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      padding: 40px;
      text-align: center;
      max-width: 500px;
      width: 100%;
    }

    h1 {
      color: #333;
      margin-bottom: 20px;
      font-size: 28px;
      font-weight: 600;
    }

    p {
      color: #666;
      font-size: 18px;
      margin-bottom: 30px;
      line-height: 1.5;
    }

    .btn {
      display: inline-block;
      background-color: #0891b2;
      color: white;
      padding: 12px 25px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .btn:hover {
      background-color: #0e7490;
      transform: translateY(-2px);
    }

    .icon {
      margin-right: 8px;
    }
  </style>
</head>

<body>
  <div class="container">
    <h1>No Action Required</h1>
    <p>This page currently does not need any edits. Please return to the previous page to continue.</p>
    <a href="javascript:history.back()" class="btn">
      <span class="icon">←</span> Go Back
    </a>
  </div>

  <script>
    // This ensures the back button takes the user to the page they came from
    document.querySelector('.btn').addEventListener('click', function(e) {
      e.preventDefault();
      window.history.back();
    });
  </script>
</body>

</html>