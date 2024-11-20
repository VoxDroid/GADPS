<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'assets/html/disable-caching.html'; ?>
    <title>About - Gender and Development Profiling System</title>
    <?php include 'assets/html/icon_front.html'; ?>
    <?php include 'assets/html/styling_front.html'; ?>
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

        h1, h2, h3 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        p {
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .team-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .team-member {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .team-member:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .team-member img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 3px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .team-member:hover img {
            transform: scale(1.05);
        }

        .team-member h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
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
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 2rem;
            border: 1px solid #888;
            width: 90%;
            max-width: 600px;
            border-radius: 15px;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .close:hover,
        .close:focus {
            color: var(--primary-color);
            text-decoration: none;
            cursor: pointer;
        }

        .social-links {
            margin-top: 1rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .social-links a {
            color: var(--primary-color);
            font-size: 1.5rem;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .social-links a:hover {
            color: var(--secondary-color);
            transform: scale(1.2);
        }

        #profileImage {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            display: block;
            border: 3px solid var(--primary-color);
        }

        #profileDescription {
            text-align: center;
            margin-bottom: 1rem;
        }

        #profileRole {
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'assets/html/header_front.html'; ?>

    <div class="container">
        <h1>About the Gender and Development Profiling System</h1>
        <p>This project is an extension project for Laguna State Polytechnic University - Santa Cruz Campus. It serves as a comprehensive system for gender and development profiling, aiming to provide valuable insights and data management for community development initiatives.</p>

        <h2>Our Team</h2>

        <?php
        $team_members = [
            [
                'name' => 'John Kervin Evangelista',
                'role' => 'Project Head',
                'description' => 'Evangelista leads the Gender and Development Profiling System project, bringing years of experience in community development and project management.',
                'image' => 'https://eu.ui-avatars.com/api/?name=J+E&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/evangelista',
                    'twitter' => 'https://twitter.com/evangelista',
                    'email' => 'mailto:evangelista@example.com'
                ]
            ],
            [
                'name' => 'Mhar Andrei Macapallag',
                'role' => 'Head Developer',
                'description' => 'Mhar Andrei leads the development team. He developed the system using PHP and SQLite3.',
                'image' => 'assets/profile-img/Macapallag.png',
                'social' => [
                    'github' => 'https://github.com/VoxDroid',
                    'twitter' => 'https://twitter.com/drei_zx',
                    'instagram' => 'https://instagram.com/andrei_who',
                    'facebook' => 'https://facebook.com/MharAndrei',
                ]
            ],
            [
                'name' => 'Geron Simon Javier',
                'role' => 'Developer',
                'description' => 'Javier is a skilled developer specializing in backend systems and database management.',
                'image' => 'https://eu.ui-avatars.com/api/?name=G+J&size=150',
                'social' => [
                    'github' => 'https://github.com/javier',
                    'linkedin' => 'https://linkedin.com/in/javier',
                    'twitter' => 'https://twitter.com/javier'
                ]
            ],
            [
                'name' => 'Carlo Guerrero Arat',
                'role' => 'Developer',
                'description' => 'Arat focuses on frontend development, creating intuitive and responsive user interfaces.',
                'image' => 'https://eu.ui-avatars.com/api/?name=C+A&size=150',
                'social' => [
                    'github' => 'https://github.com/arat',
                    'dribbble' => 'https://dribbble.com/arat',
                    'linkedin' => 'https://linkedin.com/in/arat'
                ]
            ],
            [
                'name' => 'Dexter Rebong',
                'role' => 'Documentation',
                'description' => 'Rebong is responsible for creating and maintaining comprehensive project documentation.',
                'image' => 'https://eu.ui-avatars.com/api/?name=D+R&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/rebong',
                    'twitter' => 'https://twitter.com/rebong'
                ]
            ],
            [
                'name' => 'Reigniell Ann Larano Bayani',
                'role' => 'Documentation',
                'description' => 'Bayani ensures that all project processes and outcomes are well-documented for future reference.',
                'image' => 'https://eu.ui-avatars.com/api/?name=R+B&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/bayani',
                    'github' => 'https://github.com/bayani'
                ]
            ],
            [
                'name' => 'Gabriel Scott Santos',
                'role' => 'Documentation',
                'description' => 'Santos contributes to the documentation team, focusing on user guides and technical specifications.',
                'image' => 'https://eu.ui-avatars.com/api/?name=G+S&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/santos',
                    'github' => 'https://github.com/santos'
                ]
            ],
            [
                'name' => 'Marquez Jethro',
                'role' => 'Documentation',
                'description' => 'Marquez specializes in creating visual documentation, including diagrams and infographics.',
                'image' => 'https://eu.ui-avatars.com/api/?name=M+J&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/marquez',
                    'behance' => 'https://www.behance.net/marquez'
                ]
            ],
            [
                'name' => 'Angelo Nicolas Arguidas Redera',
                'role' => 'Documentation',
                'description' => 'Redera focuses on quality assurance for all project documentation, ensuring accuracy and clarity.',
                'image' => 'https://eu.ui-avatars.com/api/?name=A+R&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/redera',
                    'twitter' => 'https://twitter.com/redera'
                ]
            ],
            [
                'name' => 'Jerahmeel Badillo',
                'role' => 'Documentation',
                'description' => 'Badillo specializes in creating user-friendly documentation and help resources.',
                'image' => 'https://eu.ui-avatars.com/api/?name=J+B&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/badillo',
                    'medium' => 'https://medium.com/@badillo'
                ]
            ],
            [
                'name' => 'Kendall Siclon',
                'role' => 'Documentation',
                'description' => 'Siclon is responsible for maintaining the project wiki and internal knowledge base.',
                'image' => 'https://eu.ui-avatars.com/api/?name=K+S&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/siclon',
                    'github' => 'https://github.com/siclon'
                ]
            ],
            [
                'name' => 'Jencel Sofer',
                'role' => 'Documentation',
                'description' => 'Sofer specializes in creating video tutorials and interactive documentation.',
                'image' => 'https://eu.ui-avatars.com/api/?name=J+S&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/sofer',
                    'youtube' => 'https://www.youtube.com/user/sofer'
                ]
            ],
            [
                'name' => 'Ed-Michael Anonuevo',
                'role' => 'Manuscript',
                'description' => 'Anonuevo is part of the manuscript team, focusing on creating detailed reports and academic papers.',
                'image' => 'https://eu.ui-avatars.com/api/?name=E+A&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/anonuevo',
                    'researchgate' => 'https://www.researchgate.net/profile/Anonuevo'
                ]
            ],
            [
                'name' => 'Erson D. Mardoquio',
                'role' => 'Manuscript',
                'description' => 'Mardoquio contributes to the manuscript team by analyzing data and writing comprehensive reports.',
                'image' => 'https://eu.ui-avatars.com/api/?name=E+M&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/mardoquio',
                    'orcid' => 'https://orcid.org/0000-0000-0000-0000'
                ]
            ],
            [
                'name' => 'Juan Carlos Ordonez',
                'role' => 'Manuscript',
                'description' => 'Ordonez specializes in statistical analysis and data visualization for the manuscript team.',
                'image' => 'https://eu.ui-avatars.com/api/?name=J+O&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/ordonez',
                    'github' => 'https://github.com/ordonez'
                ]
            ],
            [
                'name' => 'Vanesse Reyes',
                'role' => 'Manuscript',
                'description' => 'Reyes focuses on literature review and theoretical framework development for the project manuscripts.',
                'image' => 'https://eu.ui-avatars.com/api/?name=V+R&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/reyes',
                    'academia' => 'https://independent.academia.edu/Reyes'
                ]
            ],
            [
                'name' => 'Rolex R Calupig',
                'role' => 'Manuscript',
                'description' => 'Calupig is responsible for proofreading and editing the final manuscripts before submission.',
                'image' => 'https://eu.ui-avatars.com/api/?name=R+C&size=150',
                'social' => [
                    'linkedin' => 'https://linkedin.com/in/calupig',
                    'twitter' => 'https://twitter.com/calupig'
                ]
            ]
        ];

        echo "<div class='team-section'>";
        foreach ($team_members as $member) {
            echo "<div class='team-member' onclick=\"showProfile('" . htmlspecialchars(json_encode($member)) . "')\">";
            echo "<img src='" . htmlspecialchars($member['image']) . "' alt='" . htmlspecialchars($member['name']) . "'>";
            echo "<h3>" . htmlspecialchars($member['name']) . "</h3>";
            echo "<p>" . htmlspecialchars($member['role']) . "</p>";
            echo "</div>";
        }
        echo "</div>";
        ?>
    </div>

    <div id="profileModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <img id="profileImage" src="" alt="Team Member">
            <h2 id="profileName"></h2>
            <p id="profileRole"></p>
            <p id="profileDescription"></p>
            <div id="socialLinks" class="social-links"></div>
        </div>
    </div>

    <?php include 'assets/html/footer.html'; ?>

    <script>
        function showProfile(memberJson) {
            const member = JSON.parse(memberJson);
            const modal = document.getElementById('profileModal');
            const profileImage = document.getElementById('profileImage');
            const profileName = document.getElementById('profileName');
            const profileRole = document.getElementById('profileRole');
            const profileDescription = document.getElementById('profileDescription');
            const socialLinks = document.getElementById('socialLinks');

            profileImage.src = member.image;
            profileImage.alt = member.name;
            profileName.textContent = member.name;
            profileRole.textContent = member.role;
            profileDescription.textContent = member.description;

            socialLinks.innerHTML = '';
            for (const [platform, url] of Object.entries(member.social)) {
                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.innerHTML = `<i class="fab fa-${platform}"></i>`;
                socialLinks.appendChild(link);
            }

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