<?php
// Start a secure tracking session for the current user
session_start();

// 1. Quietly incorporate your database connection settings file
require_once('db.php'); 

$message = "";
$messageClass = ""; 

// 2. LOGOUT ROUTINE: If user explicitly clicks sign-out, terminate session variables cleanly
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// 3. LOGIN SUBMISSION HANDLING
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Query matching user email from database via safe Prepared Statement
    $stmt = $conn->prepare("SELECT id, fullname, password, phone, country FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Securely verify string input with database hashed passwords
        if (password_verify($password, $user['password'])) {
            // Assign persistent user variables to the session array
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['fullname']  = $user['fullname'];
            $_SESSION['email']     = $email;
            $_SESSION['phone']     = $user['phone'];
            $_SESSION['country']   = $user['country']; // Storing registered country selection
        } else {
            $message = "❌ Invalid email or password confirmation.";
            $messageClass = "error";
        }
    } else {
        $message = "❌ Account not found. Please register first!";
        $messageClass = "error";
    }
    $stmt->close();
}

// Determine view display switches dynamically on the backend
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSGS | Global Study Guidance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --brand: #0d47a1;
            --accent: #fafa4c;
            --glass: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --error-red: #d32f2f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            min-height: 100vh;
            width: 100vw;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)),
                url('https://images.unsplash.com/photo-1543269664-76bc3997d9ea?q=80&w=2000&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            overflow-y: auto;
        }

        /* Server-driven dynamic background application */
        body.dashboard-bg {
            background: linear-gradient(rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.3)),
                url('https://i.pinimg.com/1200x/87/0a/9f/870a9fd2c38d42373301bd563c4c055b.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        #loginView {
            height: 100vh;
            display: <?php echo $isLoggedIn ? 'none' : 'flex'; ?>;
            justify-content: center;
            align-items: center;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            padding: 45px;
            border-radius: 24px;
            width: 400px;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
        }

        .login-card h2 {
            color: var(--brand);
            margin-bottom: 25px;
            font-weight: 800;
        }

        .input-group {
            margin-bottom: 18px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .input-group input {
            width: 100%;
            padding: 13px;
            border: 1.5px solid #eee;
            border-radius: 10px;
            outline: none;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: var(--brand);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
        }

        /* Error box adjustments */
        #messageBox {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 600;
            text-align: left;
            background-color: #ffebee;
            color: var(--error-red);
            border: 1px solid #ffcdd2;
        }

        /* --- DASHBOARD SECTION --- */
        #dashboardView {
            display: <?php echo $isLoggedIn ? 'grid' : 'none'; ?>;
            height: 100vh;
            grid-template-columns: 280px 1fr;
        }

        .sidebar {
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(30px);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            color: white;
            border-right: 1px solid var(--glass-border);
            height: 100vh;
            overflow-y: auto;
        }

        .nav-item {
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 12px;
            margin-bottom: 5px;
            transition: 0.3s;
        }

        .nav-item:hover,
        .nav-item.active {
            background: var(--glass);
            color: white;
        }

        .main-content {
            padding: 40px;
            overflow-y: auto;
            color: white;
        }

        .welcome-section {
            margin-bottom: 40px;
        }

        .welcome-section h1 {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .target-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 215, 0, 0.15);
            border: 1px solid var(--accent);
            padding: 10px 20px;
            border-radius: 50px;
            margin-top: 15px;
        }

        .target-pill span {
            font-weight: bold;
            color: var(--accent);
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .option-card {
            background: var(--glass);
            border: 1px solid var(--glass-border);
            padding: 30px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            text-align: center;
            transition: all 0.4s ease;
            cursor: pointer;
        }

        .option-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.18);
            border-color: var(--accent);
        }

        .option-card i {
            font-size: 35px;
            color: var(--accent);
            margin-bottom: 15px;
            display: block;
        }

        .option-card h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .option-card p {
            font-size: 13px;
            opacity: 0.7;
            line-height: 1.4;
        }

        .logout-btn {
            margin-top: auto;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #c62828;
            border-color: transparent;
        }
    </style>
</head>

<body class="<?php echo $isLoggedIn ? 'dashboard-bg' : ''; ?>">

    <div id="loginView">
        <div class="login-card" style="position: relative; padding-top: 55px;">
            <!-- ⬅️ Clean Back to Welcome Page Arrow -->
            <a href="dashboard.php" style="position: absolute; top: 20px; left: 25px; color: var(--brand); text-decoration: none; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 6px; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1">
                <i class="fas fa-arrow-left"></i> Home
            </a>
            <h2>GSGS SIGN IN</h2>
            
            <?php if (!empty($message)): ?>
                <div id="messageBox">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php">
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="student@example.com" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login_submit" class="btn-login">SIGN IN</button>
            </form>
            <p style="margin-top:20px; font-size:14px; color: #666;">
                New student? <a href="registration.php" style="color:var(--brand); font-weight:bold; text-decoration:none;">Register Now</a>
            </p>
        </div>
    </div>

    <div id="dashboardView">
        <aside class="sidebar">
            <h2 style="margin-bottom:30px; color:var(--accent);">✈️ GSGS PORTAL</h2>
            <nav>
                <a href="index.php" class="nav-item active">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="universities.php" class="nav-item">
                    <i class="fas fa-university"></i> Universities
                </a>
                <a href="course-finder.php" class="nav-item">
                    <i class="fas fa-book"></i> Course Finder
                </a>
                <a href="sop-builder.php" class="nav-item">
                    <i class="fas fa-file-alt"></i> SOP Builder
                </a>
                <a href="scholarship.php" class="nav-item">
                    <i class="fas fa-graduation-cap"></i> Scholarships
                </a>
                <a href="financial-aid.php" class="nav-item">
                    <i class="fas fa-hand-holding-usd"></i> Financial Guidance
                </a>
                <a href="visa-guide.php" class="nav-item">
                    <i class="fas fa-passport"></i> Visa Guide
                </a>
                <a href="career-support.php" class="nav-item">
                    <i class="fas fa-briefcase"></i> Careers
                </a>
            </nav>
            <a href="index.php?action=logout" class="logout-btn">Sign Out</a>
        </aside>

        <main class="main-content">
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Welcome back, <?php echo $isLoggedIn ? htmlspecialchars($_SESSION['fullname']) : 'Scholar'; ?>!</h1>
                    <p>Explore the best universities across the globe tailored for your future.</p>
                    
                  
                </div>
            </div>
            
            <div class="options-grid">
                <div class="option-card" onclick="window.location.href='universities.php'">
                    <i class="fas fa-university"></i>
                    <h3>University Search</h3>
                    <p>Explore top-ranked institutions worldwide.</p>
                </div>
                <div class="option-card" onclick="window.location.href='course-finder.php'">
                    <i class="fas fa-book"></i>
                    <h3>Course Finder</h3>
                    <p>Discover the perfect programs across top universities worldwide.</p>
                </div>
                <div class="option-card" onclick="window.location.href='sop-builder.php'">
                    <i class="fas fa-file-signature"></i>
                    <h3>SOP Builder</h3>
                    <p>AI-powered tools to help you write winning statements.</p>
                </div>
                <div class="option-card" onclick="window.location.href='scholarship.php'">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>Scholarships</h3>
                    <p>Explore funding opportunities and financial aid tailored to your profile.</p>
                </div>
                <div class="option-card" onclick="window.location.href='financial-aid.php'">
                    <i class="fas fa-hand-holding-usd"></i>
                    <h3>Financial Guidance</h3>
                    <p>Get assistance with student loans, grants, and budget planning.</p>
                </div>
                <div class="option-card" onclick="window.location.href='visa-guide.php'">
                    <i class="fas fa-passport"></i>
                    <h3>Visa Guide</h3>
                    <p>Step-by-step assistance and checklists for your visa application.</p>
                </div>
                <div class="option-card" onclick="window.location.href='career-support.php'">
                    <i class="fas fa-briefcase"></i>
                    <h3>Career Support</h3>
                    <p>Guidance on internships, post-study work permits, and job markets.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Smooth alert fade away tracking
        window.addEventListener('DOMContentLoaded', () => {
            const box = document.getElementById('messageBox');
            if(box) {
                setTimeout(() => {
                    box.style.transition = "opacity 0.5s ease";
                    box.style.opacity = "0";
                    setTimeout(() => { box.style.display = "none"; }, 500);
                }, 3000);
            }
        });
    </script>
</body>
</html>
