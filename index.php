<?php
// index.php
// Database initialization
$db_file = 'gender_dev_profiling.db';
$db = new SQLite3($db_file);

// Create tables if they don't exist
$db->exec("CREATE TABLE IF NOT EXISTS barangay_midwifery (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    age INTEGER,
    address TEXT,
    lmp DATE,
    edc DATE,
    prenatal_visits TEXT,
    date_of_birth DATE,
    sex TEXT,
    birth_weight REAL,
    birth_length REAL,
    place_of_delivery TEXT,
    item_status TEXT DEFAULT 'active'
)");

// Close the database connection
$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gender and Development Profiling System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6A5ACD;
            --secondary-color: #9370DB;
            --accent-color: #E6E6FA;
            --text-color: #333;
            --shadow-color: rgba(106, 90, 205, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #E6E6FA 0%, #9370DB 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background-color: white;
            padding: 0.75rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo i {
            color: white;
            font-size: 1.2rem;
        }

        .site-title {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 500;
            line-height: 50px;
            margin: 0;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .carousel-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }

        .carousel {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            max-width: 1600px;
            margin: 0 auto;
            position: relative;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            width: 450px;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.5s ease;
            position: absolute;
            opacity: 0;
            transform: translateX(100%) scale(0.8);
        }

        .card.active {
            position: relative;
            opacity: 1;
            transform: translateX(0) scale(1);
            z-index: 2;
        }

        .card.prev {
            opacity: 0.7;
            transform: translateX(-110%) scale(0.8);
            z-index: 1;
        }

        .card.next {
            opacity: 0.7;
            transform: translateX(110%) scale(0.8);
            z-index: 1;
        }

        .card-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            transition: all 0.5s ease;
            align-self: flex-start;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .card-description {
            color: #666;
            line-height: 1.6;
        }

        .access-form-btn {
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
            margin-top: auto;
            position: relative;
            overflow: hidden;
        }

        .access-form-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .card:hover .card-icon {
            transform: translateX(calc(50% - 1.25rem));
            color: var(--secondary-color);
        }

        .carousel-controls {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            display: flex;
            justify-content: space-between;
            padding: 0 1rem;
        }

        .control-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.5rem;
        }

        .control-btn:hover {
            background-color: var(--secondary-color);
        }

        .footer {
            background-color: white;
            padding: 1rem;
            margin-top: auto;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: var(--secondary-color);
        }

        @media (max-width: 1200px) {
            .carousel {
                flex-direction: column;
                gap: 1rem;
            }

            .card {
                width: 90%;
                max-width: 450px;
                margin: 0 auto;
            }

            .card.prev, .card.next {
                display: none;
            }

            .carousel-controls {
                position: static;
                transform: none;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo">
                        <i class="fas fa-venus-mars"></i>
                    </div>
                    <h1 class="site-title">Gender and Development Profiling System</h1>
                </div>
                <nav class="nav-links">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="#"><i class="fas fa-info-circle"></i> About</a>
                    <a href="#"><i class="fas fa-envelope"></i> Contact</a>
                </nav>
            </div>
        </header>

    <main class="carousel-section">
        <div class="carousel">
            <div class="card prev" data-position="prev">
                <i class="fas fa-user-md card-icon"></i>
                <h2 class="card-title">Barangay Midwifery Form</h2>
                <p class="card-description">Manage and track midwifery data for your barangay.</p>
                <a href="BMF/barangay_midwifery.php" class="access-form-btn">Access Form</a>
            </div>
            <div class="card active" data-position="active">
                <i class="fas fa-map-marker-alt card-icon"></i>
                <h2 class="card-title">Purok Selection Form</h2>
                <p class="card-description">Select and manage data for specific puroks in your area.</p>
                <a href="PSF/purok_selection.php" class="access-form-btn">Access Form</a>
            </div>
            <div class="card next" data-position="next">
                <i class="fas fa-baby card-icon"></i>
                <h2 class="card-title">Child Immunization Form</h2>
                <p class="card-description">Track and manage child immunization records efficiently.</p>
                <a href="CIF/child_immunization.php" class="access-form-btn">Access Form</a>
            </div>
        </div>
        <div class="carousel-controls">
            <button class="control-btn prev-btn"><i class="fas fa-chevron-left"></i></button>
            <button class="control-btn next-btn"><i class="fas fa-chevron-right"></i></button>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
            </div>
            <p>&copy; 2024 Gender and Development Profiling System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const carousel = document.querySelector('.carousel');
            const cards = Array.from(document.querySelectorAll('.card'));
            const prevBtn = document.querySelector('.prev-btn');
            const nextBtn = document.querySelector('.next-btn');

            function updateCarousel(direction) {
                const positions = ['prev', 'active', 'next'];
                cards.forEach(card => {
                    const currentPos = card.dataset.position;
                    const currentIndex = positions.indexOf(currentPos);
                    let newIndex;
                    
                    if (direction === 'next') {
                        newIndex = (currentIndex - 1 + positions.length) % positions.length;
                    } else {
                        newIndex = (currentIndex + 1) % positions.length;
                    }
                    
                    card.dataset.position = positions[newIndex];
                    card.className = `card ${positions[newIndex]}`;
                });
            }

            prevBtn.addEventListener('click', () => updateCarousel('prev'));
            nextBtn.addEventListener('click', () => updateCarousel('next'));

            // Add hover animations for buttons and links
            const buttons = document.querySelectorAll('.access-form-btn');
            buttons.forEach(button => {
                button.addEventListener('mouseenter', () => {
                    const icon = button.parentElement.querySelector('.card-icon');
                    icon.style.transform = 'translateX(calc(50% - 1.25rem))';
                });
                
                button.addEventListener('mouseleave', () => {
                    const icon = button.parentElement.querySelector('.card-icon');
                    icon.style.transform = '';
                });
            });
        });
    </script>
</body>
</html>