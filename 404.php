<?php
http_response_code(404);
// Detect if we're inside a sub-directory request so asset paths work
$base = '/careygo';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — Careygo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0; padding: 0;
            font-family: 'Poppins', sans-serif;
            background: #f0f2f9;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .err-card {
            background: #fff;
            border-radius: 20px;
            padding: 56px 48px;
            text-align: center;
            max-width: 480px; width: 90%;
            box-shadow: 0 8px 40px rgba(0,26,147,.08);
        }
        .err-code {
            font-family: 'Montserrat', sans-serif;
            font-size: 96px; font-weight: 800; line-height: 1;
            background: linear-gradient(135deg, #001A93, #3B5BDB);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        .err-icon { font-size: 48px; color: #001A93; margin-bottom: 16px; }
        .err-title { font-family: 'Montserrat',sans-serif; font-weight: 700; font-size: 22px; color: #1a1a2e; margin-bottom: 10px; }
        .err-msg   { color: #6b7280; font-size: 14px; line-height: 1.6; margin-bottom: 32px; }
        .btn-home  {
            display: inline-flex; align-items: center; gap: 8px;
            background: #001A93; color: #fff; text-decoration: none;
            padding: 12px 28px; border-radius: 10px;
            font-weight: 600; font-size: 14px;
            transition: background .2s, transform .15s;
        }
        .btn-home:hover { background: #001270; color: #fff; transform: translateY(-1px); }
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px;
            border: 1.5px solid #e4e7f0; color: #6b7280; text-decoration: none;
            padding: 11px 24px; border-radius: 10px;
            font-weight: 500; font-size: 14px; margin-left: 10px;
            transition: border-color .2s, color .2s;
        }
        .btn-back:hover { border-color: #001A93; color: #001A93; }
        .logo-wrap { margin-bottom: 32px; }
        .logo-wrap img { height: 36px; }
    </style>
</head>
<body>
    <div class="err-card">
        <div class="logo-wrap">
            <img src="<?= $base ?>/assets/images/Main-Careygo-logo-blue.png" alt="Careygo">
        </div>
        <div class="err-code">404</div>
        <div class="err-icon"><i class="bi bi-map"></i></div>
        <h1 class="err-title">Page Not Found</h1>
        <p class="err-msg">
            The page you're looking for doesn't exist or has been moved.<br>
            Check the URL or head back home.
        </p>
        <div>
            <a href="<?= $base ?>/index.php" class="btn-home">
                <i class="bi bi-house-door"></i> Go Home
            </a>
            <a href="javascript:history.back()" class="btn-back">
                <i class="bi bi-arrow-left"></i> Go Back
            </a>
        </div>
    </div>
</body>
</html>
