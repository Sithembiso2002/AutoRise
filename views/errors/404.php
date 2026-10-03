<?php
$title = 'Page Not Found';
$status = 404;
$message = $message ?? 'The page you are looking for could not be found.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 | <?= e(brand_name()) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
  <style>
    body {
      background: #111;
      color: #eee;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', Tahoma, sans-serif;
      margin: 0;
      padding: 24px;
    }
    .error-wrap { max-width: 640px; text-align: center; }
    .error-code {
      font-size: clamp(6rem, 20vw, 12rem);
      font-weight: 900;
      line-height: 1;
      background: linear-gradient(135deg, #e60000 0%, #7a0000 100%);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      letter-spacing: -4px;
    }
    .error-title { font-size: clamp(1.4rem, 4vw, 2rem); font-weight: 700; margin: 12px 0; }
    .error-msg { color: #999; margin-bottom: 32px; }
    .btn-red {
      background: #e60000; color: #fff; border: none;
      padding: 12px 32px; border-radius: 30px; font-weight: 500;
      text-decoration: none; display: inline-block; transition: all .25s;
    }
    .btn-red:hover { background: #b30000; color: #fff; transform: translateY(-2px); }
    .btn-outline {
      color: #ccc; border: 1px solid #333; padding: 12px 32px;
      border-radius: 30px; text-decoration: none; display: inline-block;
      transition: all .25s; margin-left: 8px;
    }
    .btn-outline:hover { color: #fff; border-color: #666; }
    @media (max-width: 480px) {
      .btn-red, .btn-outline { display: block; margin: 8px 0; width: 100%; }
    }
  </style>
</head>
<body>
  <div class="error-wrap">
    <div class="error-code">404</div>
    <h1 class="error-title"><?= e($title) ?></h1>
    <p class="error-msg"><?= e($message) ?></p>
    <a href="<?= e(url('/')) ?>" class="btn-red">
      <i class="fa fa-home"></i> Back to Home
    </a>
    <a href="<?= e(url('products')) ?>" class="btn-outline">Browse Cars</a>
  </div>
</body>
</html>