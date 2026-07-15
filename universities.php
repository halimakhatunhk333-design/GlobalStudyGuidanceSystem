<?php
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$jsonData = json_decode(file_get_contents("php://input"), true);

if ($jsonData && isset($jsonData['action'])) {
    header('Content-Type: application/json');
    

    if ($jsonData['action'] === 'track-search') {
        $email = isset($jsonData['email']) ? trim($jsonData['email']) : '';
        $searchedCountry = isset($jsonData['searchedCountry']) ? trim($jsonData['searchedCountry']) : '';
        
        if (empty($email) || empty($searchedCountry)) {
            echo json_encode([
                "success" => false, 
                "message" => "Email অথবা Country খালি এসেছে!",
                "received_email" => $email,
                "received_country" => $searchedCountry
            ]);
            exit;
        }

      
        $stmt = $conn->prepare("INSERT INTO search_history (user_email, searched_country) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $searchedCountry);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "সফলভাবে ডাটাবেজে সেভ হয়েছে!"]);
        } else {
            echo json_encode([
                "success" => false, 
                "message" => "MySQL কোয়েরি ব্যর্থ হয়েছে!",
                "mysql_error" => $stmt->error
            ]);
        }
        $stmt->close(); 
        $conn->close(); 
        exit;
    }
    
   
    if ($jsonData['action'] === 'login') {
        $email = $jsonData['email'];
        $password = $jsonData['password'];
        
        $stmt = $conn->prepare("SELECT fullname, email, password, country FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                unset($user['password']); 
                
                
                $_SESSION['email'] = $user['email']; 
                $_SESSION['user_id'] = $user['id'] ?? null; 
                $_SESSION['fullname'] = $user['fullname'];
                
                echo json_encode(["success" => true, "user" => $user]);
            } else {
                echo json_encode(["success" => false, "message" => "Invalid password!"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Invalid email!"]);
        }
        $stmt->close(); $conn->close(); exit;
    }
    
   
    if ($jsonData['action'] === 'update-target') {
        $email = $jsonData['email'];
        $targetCountry = $jsonData['targetCountry'];
        
        $stmt = $conn->prepare("UPDATE users SET country = ? WHERE email = ?");
        $stmt->bind_param("ss", $targetCountry, $email);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Updated successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Database update failed."]);
        }
        $stmt->close(); $conn->close(); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global University Rankings | GSGS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --brand: #0d47a1;
            --accent: #FFD700;
            --glass: rgba(255, 255, 255, 0.1);
            --card-bg: rgba(255, 255, 255, 0.07);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.95)),
                url('https://images.unsplash.com/photo-1523050853051-be991f85a6ad?q=80&w=2000&auto=format&fit=crop');
            background-size: cover;
            background-attachment: fixed;
            color: white;
            display: flex;
        }

        /* Access Security */
        script {
            display: none;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(25px);
            padding: 30px 20px;
            height: 100vh;
            position: fixed;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1000;
        }

        .nav-item {
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: 0.3s;
        }

        .nav-item:hover,
        .active {
            background: var(--glass);
            color: white;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 40px;
            width: calc(100% - 260px);
        }

        .header-section {
            margin-bottom: 40px;
            text-align: center;
        }

        .search-wrapper {
            max-width: 700px;
            margin: 20px auto;
            position: relative;
        }

        .search-wrapper input {
            width: 100%;
            padding: 18px 25px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            outline: none;
            backdrop-filter: blur(10px);
        }

        /* Uni Grid */
        .uni-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .uni-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: 25px;
            transition: 0.4s;
            position: relative;
            overflow: hidden;
        }

        .uni-card:hover {
            transform: translateY(-8px);
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.12);
        }

        .uni-card h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: var(--accent);
        }

        .region-tag {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #bbb;
            display: block;
            margin-bottom: 5px;
        }

        .country-pill {
            display: inline-block;
            padding: 4px 12px;
            background: var(--brand);
            border-radius: 20px;
            font-size: 12px;
            margin-top: 10px;
        }

        .rank {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 14px;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.3);
        }

        /* This makes the popup stay hidden until we click */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(8px);
            z-index: 3000;
            justify-content: center;
            align-items: center;
        }

        /* This makes the popup box look like a card */
        .modal-content {
            background: #1a1a1a;
            padding: 30px;
            border-radius: 20px;
            border: 1px solid var(--accent);
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        /* This adds the "hand" cursor so people know they can click the cards */
        .uni-card {
            cursor: pointer;
        }

        /* Makes the cards feel like buttons */
        .uni-card {
            cursor: pointer;
            transition: 0.3s;
        }

        .uni-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
        }

        /* The Popup Background */
        .modal-overlay {
            display: none;
            /* Stays hidden until click */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            z-index: 9999;
            /* Ensures it is on top of everything */
            justify-content: center;
            align-items: center;
        }

        /* The Popup Box */
        .modal-content {
            background: #121212;
            padding: 30px;
            border-radius: 20px;
            border: 1px solid var(--accent);
            width: 90%;
            max-width: 500px;
            position: relative;
        }
    </style>
</head>

<body>


    <aside class="sidebar">
        <h2 style="color:var(--accent); margin-bottom:40px; text-align: center;">GSGS GLOBAL</h2>
        <nav>
            <a href="index.html" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="universities.html" class="nav-item active"><i class="fas fa-university"></i> Universities</a>
            <a href="course-finder.html" class="nav-item"><i class="fas fa-book"></i>course-finder</a>
            <a href="sop-builder.html" class="nav-item"><i class="fas fa-file-alt"></i> SOP Builder</a>
            <a href="scholarship.html" class="nav-item"><i class="fas fa-globe"></i> scholarships</a>
            <a href="financial-guidance.html" class="nav-item"><i class="fas fa-hand-holding-usd"></i> financial guidance</a>
            <a href="visa-guide.html" class="nav-item"><i class="fas fa-passport"></i> Visa Guide</a>
            <a href="career-support.html" class="nav-item"><i class="fas fa-user-graduate"></i> Career Support</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header-section">
            <h1>World Top Universities</h1>
            <p style="opacity: 0.7;">Browse elite institutions across North America, Europe, and Asia-Pacific</p>
            <div class="search-wrapper">
                <input type="text" id="uniSearch" placeholder="Search by University, Country or Region..."
                    onkeyup="filterUnis()">
            </div>
        </div>

        <div class="uni-grid" id="universityList">
            <div class="uni-card" data-info="North America USA MIT Massachusetts Institute of Technology">
                <span class="region-tag">North America</span>
                <span class="rank">#1</span>
                <h3>MIT</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Stanford University">
                <span class="region-tag">North America</span>
                <span class="rank">#3</span>
                <h3>Stanford University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Harvard University">
                <span class="region-tag">North America</span>
                <span class="rank">#5</span>
                <h3>Harvard University</h3>
                <span class="country-pill">United States</span>
            </div>


            <div class="uni-card" data-info="North America USA California Institute of Technology Caltech">
                <span class="region-tag">North America</span>
                <span class="rank">#6</span>
                <h3>California Institute of Technology</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Princeton University">
                <span class="region-tag">North America</span>
                <span class="rank">#12</span>
                <h3>Princeton University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Yale University">
                <span class="region-tag">North America</span>
                <span class="rank">#16</span>
                <h3>Yale University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Columbia University">
                <span class="region-tag">North America</span>
                <span class="rank">#22</span>
                <h3>Columbia University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA University of Chicago">
                <span class="region-tag">North America</span>
                <span class="rank">#13</span>
                <h3>University of Chicago</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="Europe France Sorbonne University">
                <span class="region-tag">Europe</span>
                <span class="rank">#44</span>
                <h3>Sorbonne University</h3>
                <span class="country-pill">France</span>
            </div>

            <div class="uni-card" data-info="Europe France Paris Sciences et Lettres PSL">
                <span class="region-tag">Europe</span>
                <span class="rank">#24</span>
                <h3>PSL University</h3>
                <span class="country-pill">France</span>
            </div>

            <div class="uni-card" data-info="Europe Spain University of Barcelona">
                <span class="region-tag">Europe</span>
                <span class="rank">#164</span>
                <h3>University of Barcelona</h3>
                <span class="country-pill">Spain</span>
            </div>

            <div class="uni-card" data-info="Europe Spain Autonomous University of Madrid">
                <span class="region-tag">Europe</span>
                <span class="rank">#215</span>
                <h3>Autonomous University of Madrid</h3>
                <span class="country-pill">Spain</span>
            </div>

            <div class="uni-card" data-info="Europe Sweden Lund University">
                <span class="region-tag">Europe</span>
                <span class="rank">#85</span>
                <h3>Lund University</h3>
                <span class="country-pill">Sweden</span>
            </div>

            <div class="uni-card" data-info="Europe Sweden Uppsala University">
                <span class="region-tag">Europe</span>
                <span class="rank">#105</span>
                <h3>Uppsala University</h3>
                <span class="country-pill">Sweden</span>
            </div>

            <div class="uni-card" data-info="Europe Denmark University of Copenhagen">
                <span class="region-tag">Europe</span>
                <span class="rank">#103</span>
                <h3>University of Copenhagen</h3>
                <span class="country-pill">Denmark</span>
            </div>

            <div class="uni-card" data-info="Europe Finland University of Helsinki">
                <span class="region-tag">Europe</span>
                <span class="rank">#117</span>
                <h3>University of Helsinki</h3>
                <span class="country-pill">Finland</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific India Indian Institute of Science IISc">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#155</span>
                <h3>Indian Institute of Science</h3>
                <span class="country-pill">India</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific India IIT Bombay">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#149</span>
                <h3>IIT Bombay</h3>
                <span class="country-pill">India</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific India IIT Delhi">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#197</span>
                <h3>IIT Delhi</h3>
                <span class="country-pill">India</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific China Tsinghua University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#25</span>
                <h3>Tsinghua University</h3>
                <span class="country-pill">China</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific China Fudan University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#50</span>
                <h3>Fudan University</h3>
                <span class="country-pill">China</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Taiwan National Taiwan University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#69</span>
                <h3>National Taiwan University</h3>
                <span class="country-pill">Taiwan</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Thailand Chulalongkorn University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#211</span>
                <h3>Chulalongkorn University</h3>
                <span class="country-pill">Thailand</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Indonesia University of Indonesia">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#237</span>
                <h3>University of Indonesia</h3>
                <span class="country-pill">Indonesia</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Bangladesh University of Dhaka">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#801+</span>
                <h3>University of Dhaka</h3>
                <span class="country-pill">Bangladesh</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Pakistan Quaid i Azam University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#315</span>
                <h3>Quaid-i-Azam University</h3>
                <span class="country-pill">Pakistan</span>
            </div>

            <div class="uni-card" data-info="Africa South Africa University of Cape Town">
                <span class="region-tag">Africa</span>
                <span class="rank">#167</span>
                <h3>University of Cape Town</h3>
                <span class="country-pill">South Africa</span>
            </div>

            <div class="uni-card" data-info="Africa Egypt Cairo University">
                <span class="region-tag">Africa</span>
                <span class="rank">#371</span>
                <h3>Cairo University</h3>
                <span class="country-pill">Egypt</span>
            </div>

            <div class="uni-card" data-info="Oceania New Zealand University of Auckland">
                <span class="region-tag">Oceania</span>
                <span class="rank">#68</span>
                <h3>University of Auckland</h3>
                <span class="country-pill">New Zealand</span>
            </div>

            <div class="uni-card" data-info="Oceania New Zealand University of Otago">
                <span class="region-tag">Oceania</span>
                <span class="rank">#206</span>
                <h3>University of Otago</h3>
                <span class="country-pill">New Zealand</span>
            </div>

            <div class="uni-card" data-info="North America USA Brown University">
                <span class="region-tag">North America</span>
                <span class="rank">#63</span>
                <h3>Brown University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Dartmouth College">
                <span class="region-tag">North America</span>
                <span class="rank">#191</span>
                <h3>Dartmouth College</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Duke University">
                <span class="region-tag">North America</span>
                <span class="rank">#57</span>
                <h3>Duke University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Northwestern University">
                <span class="region-tag">North America</span>
                <span class="rank">#47</span>
                <h3>Northwestern University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Johns Hopkins University">
                <span class="region-tag">North America</span>
                <span class="rank">#28</span>
                <h3>Johns Hopkins University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="Europe Ireland Trinity College Dublin">
                <span class="region-tag">Europe</span>
                <span class="rank">#81</span>
                <h3>Trinity College Dublin</h3>
                <span class="country-pill">Ireland</span>
            </div>

            <div class="uni-card" data-info="Europe Ireland University College Dublin">
                <span class="region-tag">Europe</span>
                <span class="rank">#171</span>
                <h3>University College Dublin</h3>
                <span class="country-pill">Ireland</span>
            </div>

            <div class="uni-card" data-info="Europe Belgium KU Leuven">
                <span class="region-tag">Europe</span>
                <span class="rank">#76</span>
                <h3>KU Leuven</h3>
                <span class="country-pill">Belgium</span>
            </div>

            <div class="uni-card" data-info="Europe Belgium Ghent University">
                <span class="region-tag">Europe</span>
                <span class="rank">#141</span>
                <h3>Ghent University</h3>
                <span class="country-pill">Belgium</span>
            </div>

            <div class="uni-card" data-info="Europe Austria University of Vienna">
                <span class="region-tag">Europe</span>
                <span class="rank">#137</span>
                <h3>University of Vienna</h3>
                <span class="country-pill">Austria</span>
            </div>

            <div class="uni-card" data-info="Europe Portugal University of Lisbon">
                <span class="region-tag">Europe</span>
                <span class="rank">#260</span>
                <h3>University of Lisbon</h3>
                <span class="country-pill">Portugal</span>
            </div>

            <div class="uni-card" data-info="Europe Czech Republic Charles University">
                <span class="region-tag">Europe</span>
                <span class="rank">#248</span>
                <h3>Charles University</h3>
                <span class="country-pill">Czech Republic</span>
            </div>

            <div class="uni-card" data-info="Europe Poland University of Warsaw">
                <span class="region-tag">Europe</span>
                <span class="rank">#258</span>
                <h3>University of Warsaw</h3>
                <span class="country-pill">Poland</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Vietnam Vietnam National University Hanoi">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#801+</span>
                <h3>Vietnam National University, Hanoi</h3>
                <span class="country-pill">Vietnam</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Sri Lanka University of Colombo">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#901+</span>
                <h3>University of Colombo</h3>
                <span class="country-pill">Sri Lanka</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Nepal Tribhuvan University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#1001+</span>
                <h3>Tribhuvan University</h3>
                <span class="country-pill">Nepal</span>
            </div>

            <div class="uni-card" data-info="Middle East Israel Hebrew University of Jerusalem">
                <span class="region-tag">Middle East</span>
                <span class="rank">#81</span>
                <h3>Hebrew University of Jerusalem</h3>
                <span class="country-pill">Israel</span>
            </div>

            <div class="uni-card" data-info="Middle East Qatar Qatar University">
                <span class="region-tag">Middle East</span>
                <span class="rank">#208</span>
                <h3>Qatar University</h3>
                <span class="country-pill">Qatar</span>
            </div>
            <div class="uni-card" data-info="Europe United Kingdom Royal Holloway University of London">
                <span class="region-tag">Europe</span>
                <span class="rank">#402</span>
                <h3>Royal Holloway, University of London</h3>
                <span class="country-pill">United Kingdom</span>
            </div>

            <div class="uni-card" data-info="Middle East Oman Sultan Qaboos University">
                <span class="region-tag">Middle East</span>
                <span class="rank">#362</span>
                <h3>Sultan Qaboos University</h3>
                <span class="country-pill">Oman</span>
            </div>

            <div class="uni-card" data-info="Africa Nigeria University of Ibadan">
                <span class="region-tag">Africa</span>
                <span class="rank">#401</span>
                <h3>University of Ibadan</h3>
                <span class="country-pill">Nigeria</span>
            </div>

            <div class="uni-card" data-info="Africa Kenya University of Nairobi">
                <span class="region-tag">Africa</span>
                <span class="rank">#601+</span>
                <h3>University of Nairobi</h3>
                <span class="country-pill">Kenya</span>
            </div>

            <div class="uni-card" data-info="Africa Morocco Mohammed V University">
                <span class="region-tag">Africa</span>
                <span class="rank">#901+</span>
                <h3>Mohammed V University</h3>
                <span class="country-pill">Morocco</span>
            </div>

            <div class="uni-card" data-info="South America Brazil University of Sao Paulo">
                <span class="region-tag">South America</span>
                <span class="rank">#85</span>
                <h3>University of São Paulo</h3>
                <span class="country-pill">Brazil</span>
            </div>

            <div class="uni-card" data-info="South America Chile Pontifical Catholic University of Chile">
                <span class="region-tag">South America</span>
                <span class="rank">#116</span>
                <h3>Pontifical Catholic University of Chile</h3>
                <span class="country-pill">Chile</span>
            </div>

            <div class="uni-card" data-info="South America Mexico UNAM National Autonomous University of Mexico">
                <span class="region-tag">South America</span>
                <span class="rank">#93</span>
                <h3>UNAM</h3>
                <span class="country-pill">Mexico</span>
            </div>

            <div class="uni-card" data-info="North America USA Rice University">
                <span class="region-tag">North America</span>
                <span class="rank">#145</span>
                <h3>Rice University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Vanderbilt University">
                <span class="region-tag">North America</span>
                <span class="rank">#261</span>
                <h3>Vanderbilt University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA University of Michigan Ann Arbor">
                <span class="region-tag">North America</span>
                <span class="rank">#33</span>
                <h3>University of Michigan</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA University of California Los Angeles UCLA">
                <span class="region-tag">North America</span>
                <span class="rank">#29</span>
                <h3>UCLA</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA University of California Berkeley">
                <span class="region-tag">North America</span>
                <span class="rank">#27</span>
                <h3>UC Berkeley</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="Europe Hungary Eotvos Lorand University">
                <span class="region-tag">Europe</span>
                <span class="rank">#701+</span>
                <h3>Eötvös Loránd University</h3>
                <span class="country-pill">Hungary</span>
            </div>

            <div class="uni-card" data-info="Europe Greece National and Kapodistrian University of Athens">
                <span class="region-tag">Europe</span>
                <span class="rank">#561</span>
                <h3>University of Athens</h3>
                <span class="country-pill">Greece</span>
            </div>

            <div class="uni-card" data-info="Europe Romania University of Bucharest">
                <span class="region-tag">Europe</span>
                <span class="rank">#801+</span>
                <h3>University of Bucharest</h3>
                <span class="country-pill">Romania</span>
            </div>

            <div class="uni-card" data-info="Europe Slovakia Comenius University Bratislava">
                <span class="region-tag">Europe</span>
                <span class="rank">#901+</span>
                <h3>Comenius University</h3>
                <span class="country-pill">Slovakia</span>
            </div>

            <div class="uni-card" data-info="Europe Croatia University of Zagreb">
                <span class="region-tag">Europe</span>
                <span class="rank">#751+</span>
                <h3>University of Zagreb</h3>
                <span class="country-pill">Croatia</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Philippines University of the Philippines">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#404</span>
                <h3>University of the Philippines</h3>
                <span class="country-pill">Philippines</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Mongolia National University of Mongolia">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#1201+</span>
                <h3>National University of Mongolia</h3>
                <span class="country-pill">Mongolia</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Cambodia Royal University of Phnom Penh">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#1201+</span>
                <h3>Royal University of Phnom Penh</h3>
                <span class="country-pill">Cambodia</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Laos National University of Laos">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#1201+</span>
                <h3>National University of Laos</h3>
                <span class="country-pill">Laos</span>
            </div>

            <div class="uni-card" data-info="Middle East Iran University of Tehran">
                <span class="region-tag">Middle East</span>
                <span class="rank">#401</span>
                <h3>University of Tehran</h3>
                <span class="country-pill">Iran</span>
            </div>

            <div class="uni-card" data-info="Middle East Iran Sharif University of Technology">
                <span class="region-tag">Middle East</span>
                <span class="rank">#342</span>
                <h3>Sharif University of Technology</h3>
                <span class="country-pill">Iran</span>
            </div>

            <div class="uni-card" data-info="Middle East Iraq University of Baghdad">
                <span class="region-tag">Middle East</span>
                <span class="rank">#1201+</span>
                <h3>University of Baghdad</h3>
                <span class="country-pill">Iraq</span>
            </div>

            <div class="uni-card" data-info="Africa Ethiopia Addis Ababa University">
                <span class="region-tag">Africa</span>
                <span class="rank">#601+</span>
                <h3>Addis Ababa University</h3>
                <span class="country-pill">Ethiopia</span>
            </div>

            <div class="uni-card" data-info="Africa Ghana University of Ghana">
                <span class="region-tag">Africa</span>
                <span class="rank">#601+</span>
                <h3>University of Ghana</h3>
                <span class="country-pill">Ghana</span>
            </div>

            <div class="uni-card" data-info="Africa Tunisia University of Tunis">
                <span class="region-tag">Africa</span>
                <span class="rank">#1001+</span>
                <h3>University of Tunis</h3>
                <span class="country-pill">Tunisia</span>
            </div>

            <div class="uni-card" data-info="South America Peru Pontifical Catholic University of Peru">
                <span class="region-tag">South America</span>
                <span class="rank">#501</span>
                <h3>Pontifical Catholic University of Peru</h3>
                <span class="country-pill">Peru</span>
            </div>

            <div class="uni-card" data-info="South America Colombia University of the Andes">
                <span class="region-tag">South America</span>
                <span class="rank">#236</span>
                <h3>University of the Andes</h3>
                <span class="country-pill">Colombia</span>
            </div>

            <div class="uni-card" data-info="South America Venezuela Central University of Venezuela">
                <span class="region-tag">South America</span>
                <span class="rank">#751+</span>
                <h3>Central University of Venezuela</h3>
                <span class="country-pill">Venezuela</span>
            </div>

            <div class="uni-card" data-info="Oceania Fiji University of the South Pacific">
                <span class="region-tag">Oceania</span>
                <span class="rank">#1001+</span>
                <h3>University of the South Pacific</h3>
                <span class="country-pill">Fiji</span>
            </div>

            <div class="uni-card" data-info="North America Canada University of Toronto">
                <span class="region-tag">North America</span>
                <span class="rank">#28</span>
                <h3>University of Toronto</h3>
                <span class="country-pill">Canada</span>
            </div>

            <div class="uni-card" data-info="Europe UK Oxford University">
                <span class="region-tag">Europe</span>
                <span class="rank">#1 (THE)</span>
                <h3>University of Oxford</h3>
                <span class="country-pill">United Kingdom</span>
            </div>

            <div class="uni-card" data-info="Europe UK Imperial College London">
                <span class="region-tag">Europe</span>
                <span class="rank">#2 (QS)</span>
                <h3>Imperial College London</h3>
                <span class="country-pill">United Kingdom</span>
            </div>


            <div class="uni-card" data-info="North America USA Boston University">
                <span class="region-tag">North America</span>
                <span class="rank">#108</span>
                <h3>Boston University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA University of Southern California USC">
                <span class="region-tag">North America</span>
                <span class="rank">#116</span>
                <h3>University of Southern California</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Pennsylvania State University">
                <span class="region-tag">North America</span>
                <span class="rank">#93</span>
                <h3>Pennsylvania State University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA University of Texas at Austin">
                <span class="region-tag">North America</span>
                <span class="rank">#58</span>
                <h3>University of Texas at Austin</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA University of Washington Seattle">
                <span class="region-tag">North America</span>
                <span class="rank">#63</span>
                <h3>University of Washington</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="Europe France University of Paris Cite">
                <span class="region-tag">Europe</span>
                <span class="rank">#114</span>
                <h3>Université Paris Cité</h3>
                <span class="country-pill">France</span>
            </div>

            <div class="uni-card" data-info="Europe France University of Bordeaux">
                <span class="region-tag">Europe</span>
                <span class="rank">#351</span>
                <h3>University of Bordeaux</h3>
                <span class="country-pill">France</span>
            </div>

            <div class="uni-card" data-info="Europe Spain Pompeu Fabra University">
                <span class="region-tag">Europe</span>
                <span class="rank">#248</span>
                <h3>Pompeu Fabra University</h3>
                <span class="country-pill">Spain</span>
            </div>

            <div class="uni-card" data-info="Europe Spain Complutense University of Madrid">
                <span class="region-tag">Europe</span>
                <span class="rank">#226</span>
                <h3>Complutense University of Madrid</h3>
                <span class="country-pill">Spain</span>
            </div>

            <div class="uni-card" data-info="Europe Germany RWTH Aachen University">
                <span class="region-tag">Europe</span>
                <span class="rank">#99</span>
                <h3>RWTH Aachen University</h3>
                <span class="country-pill">Germany</span>
            </div>

            <div class="uni-card" data-info="Europe Germany University of Bonn">
                <span class="region-tag">Europe</span>
                <span class="rank">#239</span>
                <h3>University of Bonn</h3>
                <span class="country-pill">Germany</span>
            </div>

            <div class="uni-card" data-info="Europe Germany University of Hamburg">
                <span class="region-tag">Europe</span>
                <span class="rank">#205</span>
                <h3>University of Hamburg</h3>
                <span class="country-pill">Germany</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific China Shanghai Jiao Tong University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#46</span>
                <h3>Shanghai Jiao Tong University</h3>
                <span class="country-pill">China</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific China Zhejiang University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#44</span>
                <h3>Zhejiang University</h3>
                <span class="country-pill">China</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific China Nanjing University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#133</span>
                <h3>Nanjing University</h3>
                <span class="country-pill">China</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Indonesia Gadjah Mada University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#263</span>
                <h3>Gadjah Mada University</h3>
                <span class="country-pill">Indonesia</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Thailand Mahidol University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#382</span>
                <h3>Mahidol University</h3>
                <span class="country-pill">Thailand</span>
            </div>

            <div class="uni-card"
                data-info="Asia Pacific Bangladesh BUET Bangladesh University of Engineering and Technology">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#801+</span>
                <h3>BUET</h3>
                <span class="country-pill">Bangladesh</span>
            </div>

            <div class="uni-card" data-info="Middle East Saudi Arabia King Saud University">
                <span class="region-tag">Middle East</span>
                <span class="rank">#266</span>
                <h3>King Saud University</h3>
                <span class="country-pill">Saudi Arabia</span>
            </div>

            <div class="uni-card" data-info="Middle East Jordan University of Jordan">
                <span class="region-tag">Middle East</span>
                <span class="rank">#368</span>
                <h3>University of Jordan</h3>
                <span class="country-pill">Jordan</span>
            </div>

            <div class="uni-card" data-info="Africa Algeria University of Algiers">
                <span class="region-tag">Africa</span>
                <span class="rank">#1001+</span>
                <h3>University of Algiers</h3>
                <span class="country-pill">Algeria</span>
            </div>

            <div class="uni-card" data-info="Africa Senegal Cheikh Anta Diop University">
                <span class="region-tag">Africa</span>
                <span class="rank">#801+</span>
                <h3>Cheikh Anta Diop University</h3>
                <span class="country-pill">Senegal</span>
            </div>

            <div class="uni-card" data-info="South America Ecuador San Francisco de Quito University">
                <span class="region-tag">South America</span>
                <span class="rank">#751+</span>
                <h3>Universidad San Francisco de Quito</h3>
                <span class="country-pill">Ecuador</span>
            </div>

            <div class="uni-card" data-info="South America Uruguay University of the Republic">
                <span class="region-tag">South America</span>
                <span class="rank">#801+</span>
                <h3>University of the Republic</h3>
                <span class="country-pill">Uruguay</span>
            </div>

            <div class="uni-card" data-info="Oceania Papua New Guinea University of Papua New Guinea">
                <span class="region-tag">Oceania</span>
                <span class="rank">#1201+</span>
                <h3>University of Papua New Guinea</h3>
                <span class="country-pill">Papua New Guinea</span>
            </div>
            <div class="uni-card" data-info="Europe Germany Karlsruhe Institute of Technology KIT">
                <span class="region-tag">Europe</span>
                <span class="rank">#119</span>
                <h3>Karlsruhe Institute of Technology</h3>
                <span class="country-pill">Germany</span>
            </div>

            <div class="uni-card" data-info="Europe Germany University of Freiburg">
                <span class="region-tag">Europe</span>
                <span class="rank">#192</span>
                <h3>University of Freiburg</h3>
                <span class="country-pill">Germany</span>
            </div>

            <div class="uni-card" data-info="Europe Germany University of Cologne">
                <span class="region-tag">Europe</span>
                <span class="rank">#268</span>
                <h3>University of Cologne</h3>
                <span class="country-pill">Germany</span>
            </div>

            <div class="uni-card" data-info="Europe Germany TU Berlin Technical University of Berlin">
                <span class="region-tag">Europe</span>
                <span class="rank">#154</span>
                <h3>Technical University of Berlin</h3>
                <span class="country-pill">Germany</span>
            </div>

            <div class="uni-card" data-info="Europe Germany University of Stuttgart">
                <span class="region-tag">Europe</span>
                <span class="rank">#314</span>
                <h3>University of Stuttgart</h3>
                <span class="country-pill">Germany</span>
            </div>
            <div class="uni-card" data-info="Europe Italy University of Bologna">
                <span class="region-tag">Europe</span>
                <span class="rank">#154</span>
                <h3>University of Bologna</h3>
                <span class="country-pill">Italy</span>
            </div>

            <div class="uni-card" data-info="Europe Italy University of Padua">
                <span class="region-tag">Europe</span>
                <span class="rank">#243</span>
                <h3>University of Padua</h3>
                <span class="country-pill">Italy</span>
            </div>

            <div class="uni-card" data-info="Europe Italy University of Pisa">
                <span class="region-tag">Europe</span>
                <span class="rank">#271</span>
                <h3>University of Pisa</h3>
                <span class="country-pill">Italy</span>
            </div>

            <div class="uni-card" data-info="Europe Italy University of Florence">
                <span class="region-tag">Europe</span>
                <span class="rank">#375</span>
                <h3>University of Florence</h3>
                <span class="country-pill">Italy</span>
            </div>

            <div class="uni-card" data-info="Europe Italy University of Naples Federico II">
                <span class="region-tag">Europe</span>
                <span class="rank">#347</span>
                <h3>University of Naples Federico II</h3>
                <span class="country-pill">Italy</span>
            </div>
            <div class="uni-card" data-info="Europe France University of Strasbourg">
                <span class="region-tag">Europe</span>
                <span class="rank">#401</span>
                <h3>University of Strasbourg</h3>
                <span class="country-pill">France</span>
            </div>

            <div class="uni-card" data-info="Europe France Aix Marseille University">
                <span class="region-tag">Europe</span>
                <span class="rank">#387</span>
                <h3>Aix-Marseille University</h3>
                <span class="country-pill">France</span>
            </div>

            <div class="uni-card" data-info="Europe Switzerland University of Basel">
                <span class="region-tag">Europe</span>
                <span class="rank">#124</span>
                <h3>University of Basel</h3>
                <span class="country-pill">Switzerland</span>
            </div>

            <div class="uni-card" data-info="Europe Austria Graz University of Technology">
                <span class="region-tag">Europe</span>
                <span class="rank">#284</span>
                <h3>Graz University of Technology</h3>
                <span class="country-pill">Austria</span>
            </div>

            <div class="uni-card" data-info="Europe Spain University of Valencia">
                <span class="region-tag">Europe</span>
                <span class="rank">#383</span>
                <h3>University of Valencia</h3>
                <span class="country-pill">Spain</span>
            </div>
            <div class="uni-card" data-info="Asia Pacific Japan Hokkaido University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#196</span>
                <h3>Hokkaido University</h3>
                <span class="country-pill">Japan</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Japan Tohoku University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#113</span>
                <h3>Tohoku University</h3>
                <span class="country-pill">Japan</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Japan Nagoya University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#152</span>
                <h3>Nagoya University</h3>
                <span class="country-pill">Japan</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific China University of Science and Technology of China">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#86</span>
                <h3>USTC</h3>
                <span class="country-pill">China</span>
            </div>

            <div class="uni-card"
                data-info="Asia Pacific South Korea POSTECH Pohang University of Science and Technology">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#100</span>
                <h3>POSTECH</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific India Jawaharlal Nehru University JNU">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#561</span>
                <h3>Jawaharlal Nehru University</h3>
                <span class="country-pill">India</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific India University of Hyderabad">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#581</span>
                <h3>University of Hyderabad</h3>
                <span class="country-pill">India</span>
            </div>
            <div class="uni-card" data-info="Europe UK University of Manchester">
                <span class="region-tag">Europe</span>
                <span class="rank">#32</span>
                <h3>University of Manchester</h3>
                <span class="country-pill">United Kingdom</span>
            </div>

            <div class="uni-card" data-info="Europe UK University of Edinburgh">
                <span class="region-tag">Europe</span>
                <span class="rank">#22</span>
                <h3>University of Edinburgh</h3>
                <span class="country-pill">United Kingdom</span>
            </div>

            <div class="uni-card" data-info="Europe UK King's College London">
                <span class="region-tag">Europe</span>
                <span class="rank">#40</span>
                <h3>King’s College London</h3>
                <span class="country-pill">United Kingdom</span>
            </div>

            <div class="uni-card" data-info="Europe UK London School of Economics LSE">
                <span class="region-tag">Europe</span>
                <span class="rank">#45</span>
                <h3>London School of Economics</h3>
                <span class="country-pill">United Kingdom</span>
            </div>

            <div class="uni-card" data-info="Europe UK University of Bristol">
                <span class="region-tag">Europe</span>
                <span class="rank">#55</span>
                <h3>University of Bristol</h3>
                <span class="country-pill">United Kingdom</span>
            </div>

            <div class="uni-card" data-info="Europe UK University of Warwick">
                <span class="region-tag">Europe</span>
                <span class="rank">#67</span>
                <h3>University of Warwick</h3>
                <span class="country-pill">United Kingdom</span>
            </div>

            <div class="uni-card" data-info="Europe UK University of Glasgow">
                <span class="region-tag">Europe</span>
                <span class="rank">#76</span>
                <h3>University of Glasgow</h3>
                <span class="country-pill">United Kingdom</span>
            </div>

            <div class="uni-card" data-info="Europe UK University of Birmingham">
                <span class="region-tag">Europe</span>
                <span class="rank">#84</span>
                <h3>University of Birmingham</h3>
                <span class="country-pill">United Kingdom</span>
            </div>

            <div class="uni-card" data-info="Europe UK University of Nottingham">
                <span class="region-tag">Europe</span>
                <span class="rank">#100</span>
                <h3>University of Nottingham</h3>
                <span class="country-pill">United Kingdom</span>
            </div>
            <div class="uni-card" data-info="North America USA New York University NYU">
                <span class="region-tag">North America</span>
                <span class="rank">#38</span>
                <h3>New York University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA University of California San Diego UCSD">
                <span class="region-tag">North America</span>
                <span class="rank">#62</span>
                <h3>UC San Diego</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA University of California Davis">
                <span class="region-tag">North America</span>
                <span class="rank">#102</span>
                <h3>UC Davis</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA University of Illinois Urbana Champaign UIUC">
                <span class="region-tag">North America</span>
                <span class="rank">#64</span>
                <h3>University of Illinois Urbana-Champaign</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA University of Wisconsin Madison">
                <span class="region-tag">North America</span>
                <span class="rank">#83</span>
                <h3>University of Wisconsin–Madison</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Purdue University">
                <span class="region-tag">North America</span>
                <span class="rank">#99</span>
                <h3>Purdue University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Ohio State University">
                <span class="region-tag">North America</span>
                <span class="rank">#120</span>
                <h3>Ohio State University</h3>
                <span class="country-pill">United States</span>
            </div>

            <div class="uni-card" data-info="North America USA Arizona State University">
                <span class="region-tag">North America</span>
                <span class="rank">#179</span>
                <h3>Arizona State University</h3>
                <span class="country-pill">United States</span>
            </div>
            <div class="uni-card" data-info="Europe Switzerland University of Bern">
                <span class="region-tag">Europe</span>
                <span class="rank">#120</span>
                <h3>University of Bern</h3>
                <span class="country-pill">Switzerland</span>
            </div>

            <div class="uni-card" data-info="Europe Switzerland University of Geneva">
                <span class="region-tag">Europe</span>
                <span class="rank">#149</span>
                <h3>University of Geneva</h3>
                <span class="country-pill">Switzerland</span>
            </div>

            <div class="uni-card" data-info="Europe Switzerland University of Lausanne">
                <span class="region-tag">Europe</span>
                <span class="rank">#176</span>
                <h3>University of Lausanne</h3>
                <span class="country-pill">Switzerland</span>
            </div>

            <div class="uni-card" data-info="Europe Switzerland University of Fribourg">
                <span class="region-tag">Europe</span>
                <span class="rank">#601+</span>
                <h3>University of Fribourg</h3>
                <span class="country-pill">Switzerland</span>
            </div>

            <div class="uni-card" data-info="Europe Switzerland USI Università della Svizzera italiana">
                <span class="region-tag">Europe</span>
                <span class="rank">#273</span>
                <h3>Università della Svizzera italiana</h3>
                <span class="country-pill">Switzerland</span>
            </div>
            <div class="uni-card" data-info="Europe Russia Lomonosov Moscow State University">
                <span class="region-tag">Europe</span>
                <span class="rank">#87</span>
                <h3>Lomonosov Moscow State University</h3>
                <span class="country-pill">Russia</span>
            </div>

            <div class="uni-card" data-info="Europe Russia Saint Petersburg State University">
                <span class="region-tag">Europe</span>
                <span class="rank">#315</span>
                <h3>Saint Petersburg State University</h3>
                <span class="country-pill">Russia</span>
            </div>

            <div class="uni-card" data-info="Europe Russia Novosibirsk State University">
                <span class="region-tag">Europe</span>
                <span class="rank">#260</span>
                <h3>Novosibirsk State University</h3>
                <span class="country-pill">Russia</span>
            </div>

            <div class="uni-card" data-info="Europe Russia Tomsk State University">
                <span class="region-tag">Europe</span>
                <span class="rank">#264</span>
                <h3>Tomsk State University</h3>
                <span class="country-pill">Russia</span>
            </div>
            <div class="uni-card" data-info="North America Mexico UNAM">
                <span class="region-tag">North America</span>
                <span class="rank">#93</span>
                <h3>National Autonomous University of Mexico (UNAM)</h3>
                <span class="country-pill">Mexico</span>
            </div>

            <div class="uni-card" data-info="North America Mexico Tecnológico de Monterrey">
                <span class="region-tag">North America</span>
                <span class="rank">#184</span>
                <h3>Tecnológico de Monterrey</h3>
                <span class="country-pill">Mexico</span>
            </div>

            <div class="uni-card" data-info="North America Mexico Universidad Panamericana">
                <span class="region-tag">North America</span>
                <span class="rank">#701+</span>
                <h3>Universidad Panamericana</h3>
                <span class="country-pill">Mexico</span>
            </div>


            <div class="uni-card" data-info="South America Argentina National University of La Plata">
                <span class="region-tag">South America</span>
                <span class="rank">#651+</span>
                <h3>National University of La Plata</h3>
                <span class="country-pill">Argentina</span>
            </div>

            <div class="uni-card" data-info="South America Argentina Universidad Austral">
                <span class="region-tag">South America</span>
                <span class="rank">#501–510</span>
                <h3>Universidad Austral</h3>
                <span class="country-pill">Argentina</span>
            </div>
            <div class="uni-card" data-info="South America Brazil University of São Paulo USP">
                <span class="region-tag">South America</span>
                <span class="rank">#85</span>
                <h3>University of São Paulo</h3>
                <span class="country-pill">Brazil</span>
            </div>

            <div class="uni-card" data-info="South America Brazil State University of Campinas UNICAMP">
                <span class="region-tag">South America</span>
                <span class="rank">#220</span>
                <h3>University of Campinas (UNICAMP)</h3>
                <span class="country-pill">Brazil</span>
            </div>

            <div class="uni-card" data-info="South America Brazil Federal University of Rio de Janeiro">
                <span class="region-tag">South America</span>
                <span class="rank">#333</span>
                <h3>Federal University of Rio de Janeiro</h3>
                <span class="country-pill">Brazil</span>
            </div>

            <div class="uni-card" data-info="South America Brazil São Paulo State University UNESP">
                <span class="region-tag">South America</span>
                <span class="rank">#450</span>
                <h3>São Paulo State University (UNESP)</h3>
                <span class="country-pill">Brazil</span>
            </div>


            <div class="uni-card" data-info="Europe Portugal University of Porto">
                <span class="region-tag">Europe</span>
                <span class="rank">#253</span>
                <h3>University of Porto</h3>
                <span class="country-pill">Portugal</span>
            </div>

            <div class="uni-card" data-info="Europe Portugal University of Coimbra">
                <span class="region-tag">Europe</span>
                <span class="rank">#440</span>
                <h3>University of Coimbra</h3>
                <span class="country-pill">Portugal</span>
            </div>


            <div class="uni-card" data-info="Middle East Qatar Hamad Bin Khalifa University">
                <span class="region-tag">Middle East</span>
                <span class="rank">#377</span>
                <h3>Hamad Bin Khalifa University</h3>
                <span class="country-pill">Qatar</span>
            </div>

            <div class="uni-card" data-info="Middle East Qatar Weill Cornell Medicine Qatar">
                <span class="region-tag">Middle East</span>
                <span class="rank">Medical</span>
                <h3>Weill Cornell Medicine – Qatar</h3>
                <span class="country-pill">Qatar</span>
            </div>
            <div class="uni-card" data-info="Africa Egypt Cairo University">
                <span class="region-tag">Africa</span>
                <span class="rank">#371</span>
                <h3>Cairo University</h3>
                <span class="country-pill">Egypt</span>
            </div>

            <div class="uni-card" data-info="Africa Egypt Ain Shams University">
                <span class="region-tag">Africa</span>
                <span class="rank">#721+</span>
                <h3>Ain Shams University</h3>
                <span class="country-pill">Egypt</span>
            </div>
            <div class="uni-card" data-info="Africa South Africa University of Cape Town">
                <span class="region-tag">Africa</span>
                <span class="rank">#173</span>
                <h3>University of Cape Town</h3>
                <span class="country-pill">South Africa</span>
            </div>

            <div class="uni-card" data-info="Africa South Africa University of the Witwatersrand">
                <span class="region-tag">Africa</span>
                <span class="rank">#264</span>
                <h3>University of the Witwatersrand</h3>
                <span class="country-pill">South Africa</span>
            </div>

            <div class="uni-card" data-info="Africa South Africa Stellenbosch University">
                <span class="region-tag">Africa</span>
                <span class="rank">#296</span>
                <h3>Stellenbosch University</h3>
                <span class="country-pill">South Africa</span>
            </div>
            <div class="uni-card" data-info="Africa Nigeria University of Ibadan">
                <span class="region-tag">Africa</span>
                <span class="rank">#801+</span>
                <h3>University of Ibadan</h3>
                <span class="country-pill">Nigeria</span>
            </div>

            <div class="uni-card" data-info="Africa Nigeria Covenant University">
                <span class="region-tag">Africa</span>
                <span class="rank">#401–450</span>
                <h3>Covenant University</h3>
                <span class="country-pill">Nigeria</span>
            </div>
            <div class="uni-card" data-info="Africa Morocco Mohammed V University">
                <span class="region-tag">Africa</span>
                <span class="rank">#1001+</span>
                <h3>Mohammed V University</h3>
                <span class="country-pill">Morocco</span>
            </div>

            <div class="uni-card" data-info="Europe Switzerland ETH Zurich">
                <span class="region-tag">Europe</span>
                <span class="rank">#7</span>
                <h3>ETH Zurich</h3>
                <span class="country-pill">Switzerland</span>
            </div>
            <div class="uni-card" data-info="Asia Pacific South Korea Korea University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#67</span>
                <h3>Korea University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Sungkyunkwan University SKKU">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#99</span>
                <h3>Sungkyunkwan University (SKKU)</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Hanyang University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#164</span>
                <h3>Hanyang University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Kyung Hee University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#332</span>
                <h3>Kyung Hee University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Ewha Womans University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#352</span>
                <h3>Ewha Womans University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Sogang University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#456</span>
                <h3>Sogang University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Inha University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#531</span>
                <h3>Inha University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Pusan National University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#611</span>
                <h3>Pusan National University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Chonnam National University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#681</span>
                <h3>Chonnam National University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Konkuk University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#701+</span>
                <h3>Konkuk University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Dongguk University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#721+</span>
                <h3>Dongguk University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Europe Germany Technical University of Munich">
                <span class="region-tag">Europe</span>
                <span class="rank">#29</span>
                <h3>Technical University of Munich</h3>
                <span class="country-pill">Germany</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Singapore NUS National University of Singapore">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#8</span>
                <h3>National University of Singapore</h3>
                <span class="country-pill">Singapore</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific China Peking University">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#14</span>
                <h3>Peking University</h3>
                <span class="country-pill">China</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Australia University of Melbourne">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#19</span>
                <h3>University of Melbourne</h3>
                <span class="country-pill">Australia</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Hong Kong HKU University of Hong Kong">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#11</span>
                <h3>University of Hong Kong</h3>
                <span class="country-pill">Hong Kong</span>
            </div>

            <div class="uni-card" data-info="Middle East Saudi Arabia King Fahd University">
                <span class="region-tag">Middle East</span>
                <span class="rank">#67</span>
                <h3>King Fahd University</h3>
                <span class="country-pill">Saudi Arabia</span>
            </div>

            <div class="uni-card" data-info="South America Argentina Universidad de Buenos Aires">
                <span class="region-tag">South America</span>
                <span class="rank">#84</span>
                <h3>Universidad de Buenos Aires</h3>
                <span class="country-pill">Argentina</span>
            </div>

            <div class="uni-card" data-info="Europe Netherlands University of Amsterdam">
                <span class="region-tag">Europe</span>
                <span class="rank">#53</span>
                <h3>University of Amsterdam</h3>
                <span class="country-pill">Netherlands</span>
            </div>
            <div class="uni-card" data-info="Asia Pacific Japan The University of Tokyo #23">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#23</span>
                <h3>The University of Tokyo</h3>
                <span class="country-pill">Japan</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Japan Kyoto University #57">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#57</span>
                <h3>Kyoto University</h3>
                <span class="country-pill">Japan</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Japan Osaka University #86">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#86</span>
                <h3>Osaka University</h3>
                <span class="country-pill">Japan</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Seoul National University SNU #21">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#21</span>
                <h3>Seoul National University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card"
                data-info="Asia Pacific South Korea KAIST Korea Advanced Institute of Science and Technology">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#53</span>
                <h3>KAIST</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific South Korea Yonsei University #76">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#76</span>
                <h3>Yonsei University</h3>
                <span class="country-pill">South Korea</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Malaysia Universiti Malaya UM #65">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#65</span>
                <h3>Universiti Malaya</h3>
                <span class="country-pill">Malaysia</span>
            </div>

            <div class="uni-card" data-info="Asia Pacific Malaysia Universiti Putra Malaysia UPM">
                <span class="region-tag">Asia-Pacific</span>
                <span class="rank">#123</span>
                <h3>Universiti Putra Malaysia</h3>
                <span class="country-pill">Malaysia</span>
            </div>

            <div class="uni-card" data-info="Middle East UAE United Arab Emirates Khalifa University">
                <span class="region-tag">Middle East</span>
                <span class="rank">#181</span>
                <h3>Khalifa University</h3>
                <span class="country-pill">UAE</span>
            </div>

            <div class="uni-card" data-info="Europe Italy Politecnico di Milano #98">
                <span class="region-tag">Europe</span>
                <span class="rank">#98</span>
                <h3>Politecnico di Milano</h3>
                <span class="country-pill">Italy</span>
            </div>

            <div class="uni-card" data-info="Europe Italy Sapienza University of Rome">
                <span class="region-tag">Europe</span>
                <span class="rank">#134</span>
                <h3>Sapienza University of Rome</h3>
                <span class="country-pill">Italy</span>
            </div>

            <div class="uni-card" data-info="Europe Germany LMU Munich Ludwig-Maximilians-Universität">
                <span class="region-tag">Europe</span>
                <span class="rank">#38</span>
                <h3>LMU Munich</h3>
                <span class="country-pill">Germany</span>
            </div>

            <div class="uni-card" data-info="Europe Germany Heidelberg University #47">
                <span class="region-tag">Europe</span>
                <span class="rank">#47</span>
                <h3>Heidelberg University</h3>
                <span class="country-pill">Germany</span>
            </div>

            <div class="uni-card" data-info="Europe Switzerland EPFL École Polytechnique Fédérale de Lausanne">
                <span class="region-tag">Europe</span>
                <span class="rank">#26</span>
                <h3>EPFL</h3>
                <span class="country-pill">Switzerland</span>
            </div>

            <div class="uni-card" data-info="Europe Switzerland University of Zurich">
                <span class="region-tag">Europe</span>
                <span class="rank">#91</span>
                <h3>University of Zurich</h3>
                <span class="country-pill">Switzerland</span>
            </div>

            <div class="uni-card" data-info="Europe Norway University of Oslo #101">
                <span class="region-tag">Europe</span>
                <span class="rank">#101</span>
                <h3>University of Oslo</h3>
                <span class="country-pill">Norway</span>
            </div>

            <div class="uni-card" data-info="Europe Luxembourg University of Luxembourg">
                <span class="region-tag">Europe</span>
                <span class="rank">#201+</span>
                <h3>University of Luxembourg</h3>
                <span class="country-pill">Luxembourg</span>
            </div>

            <div class="uni-card" data-info="Europe Netherlands Delft University of Technology TU Delft">
                <span class="region-tag">Europe</span>
                <span class="rank">#47</span>
                <h3>TU Delft</h3>
                <span class="country-pill">Netherlands</span>
            </div>
        </div>
    </main>

    </div>

    <div id="uniModal" class="modal-overlay" onclick="closeModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span class="close-btn" onclick="closeModal()"
                style="position:absolute; right:20px; cursor:pointer; font-size:24px;">&times;</span>

            <h2 id="modalTitle" style="color:var(--accent); font-size: 28px; margin:0;"></h2>
            <p id="modalRegion"
                style="color: #888; text-transform: uppercase; letter-spacing: 2px; font-size: 12px; margin-top: 5px;">
            </p>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 20px 0;">
                <div
                    style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 12px; text-align: center;">
                    <small style="color: #666; font-size: 10px; display: block;">RANK</small>
                    <span id="statRank" style="color: var(--accent); font-weight: bold;"></span>
                </div>
                <div
                    style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 12px; text-align: center;">
                    <small style="color: #666; font-size: 10px; display: block;">ADMIT %</small>
                    <span id="statAccept" style="color: var(--accent); font-weight: bold;">-</span>
                </div>
                <div
                    style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 12px; text-align: center;">
                    <small style="color: #666; font-size: 10px; display: block;">FEES</small>
                    <span id="statFees" style="color: var(--accent); font-weight: bold;">-</span>
                </div>
            </div>

            <p id="modalDesc" style="line-height: 1.6; color: #ddd; margin-bottom: 20px;">Details for this institution
                are being updated.</p>

            <a id="siteLink" target="_blank"
                style="display: block; text-align: center; padding: 12px; background: var(--accent); color: #000; text-decoration: none; border-radius: 10px; font-weight: bold;">Visit
                Official Website</a>
        </div>
    </div>

<script>
    // ১. পিএইচপি সেশন থেকে সরাসরি ইউজারের লগইন স্ট্যাটাস নেওয়া হলো
    const isUserLoggedIn = <?php echo isset($_SESSION['email']) ? 'true' : 'false'; ?>;
    const currentUserEmail = "<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>";

    window.addEventListener('DOMContentLoaded', () => {
        const loginView = document.getElementById('loginView');
        const dashboardView = document.getElementById('dashboardView');

        // ২. পিএইচপি সেশন একটিভ থাকলে ড্যাশবোর্ড দেখাবে
        if (isUserLoggedIn) {
            if (loginView) loginView.style.display = 'none';
            if (dashboardView) dashboardView.style.display = 'grid'; // আপনার সিএসএস অনুযায়ী grid/flex
            
            // পিএইচপি সেশনের ডেটা ব্যাকআপ হিসেবে ব্রাউজারের localStorage এও সেভ করে রাখা হলো
            localStorage.setItem('currentUser', JSON.stringify({ email: currentUserEmail }));
        } else {
            // যদি ইউজার লগইন না থাকে এবং জোর করে ড্যাশবোর্ড দেখতে চায়
            if (dashboardView && dashboardView.style.display !== 'none') {
                alert("Security Alert: Access Denied! Please Login.");
                if (loginView) loginView.style.display = 'flex';
                if (dashboardView) dashboardView.style.display = 'none';
                window.location.href = 'index.php'; // সরাসরি লগইন পেজে রিডাইরেক্ট করা
            }
        }
    });
        const DB_KEY = 'gsgs_users_list';

        const uniData = {
            "MIT": {
                tagline: "World leader in tech, science, and engineering located in Cambridge.",
                accept: "4%",
                fees: "$60,150",
                site: "https://www.mit.edu"
            },
            "Stanford University": {
                tagline: "Located in Silicon Valley, known for entrepreneurship and tech.",
                accept: "3.9%",
                fees: "$61,731",
                site: "https://www.stanford.edu"
            },
            "Harvard University": {
                tagline: "The oldest institution of higher education in the United States.",
                accept: "3.2%",
                fees: "$59,076",
                site: "https://www.harvard.edu"
            },

            "California Institute of Technology": {
                tagline: "World-renowned for science and engineering research in Pasadena.",
                accept: "3.9%",
                fees: "$63,000",
                site: "https://www.caltech.edu"
            },
            "Princeton University": {
                tagline: "An Ivy League leader focused on undergraduate and research excellence.",
                accept: "4.4%",
                fees: "$59,710",
                site: "https://www.princeton.edu"
            },
            "Yale University": {
                tagline: "Elite Ivy League institution known for social sciences and law.",
                accept: "4.5%",
                fees: "$64,700",
                site: "https://www.yale.edu"
            },
            "Columbia University": {
                tagline: "A global research hub located in the heart of New York City.",
                accept: "3.9%",
                fees: "$66,139",
                site: "https://www.columbia.edu"
            },
            "University of Chicago": {
                tagline: "Famous for its rigorous core curriculum and influential research.",
                accept: "5%",
                fees: "$64,000",
                site: "https://www.uchicago.edu"
            },
            "Sorbonne University": {
                tagline: "A historic public research university in Paris, France.",
                accept: "15%",
                fees: "$3,500", // International public rate approx.
                site: "https://www.sorbonne-universite.fr/en"
            },
            "PSL University": {
                tagline: "France's leading university in science, arts, and humanities.",
                accept: "10%",
                fees: "$4,200",
                site: "https://psl.eu/en"
            },
            "University of Barcelona": {
                tagline: "A top-ranked public university in the heart of Catalonia, Spain.",
                accept: "40%",
                fees: "$3,200",
                site: "https://www.ub.edu/web/portal/en/"
            },
            "Autonomous University of Madrid": {
                tagline: "One of the most prestigious research institutions in Spain.",
                accept: "35%",
                fees: "$3,800",
                site: "https://www.uam.es/uam/en/inicio"
            },
            "Lund University": {
                tagline: "One of northern Europe's oldest and most prestigious universities.",
                accept: "20%",
                fees: "$14,500", // Average for non-EU students
                site: "https://www.lunduniversity.lu.se"
            },
            "Uppsala University": {
                tagline: "Sweden's oldest university, world-class in medicine and science.",
                accept: "25%",
                fees: "$13,800",
                site: "https://www.uu.se/en"
            },
            "University of Copenhagen": {
                tagline: "The largest research and education institution in Denmark.",
                accept: "20%",
                fees: "$16,000",
                site: "https://www.ku.dk/english"
            },
            "University of Helsinki": {
                tagline: "Finland's leading university for multidisciplinary research.",
                accept: "17%",
                fees: "$14,200",
                site: "https://www.helsinki.fi/en"
            },
            "Indian Institute of Science": {
                tagline: "India's premier institute for advanced scientific research (IISc).",
                accept: "2%",
                fees: "$400", // Very low tuition for public research
                site: "https://iisc.ac.in"
            },
            "IIT Bombay": {
                tagline: "Top technical institute in India, highly competitive (JEE Advanced).",
                accept: "1%",
                fees: "$2,800",
                site: "https://www.iitb.ac.in"
            },
            "IIT Delhi": {
                tagline: "Leading engineering and technology hub in India's capital.",
                accept: "1%",
                fees: "$2,800",
                site: "https://home.iitd.ac.in"
            },
            "Tsinghua University": {
                tagline: "Often ranked as the best engineering school in Asia (China).",
                accept: "2%",
                fees: "$4,500",
                site: "https://www.tsinghua.edu.cn/en/"
            },
            "Fudan University": {
                tagline: "A top-tier research university in Shanghai, China.",
                accept: "5%",
                fees: "$3,800",
                site: "https://www.fudan.edu.cn/en/"
            },
            "National Taiwan University": {
                tagline: "The most prestigious and largest university in Taiwan.",
                accept: "15%",
                fees: "$4,200",
                site: "https://www.ntu.edu.tw/english/"
            },
            "Chulalongkorn University": {
                tagline: "Thailand's oldest and most prestigious university.",
                accept: "10%",
                fees: "$5,500",
                site: "https://www.chula.ac.th/en/"
            },
            "University of Indonesia": {
                tagline: "The premier state university in Indonesia.",
                accept: "8%",
                fees: "$2,500",
                site: "https://www.ui.ac.id/en/"
            },
            "University of Dhaka": {
                tagline: "The oldest and highest-ranked public university in Bangladesh.",
                accept: "3%",
                fees: "$150", // Very low cost for public students
                site: "https://www.du.ac.bd"
            },
            "Quaid-i-Azam University": {
                tagline: "Ranked #1 in Pakistan for research and international outlook.",
                accept: "10%",
                fees: "$1,200",
                site: "https://qau.edu.pk"
            },
            "University of Cape Town": {
                tagline: "The highest-ranked university in Africa, located in South Africa.",
                accept: "25%",
                fees: "$4,500",
                site: "http://www.uct.ac.za"
            },
            "Cairo University": {
                tagline: "A major public university in Giza, Egypt, historic and large.",
                accept: "30%",
                fees: "$2,000",
                site: "https://cu.edu.eg/Home"
            },
            "University of Auckland": {
                tagline: "The largest and highest-ranked university in New Zealand.",
                accept: "45%",
                fees: "$28,000",
                site: "https://www.auckland.ac.nz"
            },
            "University of Otago": {
                tagline: "New Zealand's oldest university, world-class for health sciences.",
                accept: "50%",
                fees: "$25,500",
                site: "https://www.otago.ac.nz"
            },
            "Brown University": {
                tagline: "Ivy League member famous for its flexible Open Curriculum.",
                accept: "5%",
                fees: "$65,656",
                site: "https://www.brown.edu"
            },
            "Dartmouth College": {
                tagline: "A prestigious Ivy League member known for its focused undergraduate teaching.",
                accept: "6.2%",
                fees: "$63,684",
                site: "https://home.dartmouth.edu"
            },
            "Duke University": {
                tagline: "A top-tier private research university located in Durham, North Carolina.",
                accept: "5.9%",
                fees: "$63,054",
                site: "https://www.duke.edu"
            },
            "Northwestern University": {
                tagline: "Leading research university famous for journalism, music, and business.",
                accept: "7%",
                fees: "$64,887",
                site: "https://www.northwestern.edu"
            },
            "Johns Hopkins University": {
                tagline: "World leader in medicine, public health, and research funding.",
                accept: "6.5%",
                fees: "$62,840",
                site: "https://www.jhu.edu"
            },
            "Trinity College Dublin": {
                tagline: "Ireland's highest-ranked university with a historic campus in Dublin.",
                accept: "33%",
                fees: "$22,500",
                site: "https://www.tcd.ie"
            },
            "University College Dublin": {
                tagline: "A global university known for its research and vibrant student life.",
                accept: "40%",
                fees: "$21,000",
                site: "https://www.ucd.ie"
            },
            "Royal Holloway, University of London": {
                "tagline": "A top-tier UK research university known for its stunning Egham campus and academic excellence.",
                "accept": "17%",
                "fees": "$25,000 - $32,000",
                "site": "https://www.royalholloway.ac.uk"
            },
            "KU Leuven": {
                tagline: "One of Europe's oldest and most innovative research universities.",
                accept: "70%",
                fees: "$1,200", // Public rate for international students varies
                site: "https://www.kuleuven.be/english/"
            },
            "Ghent University": {
                tagline: "A top-tier public university in Belgium with a strong global focus.",
                accept: "60%",
                fees: "$1,100",
                site: "https://www.ugent.be/en"
            },
            "University of Vienna": {
                tagline: "One of the largest and most famous universities in Central Europe.",
                accept: "40%",
                fees: "$1,600",
                site: "https://www.univie.ac.at/en/"
            },
            "University of Lisbon": {
                tagline: "The largest and most prestigious university in Portugal.",
                accept: "35%",
                fees: "$3,800",
                site: "https://www.ulisboa.pt/en"
            },
            "Charles University": {
                tagline: "The oldest and largest university in the Czech Republic.",
                accept: "28%",
                fees: "$5,000",
                site: "https://cuni.cz/UKEN-1.html"
            },
            "University of Warsaw": {
                tagline: "Poland's leading university and a major research center.",
                accept: "30%",
                fees: "$3,500",
                site: "https://en.uw.edu.pl"
            },
            "Vietnam National University, Hanoi": {
                tagline: "One of the two most prestigious research universities in Vietnam.",
                accept: "20%",
                fees: "$1,500",
                site: "https://vnu.edu.vn/eng/"
            },
            "University of Colombo": {
                tagline: "The oldest and highest-ranked public university in Sri Lanka.",
                accept: "5%",
                fees: "$200",
                site: "https://cmb.ac.lk"
            },
            "Tribhuvan University": {
                tagline: "The oldest and largest state university in Nepal.",
                accept: "40%",
                fees: "$300",
                site: "https://tu.edu.np"
            },
            "Hebrew University of Jerusalem": {
                tagline: "A world-class research institution and Israel's first university.",
                accept: "25%",
                fees: "$4,500",
                site: "https://new.huji.ac.il/en"
            },
            "Qatar University": {
                tagline: "The primary institution of higher education in Qatar.",
                accept: "20%",
                fees: "$10,500",
                site: "https://www.qu.edu.qa"
            },
            "Sultan Qaboos University": {
                tagline: "The only public university in the Sultanate of Oman.",
                accept: "15%",
                fees: "Free/Varies",
                site: "https://www.squ.edu.om"
            },
            "University of Ibadan": {
                tagline: "The oldest and most prestigious degree-awarding institution in Nigeria.",
                accept: "10%",
                fees: "$500",
                site: "https://ui.edu.ng"
            },
            "University of Nairobi": {
                tagline: "A leading research and teaching university in Kenya.",
                accept: "15%",
                fees: "$1,800",
                site: "https://uonbi.ac.ke"
            },
            "Mohammed V University": {
                tagline: "The premier public university in Rabat, Morocco.",
                accept: "30%",
                fees: "$500",
                site: "http://www.um5.ac.ma"
            },
            "University of São Paulo": {
                tagline: "The most important research institution in Brazil and Latin America.",
                accept: "10%",
                fees: "Free", // Public university in Brazil
                site: "https://www.usp.br/english/"
            },
            "Pontifical Catholic University of Chile": {
                tagline: "One of the highest-ranked universities in Latin America.",
                accept: "20%",
                fees: "$7,500",
                site: "https://www.uc.cl/en"
            },
            "UNAM": {
                tagline: "The National Autonomous University of Mexico, a cultural and research giant.",
                accept: "10%",
                fees: "$1,000", // Very low for international students
                site: "https://www.unam.mx"
            },
            "Rice University": {
                tagline: "Highly selective private research university in Houston, Texas.",
                accept: "8.7%",
                fees: "$57,210",
                site: "https://www.rice.edu"
            },
            "Vanderbilt University": {
                tagline: "A top-ranked private research university in Nashville, Tennessee.",
                accept: "6.7%",
                fees: "$61,618",
                site: "https://www.vanderbilt.edu"
            },
            "University of Michigan": {
                tagline: "A premier public research university in the United States.",
                accept: "18%",
                fees: "$55,000",
                site: "https://umich.edu"
            },
            "UCLA": {
                tagline: "The most applied-to university in the US, famous for sports and research.",
                accept: "9%",
                fees: "$44,000",
                site: "https://www.ucla.edu"
            },
            "UC Berkeley": {
                tagline: "A world-leading public research university in California.",
                accept: "11.4%",
                fees: "$44,500",
                site: "https://www.berkeley.edu"
            },
            "Eötvös Loránd University": {
                tagline: "The oldest and largest university in Hungary, located in Budapest.",
                accept: "35%",
                fees: "$3,500",
                site: "https://www.elte.hu/en"
            },
            "University of Athens": {
                tagline: "A historic institution and the top-ranked university in Greece.",
                accept: "40%",
                fees: "$1,600",
                site: "https://en.uoa.gr"
            },
            "University of Bucharest": {
                tagline: "One of the most important education and research centers in Romania.",
                accept: "45%",
                fees: "$2,800",
                site: "https://unibuc.ro/?lang=en"
            },
            "Comenius University": {
                tagline: "The largest university in Slovakia, with a rich academic tradition.",
                accept: "40%",
                fees: "$3,200",
                site: "https://uniba.sk/en/"
            },
            "University of Zagreb": {
                tagline: "The oldest and most influential university in Croatia.",
                accept: "35%",
                fees: "$2,500",
                site: "http://www.unizg.hr/homepage/"
            },
            "University of the Philippines": {
                tagline: "The national university system of the Philippines.",
                accept: "12%",
                fees: "$1,000",
                site: "https://up.edu.ph"
            },
            "National University of Mongolia": {
                tagline: "The oldest university in Mongolia and a premier research hub.",
                accept: "50%",
                fees: "$1,800",
                site: "https://www.num.edu.mn/en"
            },
            "Royal University of Phnom Penh": {
                tagline: "Cambodia's oldest and largest public university.",
                accept: "60%",
                fees: "$1,200",
                site: "http://www.rupp.edu.kh"
            },
            "National University of Laos": {
                tagline: "The premier higher education institution in Laos.",
                accept: "55%",
                fees: "$900",
                site: "http://www.nuol.edu.la"
            },
            "University of Tehran": {
                tagline: "The oldest and most prestigious university in Iran.",
                accept: "10%",
                fees: "$1,500",
                site: "https://ut.ac.ir/en"
            },
            "Sharif University of Technology": {
                tagline: "The 'MIT of Iran', known for elite engineering and science.",
                accept: "5%",
                fees: "$2,000",
                site: "https://en.sharif.edu"
            },
            "University of Baghdad": {
                tagline: "The largest university in Iraq and a historic academic center.",
                accept: "30%",
                fees: "$1,100",
                site: "https://uobaghdad.edu.iq/?lang=en"
            },
            "Addis Ababa University": {
                tagline: "The largest and oldest university in Ethiopia.",
                accept: "25%",
                fees: "$800",
                site: "http://www.aau.edu.et"
            },
            "University of Ghana": {
                tagline: "The premier and largest university in Ghana.",
                accept: "35%",
                fees: "$2,500",
                site: "https://www.ug.edu.gh"
            },
            "University of Tunis": {
                tagline: "A major educational and research hub in the heart of Tunisia.",
                accept: "40%",
                fees: "$1,200",
                site: "http://www.utunis.rnu.tn"
            },
            "Pontifical Catholic University of Peru": {
                tagline: "One of the most prestigious private universities in Peru.",
                accept: "25%",
                fees: "$8,500",
                site: "https://www.pucp.edu.pe"
            },
            "University of the Andes": {
                tagline: "A top-ranked private research university in Bogotá, Colombia.",
                accept: "20%",
                fees: "$9,200",
                site: "https://uniandes.edu.co/en"
            },
            "Central University of Venezuela": {
                tagline: "Venezuela's most important and historic university.",
                accept: "15%",
                fees: "Free/Varies",
                site: "http://www.ucv.ve"
            },
            "University of the South Pacific": {
                tagline: "A premier regional university serving 12 Pacific Island nations.",
                accept: "65%",
                fees: "$4,500",
                site: "https://www.usp.ac.fj"
            },
            "University of Toronto": {
                tagline: "Canada's top university, global leader in research and innovation.",
                accept: "43%",
                fees: "$48,000",
                site: "https://www.utoronto.ca"
            },
            "University of Oxford": {
                tagline: "The oldest university in the English-speaking world.",
                accept: "17%",
                fees: "$46,000",
                site: "https://www.ox.ac.uk"
            },
            "Imperial College London": {
                tagline: "A world-class institution focusing on science, engineering, and medicine.",
                accept: "14%",
                fees: "$49,000",
                site: "https://www.imperial.ac.uk"
            },
            "Boston University": {
                tagline: "A large private research university in the heart of Boston.",
                accept: "14%",
                fees: "$63,000",
                site: "https://www.bu.edu"
            },
            "University of Southern California": {
                tagline: "A world-leading private research university in Los Angeles.",
                accept: "12%",
                fees: "$66,000",
                site: "https://www.usc.edu"
            },
            "Pennsylvania State University": {
                tagline: "A top-ranked public research university in Pennsylvania.",
                accept: "55%",
                fees: "$39,000",
                site: "https://www.psu.edu"
            },
            "University of Texas at Austin": {
                tagline: "A major public research university and pride of the Lone Star State.",
                accept: "31%",
                fees: "$41,000",
                site: "https://www.utexas.edu"
            },
            "University of Washington": {
                tagline: "A global hub for innovation and research in the Pacific Northwest.",
                accept: "48%",
                fees: "$40,500",
                site: "https://www.uw.edu"
            },
            "Université Paris Cité": {
                tagline: "A world-class multidisciplinary university in the heart of Paris.",
                accept: "15%",
                fees: "$3,800",
                site: "https://u-paris.fr/en/"
            },
            "University of Bordeaux": {
                tagline: "Renowned for its research and its campus in the wine capital of France.",
                accept: "20%",
                fees: "$3,200",
                site: "https://www.u-bordeaux.com"
            },
            "Pompeu Fabra University": {
                tagline: "A modern, highly-ranked research university in Barcelona.",
                accept: "22%",
                fees: "$4,100",
                site: "https://www.upf.edu/en/"
            },
            "Complutense University of Madrid": {
                tagline: "One of the oldest and largest universities in the Spanish-speaking world.",
                accept: "45%",
                fees: "$3,500",
                site: "https://www.ucm.es/english"
            },
            "RWTH Aachen University": {
                tagline: "Germany's top technical university, focused on engineering.",
                accept: "10%",
                fees: "$700", // Semester contribution
                site: "https://www.rwth-aachen.de"
            },
            "University of Bonn": {
                tagline: "A premier German university, home to multiple Nobel laureates.",
                accept: "18%",
                fees: "$700",
                site: "https://www.uni-bonn.de/en"
            },
            "University of Hamburg": {
                tagline: "Northern Germany's largest research and education institution.",
                accept: "15%",
                fees: "$750",
                site: "https://www.uni-hamburg.de/en.html"
            },
            "Shanghai Jiao Tong University": {
                tagline: "A top-tier research university in China with global influence.",
                accept: "5%",
                fees: "$4,200",
                site: "https://en.sjtu.edu.cn"
            },
            "Zhejiang University": {
                tagline: "One of China's oldest, most selective, and prestigious universities.",
                accept: "5%",
                fees: "$4,800",
                site: "https://www.zju.edu.cn/english/"
            },
            "Nanjing University": {
                tagline: "A member of the C9 League and a major research center in China.",
                accept: "7%",
                fees: "$4,200",
                site: "https://www.nju.edu.cn/en/"
            },
            "Gadjah Mada University": {
                tagline: "The largest and oldest flagship university in Indonesia.",
                accept: "10%",
                fees: "$2,200",
                site: "https://ugm.ac.id/en/"
            },
            "Mahidol University": {
                tagline: "Renowned as the top medical and research university in Thailand.",
                accept: "15%",
                fees: "$5,800",
                site: "https://mahidol.ac.th"
            },
            "BUET": {
                tagline: "The premier engineering university in Bangladesh with extreme competition.",
                accept: "1.5%",
                fees: "$150",
                site: "https://www.buet.ac.bd"
            },
            "King Saud University": {
                tagline: "The first public university in Saudi Arabia, leader in research.",
                accept: "20%",
                fees: "Free/Varies",
                site: "https://ksu.edu.sa/en/"
            },
            "University of Jordan": {
                tagline: "The largest and oldest institution of higher education in Jordan.",
                accept: "30%",
                fees: "$4,500",
                site: "http://ju.edu.jo/home.aspx"
            },
            "University of Algiers": {
                tagline: "The premier public university system in Algeria.",
                accept: "35%",
                fees: "$400",
                site: "https://www.univ-alger.dz"
            },
            "Cheikh Anta Diop University": {
                tagline: "A major university in Dakar, Senegal, influential in Francophone Africa.",
                accept: "40%",
                fees: "$600",
                site: "https://www.ucad.sn"
            },
            "Universidad San Francisco de Quito": {
                tagline: "A leading private liberal arts university in Ecuador.",
                accept: "35%",
                fees: "$9,500",
                site: "https://www.usfq.edu.ec"
            },
            "University of the Republic": {
                tagline: "Uruguay's most important public and secular university.",
                accept: "45%",
                fees: "Free/Varies",
                site: "https://udelar.edu.uy"
            },
            "University of Papua New Guinea": {
                tagline: "The oldest and most prestigious university in Papua New Guinea.",
                accept: "25%",
                fees: "$1,800",
                site: "https://www.upng.ac.nz"
            },
            "Karlsruhe Institute of Technology": {
                tagline: "Germany's leading technical research university (KIT).",
                accept: "20%",
                fees: "$3,200", // For non-EU students in Baden-Württemberg
                site: "https://www.kit.edu/english/"
            },
            "University of Freiburg": {
                tagline: "A world-class research university with a long tradition in Germany.",
                accept: "15%",
                fees: "$3,200", // For non-EU students
                site: "https://uni-freiburg.de/en/"
            },
            "University of Cologne": {
                tagline: "One of the largest and oldest universities in Europe (Germany).",
                accept: "18%",
                fees: "$650",
                site: "https://www.uni-koeln.de/en/"
            },
            "Technical University of Berlin": {
                tagline: "A leading member of TU9, focused on engineering and technology.",
                accept: "15%",
                fees: "$650",
                site: "https://www.tu.berlin/en/"
            },
            "University of Stuttgart": {
                tagline: "A major research university in Germany's high-tech industrial hub.",
                accept: "20%",
                fees: "$3,200",
                site: "https://www.uni-stuttgart.de/en/"
            },
            "University of Bologna": {
                tagline: "The oldest university in continuous operation in the Western world.",
                accept: "25%",
                fees: "$2,800",
                site: "https://www.unibo.it/en"
            },
            "University of Padua": {
                tagline: "A historic Italian university where Galileo once taught.",
                accept: "30%",
                fees: "$2,700",
                site: "https://www.unipd.it/en/"
            },
            "University of Pisa": {
                tagline: "One of Italy's most prestigious universities, home to the SNS.",
                accept: "35%",
                fees: "$2,500",
                site: "https://www.unipi.it/index.php/english"
            },
            "University of Florence": {
                tagline: "A major public research university in the historic city of Florence.",
                accept: "40%",
                fees: "$2,600",
                site: "https://www.unifi.it/changelang-eng.html"
            },
            "University of Naples Federico II": {
                tagline: "The oldest state-supported university in the world.",
                accept: "45%",
                fees: "$2,200",
                site: "http://www.unina.it/en_GB/home"
            },
            "University of Strasbourg": {
                tagline: "A French powerhouse for research, especially in chemistry.",
                accept: "25%",
                fees: "$3,100",
                site: "https://en.unistra.fr"
            },
            "Aix-Marseille University": {
                tagline: "The largest university in the French-speaking world by enrollment.",
                accept: "30%",
                fees: "$3,100",
                site: "https://www.univ-amu.fr/en"
            },
            "University of Basel": {
                tagline: "The oldest university in Switzerland, world-class in life sciences.",
                accept: "25%",
                fees: "$1,800",
                site: "https://www.unibas.ch/en.html"
            },
            "Graz University of Technology": {
                tagline: "A leading technical university in Austria's second largest city.",
                accept: "35%",
                fees: "$1,600",
                site: "https://www.tugraz.at/en/home"
            },
            "University of Valencia": {
                tagline: "A historic public university in Spain, leader in student exchange.",
                accept: "45%",
                fees: "$2,900",
                site: "https://www.uv.es/uvweb/college/en/university-valencia-1285845048380.html"
            },
            "Hokkaido University": {
                tagline: "One of Japan's top Imperial universities, beautiful campus in Sapporo.",
                accept: "15%",
                fees: "$4,500",
                site: "https://www.global.hokudai.ac.jp"
            },
            "Tohoku University": {
                tagline: "Frequently ranked #1 in Japan for its 'open doors' policy.",
                accept: "12%",
                fees: "$4,500",
                site: "https://www.tohoku.ac.jp/en/"
            },
            "Nagoya University": {
                tagline: "A top Japanese research university with numerous Nobel laureates.",
                accept: "14%",
                fees: "$4,500",
                site: "https://en.nagoya-u.ac.jp"
            },
            "USTC": {
                tagline: "University of Science and Technology of China, leader in quantum physics.",
                accept: "5%",
                fees: "$4,200",
                site: "https://en.ustc.edu.cn"
            },
            "POSTECH": {
                tagline: "A world-class research-oriented university in South Korea.",
                accept: "10%",
                fees: "$6,500",
                site: "https://www.postech.ac.th"
            },
            "Jawaharlal Nehru University": {
                tagline: "India's premier social science and humanities research hub (JNU).",
                accept: "1%",
                fees: "$50",
                site: "https://www.jnu.ac.in"
            },
            "University of Hyderabad": {
                tagline: "A top-ranked Indian research university in the city of pearls.",
                accept: "5%",
                fees: "$350",
                site: "https://uohyd.ac.in"
            },
            "University of Manchester": {
                tagline: "A prestigious Red Brick university known for innovation and research.",
                accept: "27%",
                fees: "$35,000",
                site: "https://www.manchester.ac.uk"
            },
            "University of Edinburgh": {
                tagline: "One of the world's oldest and most distinguished universities.",
                accept: "10%",
                fees: "$33,500",
                site: "https://www.ed.ac.uk"
            },
            "King’s College London": {
                tagline: "A historic university in the heart of London, leader in healthcare and law.",
                accept: "13%",
                fees: "$36,000",
                site: "https://www.kcl.ac.uk"
            },
            "London School of Economics": {
                tagline: "A world-leading social science institution specializing in economics and politics.",
                accept: "9%",
                fees: "$32,000",
                site: "https://www.lse.ac.uk"
            },
            "University of Bristol": {
                tagline: "A research-intensive university in one of the UK’s most vibrant cities.",
                accept: "18%",
                fees: "$31,000",
                site: "https://www.bristol.ac.uk"
            },
            "University of Warwick": {
                tagline: "Consistently ranked among the UK's top 10 for academic excellence.",
                accept: "14%",
                fees: "$30,500",
                site: "https://warwick.ac.uk"
            },
            "University of Glasgow": {
                tagline: "The fourth-oldest university in the English-speaking world.",
                accept: "16%",
                fees: "$32,000",
                site: "https://www.gla.ac.uk"
            },
            "University of Birmingham": {
                tagline: "A founding member of the Russell Group with a beautiful red-brick campus.",
                accept: "13%",
                fees: "$29,000",
                site: "https://www.birmingham.ac.uk"
            },
            "University of Nottingham": {
                tagline: "Renowned for its global reach and sustainable 'green' campuses.",
                accept: "15%",
                fees: "$28,500",
                site: "https://www.nottingham.ac.uk"
            },
            "New York University": {
                tagline: "An elite private university integrated into the heart of NYC.",
                accept: "8%",
                fees: "$60,500",
                site: "https://www.nyu.edu"
            },
            "UC San Diego": {
                tagline: "A powerhouse in STEM and oceanography on the California coast.",
                accept: "24%",
                fees: "$46,000",
                site: "https://ucsd.edu"
            },
            "UC Davis": {
                tagline: "Global leader in agriculture, veterinary medicine, and sustainability.",
                accept: "37%",
                fees: "$45,500",
                site: "https://www.ucdavis.edu"
            },
            "University of Illinois Urbana-Champaign": {
                tagline: "Home to one of the world's most elite engineering and computer science programs.",
                accept: "44%",
                fees: "$39,500",
                site: "https://illinois.edu"
            },
            "University of Wisconsin–Madison": {
                tagline: "A top-tier public research university known for academic rigour.",
                accept: "43%",
                fees: "$40,000",
                site: "https://www.wisc.edu"
            },
            "Purdue University": {
                tagline: "Known as the 'Cradle of Astronauts' and a titan in engineering.",
                accept: "52%",
                fees: "$31,000",
                site: "https://www.purdue.edu"
            },
            "Ohio State University": {
                tagline: "One of the largest and most comprehensive public universities in the USA.",
                accept: "53%",
                fees: "$38,000",
                site: "https://www.osu.edu"
            },
            "Arizona State University": {
                tagline: "Ranked #1 in the US for innovation for several consecutive years.",
                accept: "89%",
                fees: "$33,000",
                site: "https://www.asu.edu"
            },
            "University of Bern": {
                tagline: "A comprehensive university in the Swiss capital offering world-class research.",
                accept: "20%",
                fees: "$2,200",
                site: "https://www.unibe.ch/eng"
            },
            "University of Geneva": {
                tagline: "A hub for international relations and scientific discovery in Geneva.",
                accept: "21%",
                fees: "$1,100",
                site: "https://www.unige.ch/en"
            },
            "University of Lausanne": {
                tagline: "Focused on life sciences, business, and environmental studies.",
                accept: "25%",
                fees: "$1,300",
                site: "https://www.unil.ch/central/en/home.html"
            },
            "University of Fribourg": {
                tagline: "Switzerland's only bilingual university (French/German).",
                accept: "30%",
                fees: "$1,800",
                site: "https://www.unifr.ch/home/en/"
            },
            "Università della Svizzera italiana": {
                tagline: "A young, dynamic university in Lugano specializing in informatics and communication.",
                accept: "35%",
                fees: "$4,500",
                site: "https://www.usi.ch/en"
            },
            "Lomonosov Moscow State University": {
                tagline: "The premier institution of higher learning in Russia.",
                accept: "12%",
                fees: "$6,500",
                site: "https://www.msu.ru/en/"
            },
            "Saint Petersburg State University": {
                tagline: "The oldest university in Russia, rich in history and culture.",
                accept: "15%",
                fees: "$5,200",
                site: "https://english.spbu.ru"
            },
            "Novosibirsk State University": {
                tagline: "Located in Russia's top scientific hub, focused on hard sciences.",
                accept: "20%",
                fees: "$4,500",
                site: "https://www.nsu.ru/n/"
            },
            "Tomsk State University": {
                tagline: "A leader in research and innovation in the heart of Siberia.",
                accept: "25%",
                fees: "$3,800",
                site: "https://www.tsu.ru/english/"
            },
            "National Autonomous University of Mexico (UNAM)": {
                tagline: "The largest university in Latin America and a cultural treasure of Mexico.",
                accept: "10%",
                fees: "$500",
                site: "https://www.unam.mx"
            },
            "Tecnológico de Monterrey": {
                tagline: "A top private university in Mexico, famous for its entrepreneurship focus.",
                accept: "25%",
                fees: "$14,000",
                site: "https://tec.mx/en"
            },
            "Universidad Panamericana": {
                tagline: "A private Catholic university in Mexico known for business and ethics.",
                accept: "40%",
                fees: "$11,500",
                site: "https://www.up.edu.mx/en"
            },
            "National University of La Plata": {
                tagline: "One of Argentina's most important public universities, renowned for research.",
                accept: "40%",
                fees: "Free",
                site: "https://unlp.edu.ar"
            },
            "Universidad Austral": {
                tagline: "A top-ranked private university in Argentina with a focus on business and law.",
                accept: "35%",
                fees: "$8,500",
                site: "https://www.austral.edu.ar/en/"
            },
            "University of São Paulo": {
                tagline: "The most prestigious research institution in Brazil and Latin America.",
                accept: "10%",
                fees: "Free",
                site: "https://www5.usp.br"
            },
            "University of Campinas (UNICAMP)": {
                tagline: "A leading public research university in Brazil, known for science and medicine.",
                accept: "12%",
                fees: "Free",
                site: "https://www.unicamp.br/unicamp/"
            },
            "Federal University of Rio de Janeiro": {
                tagline: "The largest federal university in Brazil with a massive research output.",
                accept: "15%",
                fees: "Free",
                site: "https://ufrj.br"
            },
            "São Paulo State University (UNESP)": {
                tagline: "A major Brazilian public university system with campuses across the state.",
                accept: "18%",
                fees: "Free",
                site: "https://www.unesp.br"
            },
            "University of Porto": {
                tagline: "The top research university in Portugal with a strong international outlook.",
                accept: "20%",
                fees: "$3,800",
                site: "https://sigarra.up.pt/up/en/"
            },
            "University of Coimbra": {
                tagline: "One of the world's oldest universities and a UNESCO World Heritage site.",
                accept: "25%",
                fees: "$4,200",
                site: "https://www.uc.pt/en"
            },
            "Hamad Bin Khalifa University": {
                tagline: "A research-intensive university in Qatar's Education City.",
                accept: "15%",
                fees: "$20,000",
                site: "https://www.hbku.edu.qa/en"
            },
            "Weill Cornell Medicine – Qatar": {
                tagline: "An elite medical branch of Cornell University (USA) based in Doha.",
                accept: "5%",
                fees: "$65,000",
                site: "https://qatar-weill.cornell.edu"
            },
            "Cairo University": {
                tagline: "Egypt's premier public university, a historic center for higher education.",
                accept: "25%",
                fees: "$3,500",
                site: "https://cu.edu.eg/Home"
            },
            "Ain Shams University": {
                tagline: "A massive public institution in Cairo known for engineering and medicine.",
                accept: "35%",
                fees: "$3,000",
                site: "https://www.asu.edu.eg"
            },
            "University of Cape Town": {
                tagline: "The highest-ranked university in Africa, set against Table Mountain.",
                accept: "15%",
                fees: "$5,500",
                site: "https://www.uct.ac.za"
            },
            "University of the Witwatersrand": {
                tagline: "A leading research university in Johannesburg, heart of South African industry.",
                accept: "20%",
                fees: "$4,800",
                site: "https://www.wits.ac.za"
            },
            "Stellenbosch University": {
                tagline: "Renowned for its research excellence and beautiful campus in the wine lands.",
                accept: "22%",
                fees: "$5,200",
                site: "https://www.sun.ac.za"
            },
            "University of Ibadan": {
                tagline: "The oldest and most prestigious degree-awarding institution in Nigeria.",
                accept: "12%",
                fees: "$800",
                site: "https://www.ui.edu.ng"
            },
            "Covenant University": {
                tagline: "A leading private Christian university in Nigeria with high employability.",
                accept: "15%",
                fees: "$3,500",
                site: "https://covenantuniversity.edu.ng"
            },
            "Mohammed V University": {
                tagline: "The first modern university in Morocco, a leader in the Maghreb.",
                accept: "40%",
                fees: "$500",
                site: "http://www.um5.ac.ma"
            },
            "ETH Zurich": {
                tagline: "World leader in science and technology; Einstein's alma mater.",
                accept: "27%",
                fees: "$1,600",
                site: "https://ethz.ch/en.html"
            },
            "Korea University": {
                tagline: "One of South Korea's prestigious SKY universities, leader in law and business.",
                accept: "8%",
                fees: "$8,500",
                site: "https://www.korea.edu"
            },
            "Sungkyunkwan University (SKKU)": {
                tagline: "A historic university with deep roots and a strong Samsung partnership.",
                accept: "10%",
                fees: "$9,000",
                site: "https://www.skku.edu/eng/"
            },
            "Hanyang University": {
                tagline: "Known as the 'Engine of Korea' for its elite engineering programs.",
                accept: "12%",
                fees: "$8,800",
                site: "https://www.hanyang.ac.kr/web/eng"
            },
            "Kyung Hee University": {
                tagline: "Famous for its beautiful campus and top-tier oriental medicine programs.",
                accept: "15%",
                fees: "$8,200",
                site: "https://www.khu.ac.kr/eng/main/index.do"
            },
            "Ewha Womans University": {
                tagline: "The world's largest female educational institute, based in Seoul.",
                accept: "13%",
                fees: "$8,500",
                site: "https://www.ewha.ac.kr/ewhaen/index.do"
            },
            "Sogang University": {
                tagline: "A premier Jesuit university in Seoul, elite in liberal arts and business.",
                accept: "10%",
                fees: "$8,400",
                site: "https://wwwn.sogang.ac.kr/wwwn/index_main.html"
            },
            "Inha University": {
                tagline: "A major research university in Incheon, excellent in engineering and logistics.",
                accept: "20%",
                fees: "$7,500",
                site: "https://www.inha.ac.kr/eng/index.do"
            },
            "Pusan National University": {
                tagline: "The top-ranked regional national university in South Korea.",
                accept: "22%",
                fees: "$4,500",
                site: "https://www.pusan.ac.kr/eng/Main.do"
            },
            "Chonnam National University": {
                tagline: "A key national research university in the Gwangju region.",
                accept: "25%",
                fees: "$4,200",
                site: "https://www.jnu.ac.kr/en/"
            },
            "Konkuk University": {
                tagline: "Known for its strong vet-med, animal science, and real estate programs.",
                accept: "18%",
                fees: "$8,000",
                site: "https://www.konkuk.ac.kr/en/"
            },
            "Dongguk University": {
                tagline: "A prestigious Buddhist-affiliated university with a focus on arts and humanities.",
                accept: "15%",
                fees: "$7,800",
                site: "https://www.dongguk.edu/mbs/en/index.jsp"
            },
            "Technical University of Munich": {
                tagline: "Europe's leading technical university with massive industry links.",
                accept: "8%",
                fees: "$6,500",
                site: "https://www.tum.de/en/"
            },
            "National University of Singapore": {
                tagline: "A world-class global university, consistently ranked #1 in Asia.",
                accept: "5%",
                fees: "$22,000",
                site: "https://www.nus.edu.sg"
            },
            "Peking University": {
                tagline: "The premier research university in China, legendary for humanities and science.",
                accept: "1%",
                fees: "$4,500",
                site: "https://english.pku.edu.cn"
            },
            "University of Melbourne": {
                tagline: "Australia's leading research university, based in the cultural capital.",
                accept: "70%*", // Based on meeting prerequisites, though highly competitive for int'l
                fees: "$32,000",
                site: "https://www.unimelb.edu.au"
            },
            "University of Hong Kong": {
                tagline: "A global hub of excellence and the oldest higher education institution in HK.",
                accept: "10%",
                fees: "$23,000",
                site: "https://www.hku.hk"
            },
            "King Fahd University": {
                tagline: "The elite engineering and petroleum research university of Saudi Arabia.",
                accept: "10%",
                fees: "Free",
                site: "https://www.kfupm.edu.sa"
            },
            "Universidad de Buenos Aires": {
                tagline: "A massive public university producing numerous Nobel laureates.",
                accept: "Open Enrollment*",
                fees: "Free",
                site: "https://www.uba.ar"
            },
            "University of Amsterdam": {
                tagline: "A world-class research university in the heart of Europe.",
                accept: "15%",
                fees: "$16,500",
                site: "https://www.uva.nl/en"
            },
            "The University of Tokyo": {
                tagline: "Japan's most prestigious university and a global research leader.",
                accept: "10%",
                fees: "$4,500",
                site: "https://www.u-tokyo.ac.jp/en/"
            },
            "Kyoto University": {
                tagline: "Japan's leader in scientific research and Nobel Prize wins.",
                accept: "12%",
                fees: "$4,500",
                site: "https://www.kyoto-u.ac.jp/en"
            },
            "Osaka University": {
                tagline: "Renowned for its medical and engineering research in Japan's second city.",
                accept: "15%",
                fees: "$4,500",
                site: "https://www.osaka-u.ac.jp/en"
            },
            "Seoul National University": {
                tagline: "The undisputed leader of higher education in South Korea.",
                accept: "15%",
                fees: "$6,500",
                site: "https://www.snu.ac.kr/en"
            },
            "KAIST": {
                tagline: "South Korea's top science and technology research institute.",
                accept: "10%",
                fees: "$7,000",
                site: "https://www.kaist.ac.kr/en/"
            },
            "Yonsei University": {
                tagline: "A member of the SKY universities, famous for business and medicine.",
                accept: "10%",
                fees: "$9,500",
                site: "https://www.yonsei.ac.kr/en_sc/index.jsp"
            },
            "Universiti Malaya": {
                tagline: "The premier and oldest public research university in Malaysia.",
                accept: "15%",
                fees: "$5,500",
                site: "https://www.um.edu.my"
            },
            "Universiti Putra Malaysia": {
                tagline: "A world leader in agricultural science and environmental research.",
                accept: "25%",
                fees: "$4,500",
                site: "https://upm.edu.my"
            },
            "Khalifa University": {
                tagline: "The top-ranked science and research university in the UAE.",
                accept: "15%",
                fees: "$25,000",
                site: "https://www.ku.ac.ae"
            },
            "Politecnico di Milano": {
                tagline: "Italy's largest and most famous technical university.",
                accept: "20%",
                fees: "$3,800",
                site: "https://www.polimi.it/en"
            },
            "Sapienza University of Rome": {
                tagline: "One of the world's oldest and largest universities, top in Classics.",
                accept: "35%",
                fees: "$2,800",
                site: "https://www.uniroma1.it/en/pagina-fissa/sapienza-university-rome"
            },
            "LMU Munich": {
                tagline: "A elite German research university with a long history of Nobel prizes.",
                accept: "15%",
                fees: "$500",
                site: "https://www.lmu.de/en/"
            },
            "Heidelberg University": {
                tagline: "Germany's oldest university and a global hub for medicine and science.",
                accept: "12%",
                fees: "$3,200",
                site: "https://www.uni-heidelberg.de/en"
            },
            "EPFL": {
                tagline: "The sister institute to ETH Zurich, a global leader in engineering.",
                accept: "20%",
                fees: "$1,600",
                site: "https://www.epfl.ch/en/"
            },
            "University of Zurich": {
                tagline: "The largest university in Switzerland, world-class in finance and medicine.",
                accept: "22%",
                fees: "$1,600",
                site: "https://www.uzh.ch/en.html"
            },
            "University of Oslo": {
                tagline: "Norway's leading research university, known for its high quality of life.",
                accept: "15%",
                fees: "Free",
                site: "https://www.uio.no/english/"
            },
            "University of Luxembourg": {
                tagline: "A young, multilingual, and highly international university.",
                accept: "30%",
                fees: "$800",
                site: "https://wwwen.uni.lu"
            },
            "TU Delft": {
                tagline: "The oldest and largest Dutch public technical university.",
                accept: "15%",
                fees: "$20,000",
                site: "https://www.tudelft.nl/en/"
            }


        };


     
let searchTimeout; 

function filterUnis() {
    let input = document.getElementById('uniSearch').value.toLowerCase().trim();
    let cards = document.getElementsByClassName('uni-card');
    
   
    for (let i = 0; i < cards.length; i++) {
        let info = cards[i].getAttribute('data-info').toLowerCase();
        cards[i].style.display = info.includes(input) ? "block" : "none";
    }

   
    clearTimeout(searchTimeout);
    if (input.length > 2) { 
        searchTimeout = setTimeout(() => {
         
            if (currentUserEmail && currentUserEmail !== "") {
                console.log("Sending search tracking request for:", input);

               
                fetch('index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'track-search', 
                        email: currentUserEmail, 
                        searchedCountry: input 
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        console.log("Success: Search history tracked in database.");
                    } else {
                        console.error("Database rejected insertion:", data.error || data.message);
                    }
                })
                .catch(err => {
                    console.error("Tracking Fetch Error:", err);
                });
            } else {
                console.warn("Tracking skipped: No logged-in user session found.");
            }
        }, 1500); 
    }
}
        // 4. THE PRO CLICK LOGIC (Fixes the "Varies" issue)
        document.getElementById('universityList').onclick = function (event) {
            const card = event.target.closest('.uni-card');
            if (!card) return;

            // Get info from the HTML card
            const name = card.querySelector('h3').innerText.trim();
            const rank = card.querySelector('.rank').innerText;
            const region = card.querySelector('.region-tag').innerText;

            // Try to find this university in our uniData library
            const extra = uniData[name];

            // Fill the Modal slots
            document.getElementById('modalTitle').innerText = name;
            document.getElementById('modalRegion').innerText = region;
            document.getElementById('statRank').innerText = rank;

            if (extra) {
                // IF FOUND: Use the real data from uniData
                document.getElementById('statAccept').innerText = extra.accept;
                document.getElementById('statFees').innerText = extra.fees;
                document.getElementById('modalDesc').innerText = extra.tagline;
                document.getElementById('siteLink').href = extra.site;
            } else {
                // IF NOT FOUND: Use these default placeholders
                document.getElementById('statAccept').innerText = "Varies";
                document.getElementById('statFees').innerText = "Contact Uni";
                document.getElementById('modalDesc').innerText = "Detailed profile for " + name + " is being updated.";
                document.getElementById('siteLink').href = "https://www.google.com/search?q=" + name;
            }

            // Show the Modal
            document.getElementById('uniModal').style.display = 'flex';
        };
// 5. LOGIN & DASHBOARD SYSTEM (Safeguarded against null elements)
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const emailInput = document.getElementById('email').value.trim();
                const passInput = document.getElementById('password').value;

                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'login',
                        email: emailInput,
                        password: passInput
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const userObj = {
                            name: data.user.fullname,
                            email: data.user.email,
                            country: data.user.country
                        };
                        localStorage.setItem('currentUser', JSON.stringify(userObj));
                        enterDashboard(userObj);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => console.error("Login Error:", err));
            });
        }

        // 6. UNIVERSITY LIST CLICK SYSTEM (Safeguarded against null elements)
        const uniList = document.getElementById('universityList');
        if (uniList) {
            uniList.onclick = function (event) {
                const card = event.target.closest('.uni-card');
                if (!card) return;

                const name = card.querySelector('h3').innerText.trim();
                const rank = card.querySelector('.rank').innerText;
                const region = card.querySelector('.region-tag').innerText;
                const extra = uniData[name];

                document.getElementById('modalTitle').innerText = name;
                document.getElementById('modalRegion').innerText = region;
                document.getElementById('statRank').innerText = rank;

                if (extra) {
                    document.getElementById('statAccept').innerText = extra.accept;
                    document.getElementById('statFees').innerText = extra.fees;
                    document.getElementById('modalDesc').innerText = extra.tagline;
                    document.getElementById('siteLink').href = extra.site;
                } else {
                    document.getElementById('statAccept').innerText = "Varies";
                    document.getElementById('statFees').innerText = "Contact Uni";
                    document.getElementById('modalDesc').innerText = "Detailed profile for " + name + " is being updated.";
                    document.getElementById('siteLink').href = "https://www.google.com/search?q=" + name;
                }

                document.getElementById('uniModal').style.display = 'flex';
            };
        }

        function enterDashboard(user) {
            
            const welcomeUser = document.getElementById('welcomeUser');
            const targetText = document.getElementById('targetText');
            const loginView = document.getElementById('loginView');
            const dashboardView = document.getElementById('dashboardView');

            document.body.classList.add('dashboard-bg');
            if (loginView) loginView.style.display = 'none';
            if (dashboardView) dashboardView.style.display = 'grid';
            
            if (welcomeUser) welcomeUser.textContent = "Welcome, " + user.name;
            if (targetText) targetText.textContent = user.country || "Not Set";
        }

        function changeTarget() {
            const val = prompt("Enter your new Target Country:");
            if (val) {
                let user = JSON.parse(localStorage.getItem('currentUser'));
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update-target', email: user.email, targetCountry: val })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        user.country = val; 
                        localStorage.setItem('currentUser', JSON.stringify(user));
                        const targetText = document.getElementById('targetText');
                        if (targetText) targetText.textContent = val;
                        alert(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => console.error("Error:", err));
            }
        }

        function logout() {
            localStorage.removeItem('currentUser');
            location.reload();
        }

        function closeModal() {
            const uniModal = document.getElementById('uniModal');
            if (uniModal) uniModal.style.display = 'none';
        }

        window.onload = () => {
            const active = JSON.parse(localStorage.getItem('currentUser'));
            if (active) enterDashboard(active);
        };
    </script>
</body>
</html>
