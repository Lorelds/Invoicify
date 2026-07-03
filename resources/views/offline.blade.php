<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - Invoicify</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web" defer></script>
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            color: #1e293b;
            text-align: center;
        }
        .offline-container {
            max-width: 400px;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .icon-container {
            width: 80px;
            height: 80px;
            background-color: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="icon-container">
            <i class="ph-bold ph-wifi-slash text-danger" style="font-size: 40px;"></i>
        </div>
        <h2 class="h4 fw-bold mb-3">You are offline</h2>
        <p class="text-muted mb-4">It looks like you've lost your internet connection. Please check your network and try again.</p>
        <button onclick="window.location.reload()" class="btn btn-primary px-4 py-2" style="border-radius: 8px;">
            <i class="ph-bold ph-arrows-clockwise me-2"></i> Try Again
        </button>
    </div>
</body>
</html>
