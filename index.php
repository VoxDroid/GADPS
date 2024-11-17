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

$db->exec("CREATE TABLE IF NOT EXISTS purok_selection (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    birthday DATE,
    age INTEGER,
    gender TEXT,
    civil_status TEXT,
    occupation TEXT,
    sc BOOLEAN,
    pwd BOOLEAN,
    hypertension BOOLEAN,
    diabetes BOOLEAN,
    f_planning BOOLEAN,
    t_pregnancy BOOLEAN,
    poso BOOLEAN,
    nawasa BOOLEAN,
    mineral BOOLEAN,
    segregation BOOLEAN,
    composition BOOLEAN,
    purok INTEGER,
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
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
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
    <?php include 'assets/html/header_front.html'; ?>

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

    <?php include 'assets/html/footer.html'; ?>

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