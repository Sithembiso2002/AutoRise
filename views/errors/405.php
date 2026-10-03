<?php
$title = 'Method Not Allowed';
$status = 405;
$message = $message ?? 'The HTTP method used is not allowed for this URL.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>405 | <?= e(brand_name()) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <style>
    body {
      background: #111; color: #eee; min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 24px;
    }
    .error-wrap { max-width: 640px; text-align: center; }
    .error-icon {
      width: 120px; height: 120px; border-radius: 50%;
      background: rgba(230, 0, 0, .12); color: #e60000;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 56px; margin-bottom: 24px;
    }
    .error-title { font-size: clamp(1.4rem, 4vw, 2rem); font-weight: 700; margin: 12px 0; }
    .error-msg { color: #999; margin-bottom: 32px; }
    .btn-red {
      background: #e60000; color: #fff; border: none;
      padding: 12px 32px; border-radius: 30px; font-weight: 500;
      text-decoration: none; display: inline-block; transition: all .25s;
    }
    .btn-red:hover { background: #b30000; color: #fff; transform: translateY(-2px); }
  </style>
</head>
<body>
  <div class="error-wrap">
    <div class="error-icon"><i class="fa fa-ban"></i></div>
    <h1 class="error-title"><?= e($title) ?></h1>
    <p class="error-msg"><?= e($message) ?></p>
    <a href="<?= e(url('/')) ?>" class="btn-red">Back to Home</a>
  </div>
</body>
</html>