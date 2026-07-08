<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Welcome | Global Study Guidance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --brand-blue: #42a5f5;
            --brand-blue-hover: #90caf9;
            --text-main: #ffffff;
            --text-sub: #f0f0f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            /* We use a slightly stronger 0.6 overlay to ensure text is visible directly on the photo */
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0,0.7)), 
                url('https://i.pinimg.com/736x/56/65/ee/5665ee7dff1098093893fe782d5aabac.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;

            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: var(--text-main);
            text-align: center;
            overflow: hidden;
        }

        .content {
            width: 90%;
            max-width: 900px;
            /* Removed the background box and blur entirely */
            background: none;
            padding: 0;
            border: none;
            box-shadow: none;
        }

        .logo-header {
            font-size: clamp(16px, 2.5vw, 22px);
            font-weight: 600;
            color: var(--brand-blue);
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 4px;
            /* Text shadow helps readability against the image details */
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        h1 {
            font-size: clamp(50px, 12vw, 100px);
            margin-bottom: 20px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            text-shadow: 4px 4px 10px rgba(0, 0, 0, 0.7);
        }

        p {
            font-size: clamp(18px, 3vw, 26px);
            margin-bottom: 50px;
            color: var(--text-sub);
            font-weight: 300;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .action-buttons a {
            text-decoration: none;
            color: #ffffff;
            font-size: 18px;
            padding: 16px 45px;
            border-radius: 50px;
            /* Using pill-shape for a modern look on transparent BG */
            background-color: var(--brand-blue);
            font-weight: 700;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-buttons a:hover {
            background-color: var(--brand-blue-hover);
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }

        .btn-register {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border: 2px solid white;
            backdrop-filter: blur(5px);
            /* Just a tiny blur inside the button only */
        }

        .btn-register:hover {
            background-color: white !important;
            color: #000 !important;
        }
    </style>
</head>

<body>

    <div class="content">
        <div class="logo-header">
            <i class="fas fa-graduation-cap"></i>
            Global Study Guidance System
        </div>

        <h1>Welcome</h1>
        <p>Your journey to international education begins here with expert guidance.</p>

    <div class="action-buttons">
    <a href="index.php" class="btn-login">
        <i class="fas fa-sign-in-alt"></i> Login
    </a>
    <a href="registration.php" class="btn-register">
        <i class="fas fa-user-plus"></i> Register
    </a>
</div>
    </div>

</body>

</html>
