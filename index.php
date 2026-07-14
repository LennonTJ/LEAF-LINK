<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeafLink</title>
    <!-- <link rel="stylesheet" href="assets/css/style.css"> -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        /* Nature-Inspired Theme */
        :root {
            --primary: #4a7c59;
            --secondary: #f4a261;
            --accent: #e76f51;
            --light-green: #b8d4b0;
            --cream: #fefae0;
            --text: #2d3e2d;
            --shadow: 0 8px 32px rgba(74, 124, 89, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--cream);
            color: var(--text);
            background-image:
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%234a7c59' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, #2d5a3d 100%);
            color: white;
            padding: 70px 20px 50px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: "🌱";
            position: absolute;
            font-size: 200px;
            opacity: 0.06;
            left: -30px;
            top: -40px;
            transform: rotate(-10deg);
        }

        .header::after {
            content: "🌻";
            position: absolute;
            font-size: 150px;
            opacity: 0.06;
            right: -20px;
            bottom: -30px;
            transform: rotate(10deg);
        }

        .header h1 {
            font-size: 3.8rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            position: relative;
        }

        .header p {
            font-size: 1.2rem;
            font-weight: 300;
            opacity: 0.9;
            position: relative;
        }

        .hero {
            padding: 40px 0;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 35px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(74, 124, 89, 0.1);
            transition: all 0.3s;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(74, 124, 89, 0.2);
        }

        .card h2 {
            color: var(--primary);
            font-size: 1.7rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .btn {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, var(--primary) 0%, #2d5a3d 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(74, 124, 89, 0.3);
            width: 100%;
            max-width: 280px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: "→";
            margin-right: 8px;
            transition: transform 0.3s;
            display: inline-block;
        }

        .btn:hover::before {
            transform: translateX(5px);
        }

        .btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(74, 124, 89, 0.4);
        }

        /* Responsive */
        @media (max-width: 520px) {
            .header h1 { font-size: 2.6rem; }
            .card { padding: 22px; border-radius: 18px; }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>LeafLink</h1>
    <p>Contract Farming Management System</p>
</div>

<div class="hero">
    <div style="padding:30px; max-width:900px; margin:0 auto;">
        <div class="card" style="margin-bottom:25px;">
            <h2>Connecting Every Leaf to Every Ledger.</h2>
            <p style="font-weight:300; opacity:.9;">Select a portal to continue.</p>
        </div>

        <div class="card" style="background:transparent; box-shadow:none; padding:0; border:0;">
            <h2 style="padding:35px 35px 15px 35px; margin-bottom:0;">Select Portal</h2>

            <div style="padding:0 35px 35px 35px; display:flex; flex-direction:column; gap:14px; align-items:flex-start;">
                <a class="btn" href="auth/login.php?role=grower">Grower Portal</a>
                <a class="btn" href="auth/login.php?role=contractor">Contractor Portal</a>
                <a class="btn" href="auth/login.php?role=admin">Administrator Portal</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>

