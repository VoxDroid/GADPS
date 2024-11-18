<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Gender and Development Profiling System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .container {
            flex: 1;
            background: linear-gradient(145deg, #ffffff, #f6f7ff);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            width: 95%;
            max-width: 1200px;
            margin: 2rem auto;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 20px 20px 0 0;
        }

        h1, h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        p {
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .team-section {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .team-member {
            text-align: center;
            width: 150px;
        }

        .team-member img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 0.5rem;
            border: 3px solid var(--primary-color);
        }

        .team-member h3 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .team-member p {
            font-size: 0.9rem;
            color: var(--text-color);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 10px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'assets/html/header_front.html'; ?>

    <div class="container">
        <h1>About the Gender and Development Profiling System</h1>
        <p>This project is an extension project for Laguna State Polytechnic University - Santa Cruz Campus. It serves as a comprehensive system for gender and development profiling, aiming to provide valuable insights and data management for community development initiatives.</p>

        <h2>Project Team</h2>
        <p><strong>Project Head:</strong> Evangelista</p>

        <h3>Programming Team</h3>
        <div class="team-section">
            <div class="team-member" onclick="showProfile('Mhar Andrei')">
                <img src="/placeholder.svg?height=100&width=100" alt="Mhar Andrei">
                <h3>Mhar Andrei</h3>
                <p>Head Developer</p>
            </div>
            <div class="team-member" onclick="showProfile('Javier')">
                <img src="/placeholder.svg?height=100&width=100" alt="Javier">
                <h3>Javier</h3>
                <p>Developer</p>
            </div>
            <div class="team-member" onclick="showProfile('Arat')">
                <img src="/placeholder.svg?height=100&width=100" alt="Arat">
                <h3>Arat</h3>
                <p>Developer</p>
            </div>
        </div>

        <h3>Documentation Team</h3>
        <div class="team-section">
            <?php
            $documentation_team = ['Rebong', 'Bayani', 'Santos', 'Marquez', 'Redera', 'Badillo', 'Siclon', 'Sofer'];
            foreach ($documentation_team as $member) {
                echo "<div class='team-member' onclick=\"showProfile('$member')\">";
                echo "<img src='/placeholder.svg?height=100&width=100' alt='$member'>";
                echo "<h3>$member</h3>";
                echo "<p>Documentation</p>";
                echo "</div>";
            }
            ?>
        </div>

        <h3>Manuscript Team</h3>
        <div class="team-section">
            <?php
            $manuscript_team = ['Anonuevo', 'Mardoquio', 'Ordonez', 'Reyes', 'Calupig'];
            foreach ($manuscript_team as $member) {
                echo "<div class='team-member' onclick=\"showProfile('$member')\">";
                echo "<img src='/placeholder.svg?height=100&width=100' alt='$member'>";
                echo "<h3>$member</h3>";
                echo "<p>Manuscript</p>";
                echo "</div>";
            }
            ?>
        </div>
    </div>

    <div id="profileModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="profileName"></h2>
            <p id="profileDescription"></p>
        </div>
    </div>

    <?php include 'assets/html/footer.html'; ?>

    <script>
        function showProfile(name) {
            const modal = document.getElementById('profileModal');
            const profileName = document.getElementById('profileName');
            const profileDescription = document.getElementById('profileDescription');

            profileName.textContent = name;
            profileDescription.textContent = `This is the profile description for ${name}. Additional details about their role and contributions to the project would be displayed here.`;

            modal.style.display = 'block';
        }

        const modal = document.getElementById('profileModal');
        const span = document.getElementsByClassName('close')[0];

        span.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>