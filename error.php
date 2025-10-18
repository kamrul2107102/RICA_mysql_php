<?php
$code = (int)($_GET['code'] ?? 500);
$message = $_GET['message'] ?? 'An error occurred';
http_response_code($code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Error <?php echo htmlspecialchars($code); ?></title>
  <link rel="stylesheet" href="/dbProjrct2107102-final/styles.css">
  <style>
    .center {min-height: 60vh; display:flex; align-items:center; justify-content:center; text-align:center;}
    .code{font-size:4rem; color:#ef4444; margin:0;}
    .msg{margin:1rem 0; color:#555;}
    .btn{padding:.6rem 1rem; background:#2563eb; color:#fff; border-radius:6px; text-decoration:none}
  </style>
</head>
<body>
  <div class="center">
    <div>
      <h1 class="code"><?php echo htmlspecialchars($code); ?></h1>
      <p class="msg"><?php echo htmlspecialchars($message); ?></p>
      <a class="btn" href="/dbProjrct2107102-final/">Go Home</a>
    </div>
  </div>
</body>
</html>
