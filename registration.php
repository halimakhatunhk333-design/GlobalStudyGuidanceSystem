<?php

// 1. Require your database connection settings file quietly
require_once('db.php'); 

$message = "";
$messageClass = ""; 

// 2. This ensures code ONLY runs when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = $_POST['fullname'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $phone    = $_POST['phone'];
    $country  = $_POST['country'];

    // 3. Check if email already exists using a safe Prepared Statement
    $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        $message = "❌ Email already exists!";
        $messageClass = "error";
        $checkEmail->close();
    } else {
        $checkEmail->close();

        // 4. Proceed to insert if email is unique
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, phone, country) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("sssss", $fullname, $email, $hashed_password, $phone, $country);
            if ($stmt->execute()) {
                $message = "✅ Registration Successful!";
                $messageClass = "success";
            } else {
                $message = "❌ Error: " . $stmt->error;
                $messageClass = "error";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register | Global Study Guidance System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --brand-blue: #0d47a1;
            --brand-blue-hover: #1976d2;
            --light-text: #333;
            --placeholder-color: #999;
            --error-red: #d32f2f;
            --success-green: #2e7d32;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0,0.7)), 
                url('https://images.unsplash.com/photo-1543269664-76bc3997d9ea?q=80&w=2000&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .registration-container {
            background: rgba(255, 255, 255, 0.98);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            width: 90%;
            max-width: 480px;
            text-align: center;
            max-height: 90vh;
            overflow-y: auto;
        }

        .registration-container h2 {
            font-size: 28px;
            color: var(--brand-blue);
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--brand-blue);
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
            color: var(--light-text);
            transition: border-color 0.3s;
        }

        .input-group input:focus,
        .input-group select:focus {
            border-color: var(--brand-blue);
            outline: none;
        }

        .phone-input-group {
            display: flex;
            gap: 10px;
        }

        .phone-input-group select {
            flex-shrink: 0;
            width: 110px;
        }

        .phone-input-group input {
            flex-grow: 1;
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            background-color: var(--brand-blue);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            margin-top: 15px;
        }

        .btn-register:hover {
            background-color: var(--brand-blue-hover);
            transform: translateY(-1px);
        }

        .footer-links {
            margin-top: 25px;
            font-size: 15px;
            color: #555;
        }

        .footer-links a {
            color: var(--brand-blue);
            text-decoration: none;
            font-weight: 600;
        }

        /* Dynamic message styling shifts */
        #messageBox {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 600;
            text-align: left;
            display: none;
        }
        
        #messageBox.success {
            display: block;
            background-color: #e8f5e9;
            color: var(--success-green);
            border: 1px solid #c8e6c9;
        }

        #messageBox.error {
            display: block;
            background-color: #ffebee;
            color: var(--error-red);
            border: 1px solid #ffcdd2;
        }
    </style>
</head>

<body>

    <div class="registration-container">
        <a href="dashboard.php" style="position: absolute; top: 20px; left: 25px; color: var(--brand, #0d47a1); text-shadow: none; text-decoration: none; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 6px; transition: opacity 0.2s; z-index: 10;" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1">
    <i class="fas fa-arrow-left"></i> Home
</a>
        <h2><i class="fas fa-user-plus"></i> Create GSGS Account</h2>

        <div id="messageBox" class="<?php echo $messageClass; ?>">
            <?php echo $message; ?>
        </div>

        <form method="POST" onsubmit="return validatePasswords();">
            <div class="input-group">
                <label for="fullName">Full Name</label>
                <input type="text" name="fullname" id="fullName" placeholder="Enter your name" required />
            </div>
            <div class="input-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required />
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Create a password" required />
            </div>
            <div class="input-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" placeholder="Re-enter your password" required />
            </div>

            <div class="input-group">
                <label for="phoneNumber">Phone Number</label>
                <div class="phone-input-group">
                    <select id="countryCode" name="countryCode" required>
                        <option value="">Code</option>
                    </select>
                    <input type="tel" name="phone" id="phoneNumber" placeholder="Enter phone number" required />
                </div>
            </div>

            <div class="input-group">
                <label for="preferredCountry">Preferred Study Country</label>
                <input list="countryList" name="country" id="preferredCountry" placeholder="Type to search..." required />
                <datalist id="countryList"></datalist>
            </div>

            <button type="submit" class="btn-register">Register Account</button>
        </form>
        <div class="footer-links">
            Already have an account? <a href="index.php">Login Here</a>
        </div>
    </div>

    <script>
        const countryData = [
            { name: "Afghanistan", code: "+93" }, { name: "Albania", code: "+355" }, { name: "Algeria", code: "+213" },
            { name: "Andorra", code: "+376" }, { name: "Angola", code: "+244" }, { name: "Antigua and Barbuda", code: "+1-268" },
            { name: "Argentina", code: "+54" }, { name: "Armenia", code: "+374" }, { name: "Australia", code: "+61" },
            { name: "Austria", code: "+43" }, { name: "Azerbaijan", code: "+994" }, { name: "Bahamas", code: "+1-242" },
            { name: "Bahrain", code: "+973" }, { name: "Bangladesh", code: "+880" }, { name: "Barbados", code: "+1-246" },
            { name: "Belarus", code: "+375" }, { name: "Belgium", code: "+32" }, { name: "Belize", code: "+501" },
            { name: "Benin", code: "+229" }, { name: "Bhutan", code: "+975" }, { name: "Bolivia", code: "+591" },
            { name: "Bosnia and Herzegovina", code: "+387" }, { name: "Botswana", code: "+267" }, { name: "Brazil", code: "+55" },
            { name: "Brunei", code: "+673" }, { name: "Bulgaria", code: "+359" }, { name: "Burkina Faso", code: "+226" },
            { name: "Burundi", code: "+257" }, { name: "Cambodia", code: "+855" }, { name: "Cameroon", code: "+237" },
            { name: "Canada", code: "+1" }, { name: "Cape Verde", code: "+238" }, { name: "Central African Republic", code: "+236" },
            { name: "Chad", code: "+235" }, { name: "Chile", code: "+56" }, { name: "China", code: "+86" },
            { name: "Colombia", code: "+57" }, { name: "Comoros", code: "+269" }, { name: "Congo", code: "+242" },
            { name: "Costa Rica", code: "+506" }, { name: "Croatia", code: "+385" }, { name: "Cuba", code: "+53" },
            { name: "Cyprus", code: "+357" }, { name: "Czech Republic", code: "+420" }, { name: "Denmark", code: "+45" },
            { name: "Djibouti", code: "+253" }, { name: "Dominica", code: "+1-767" }, { name: "Dominican Republic", code: "+1-809" },
            { name: "Ecuador", code: "+593" }, { name: "Egypt", code: "+20" }, { name: "El Salvador", code: "+503" },
            { name: "Equatorial Guinea", code: "+240" }, { name: "Eritrea", code: "+291" }, { name: "Estonia", code: "+372" },
            { name: "Eswatini", code: "+268" }, { name: "Ethiopia", code: "+251" }, { name: "Fiji", code: "+679" },
            { name: "Finland", code: "+358" }, { name: "France", code: "+33" }, { name: "Gabon", code: "+241" },
            { name: "Gambia", code: "+220" }, { name: "Georgia", code: "+995" }, { name: "Germany", code: "+49" },
            { name: "Ghana", code: "+233" }, { name: "Greece", code: "+30" }, { name: "Grenada", code: "+1-473" },
            { name: "Guatemala", code: "+502" }, { name: "Guinea", code: "+224" }, { name: "Guyana", code: "+592" },
            { name: "Haiti", code: "+509" }, { name: "Honduras", code: "+504" }, { name: "Hungary", code: "+36" },
            { name: "Iceland", code: "+354" }, { name: "India", code: "+91" }, { name: "Indonesia", code: "+62" },
            { name: "Iran", code: "+98" }, { name: "Iraq", code: "+964" }, { name: "Ireland", code: "+353" },
            { name: "Israel", code: "+972" }, { name: "Italy", code: "+39" }, { name: "Jamaica", code: "+1-876" },
            { name: "Japan", code: "+81" }, { name: "Jordan", code: "+962" }, { name: "Kazakhstan", code: "+7" },
            { name: "Kenya", code: "+254" }, { name: "Kuwait", code: "+965" }, { name: "Kyrgyzstan", code: "+996" },
            { name: "Laos", code: "+856" }, { name: "Latvia", code: "+371" }, { name: "Lebanon", code: "+961" },
            { name: "Lesotho", code: "+266" }, { name: "Liberia", code: "+231" }, { name: "Libya", code: "+218" },
            { name: "Liechtenstein", code: "+423" }, { name: "Lithuania", code: "+370" }, { name: "Luxembourg", code: "+352" },
            { name: "Madagascar", code: "+261" }, { name: "Malawi", code: "+265" }, { name: "Malaysia", code: "+60" },
            { name: "Maldives", code: "+960" }, { name: "Mali", code: "+223" }, { name: "Malta", code: "+356" },
            { name: "Mexico", code: "+52" }, { name: "Moldova", code: "+373" }, { name: "Monaco", code: "+377" },
            { name: "Mongolia", code: "+976" }, { name: "Montenegro", code: "+382" }, { name: "Morocco", code: "+212" },
            { name: "Mozambique", code: "+258" }, { name: "Myanmar", code: "+95" }, { name: "Namibia", code: "+264" },
            { name: "Nepal", code: "+977" }, { name: "Netherlands", code: "+31" }, { name: "New Zealand", code: "+64" },
            { name: "Nicaragua", code: "+505" }, { name: "Niger", code: "+227" }, { name: "Nigeria", code: "+234" },
            { name: "North Korea", code: "+850" }, { name: "Norway", code: "+47" }, { name: "Oman", code: "+968" },
            { name: "Pakistan", code: "+92" }, { name: "Palestine", code: "+970" }, { name: "Panama", code: "+507" },
            { name: "Papua New Guinea", code: "+675" }, { name: "Paraguay", code: "+595" }, { name: "Peru", code: "+51" },
            { name: "Philippines", code: "+63" }, { name: "Poland", code: "+48" }, { name: "Portugal", code: "+351" },
            { name: "Qatar", code: "+974" }, { name: "Romania", code: "+40" }, { name: "Russia", code: "+7" },
            { name: "Rwanda", code: "+250" }, { name: "Saudi Arabia", code: "+966" }, { name: "Senegal", code: "+221" },
            { name: "Serbia", code: "+381" }, { name: "Singapore", code: "+65" }, { name: "Slovakia", code: "+421" },
            { name: "Slovenia", code: "+386" }, { name: "South Africa", code: "+27" }, { name: "South Korea", code: "+82" },
            { name: "Spain", code: "+34" }, { name: "Sri Lanka", code: "+94" }, { name: "Sudan", code: "+249" },
            { name: "Sweden", code: "+46" }, { name: "Switzerland", code: "+41" }, { name: "Syria", code: "+963" },
            { name: "Taiwan", code: "+886" }, { name: "Tanzania", code: "+255" }, { name: "Thailand", code: "+66" },
            { name: "Tunisia", code: "+216" }, { name: "Turkey", code: "+90" }, { name: "Uganda", code: "+256" },
            { name: "Ukraine", code: "+380" }, { name: "United Arab Emirates", code: "+971" }, { name: "United Kingdom", code: "+44" },
            { name: "United States", code: "+1" }, { name: "Uruguay", code: "+598" }, { name: "Uzbekistan", code: "+998" },
            { name: "Vietnam", code: "+84" }, { name: "Yemen", code: "+967" }, { name: "Zambia", code: "+260" }, { name: "Zimbabwe", code: "+263" }
        ];

        window.onload = function () {
            const codeSelect = document.getElementById('countryCode');
            const nameDataList = document.getElementById('countryList');

            countryData.forEach(item => {
                let codeOption = document.createElement('option');
                codeOption.value = item.code;
                codeOption.textContent = item.name + " (" + item.code + ")";
                codeSelect.appendChild(codeOption);

                let nameOption = document.createElement('option');
                nameOption.value = item.name;
                nameDataList.appendChild(nameOption);
            });
        };

        // Front-end password check helper
        function validatePasswords() {
            const pass = document.getElementById('password').value;
            const confirmPass = document.getElementById('confirmPassword').value;
            if (pass !== confirmPass) {
                alert("❌ Passwords do not match!");
                return false;
            }
            return true;
        }
        // Auto-hide the message box after 3 seconds

    </script>
    
</body>
</html>
