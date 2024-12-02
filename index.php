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

$db->exec("CREATE TABLE IF NOT EXISTS child_immunization (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    age INTEGER,
    birthdate DATE,
    mother_name TEXT,
    bcg DATE,
    hepa_b DATE,
    penta_1 DATE,
    penta_2 DATE,
    penta_3 DATE,
    opv_1 DATE,
    opv_2 DATE,
    opv_3 DATE,
    pcv_1 DATE,
    pcv_2 DATE,
    pcv_3 DATE,
    ipv DATE,
    mcv_1 DATE,
    mcv_2 DATE,
    item_status TEXT DEFAULT 'active'
)");

// Fetch statistics
$total_midwifery = $db->querySingle("SELECT COUNT(*) FROM barangay_midwifery WHERE item_status = 'active'");
$total_purok = $db->querySingle("SELECT COUNT(*) FROM purok_selection WHERE item_status = 'active'");
$total_immunization = $db->querySingle("SELECT COUNT(*) FROM child_immunization WHERE item_status = 'active'");

// Fetch data for graphs
$purok_data = $db->query("SELECT purok, COUNT(*) as count FROM purok_selection WHERE item_status = 'active' GROUP BY purok");
$purok_counts = [];
while ($row = $purok_data->fetchArray(SQLITE3_ASSOC)) {
    $purok_counts[$row['purok']] = $row['count'];
}

$immunization_data = $db->query("SELECT 
    SUM(CASE WHEN TRIM(bcg) != '' AND bcg IS NOT NULL THEN 1 ELSE 0 END) as bcg,
    SUM(CASE WHEN TRIM(hepa_b) != '' AND hepa_b IS NOT NULL THEN 1 ELSE 0 END) as hepa_b,
    SUM(CASE WHEN TRIM(penta_1) != '' AND penta_1 IS NOT NULL THEN 1 ELSE 0 END) as penta_1,
    SUM(CASE WHEN TRIM(penta_2) != '' AND penta_2 IS NOT NULL THEN 1 ELSE 0 END) as penta_2,
    SUM(CASE WHEN TRIM(penta_3) != '' AND penta_3 IS NOT NULL THEN 1 ELSE 0 END) as penta_3,
    SUM(CASE WHEN TRIM(opv_1) != '' AND opv_1 IS NOT NULL THEN 1 ELSE 0 END) as opv_1,
    SUM(CASE WHEN TRIM(opv_2) != '' AND opv_2 IS NOT NULL THEN 1 ELSE 0 END) as opv_2,
    SUM(CASE WHEN TRIM(opv_3) != '' AND opv_3 IS NOT NULL THEN 1 ELSE 0 END) as opv_3,
    SUM(CASE WHEN TRIM(pcv_1) != '' AND pcv_1 IS NOT NULL THEN 1 ELSE 0 END) as pcv_1,
    SUM(CASE WHEN TRIM(pcv_2) != '' AND pcv_2 IS NOT NULL THEN 1 ELSE 0 END) as pcv_2,
    SUM(CASE WHEN TRIM(pcv_3) != '' AND pcv_3 IS NOT NULL THEN 1 ELSE 0 END) as pcv_3,
    SUM(CASE WHEN TRIM(ipv) != '' AND ipv IS NOT NULL THEN 1 ELSE 0 END) as ipv,
    SUM(CASE WHEN TRIM(mcv_1) != '' AND mcv_1 IS NOT NULL THEN 1 ELSE 0 END) as mcv_1,
    SUM(CASE WHEN TRIM(mcv_2) != '' AND mcv_2 IS NOT NULL THEN 1 ELSE 0 END) as mcv_2
FROM child_immunization
WHERE item_status = 'active';");

$immunization_counts = $immunization_data->fetchArray(SQLITE3_ASSOC);

// Close the database connection
$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'assets/html/disable-caching.html'; ?>
    <title>Gender and Development Profiling System</title>
    <?php include 'assets/html/icon_front.html'; ?>
    <?php include 'assets/html/styling_front.html'; ?>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
        }

        .dashboard {
            background: linear-gradient(145deg, #ffffff, #f6f7ff);
            border-radius: 20px;
            padding: 2rem;
            margin: 2rem auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            aspect-ratio: 1 / 1;
            display: flex;
            flex-direction: column;
        }

        .dashboard-title {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-title {
            color: var(--secondary-color);
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: bold;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            flex-grow: 1;
        }

        .chart-wrapper {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .chart-title {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 1rem;
            text-align: center;
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
<body class="slideshow-background">
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

    <div class="dashboard">
        <h2 class="dashboard-title">Dashboard Statistics</h2>
        <div class="stats-container">
            <div class="stat-card">
                <h3 class="stat-title">Total Midwifery Entries</h3>
                <p class="stat-value"><?php echo $total_midwifery; ?></p>
            </div>
            <div class="stat-card">
                <h3 class="stat-title">Total Purok Entries</h3>
                <p class="stat-value"><?php echo $total_purok; ?></p>
            </div>
            <div class="stat-card">
                <h3 class="stat-title">Total Immunization Entries</h3>
                <p class="stat-value"><?php echo $total_immunization; ?></p>
            </div>
        </div>
        <div class="charts-container">
            <div class="chart-wrapper">
                <h3 class="chart-title">Purok Distribution</h3>
                <div id="purokChart"></div>
            </div>
            <div class="chart-wrapper">
                <h3 class="chart-title">Immunization Coverage</h3>
                <div id="immunizationChart"></div>
            </div>
        </div>
    </div>

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

            // Purok Distribution Chart
            const purokOptions = {
                series: [{
                    name: 'Number of Entries',
                    data: [
                        <?php echo $purok_counts[1] ?? 0; ?>,
                        <?php echo $purok_counts[2] ?? 0; ?>,
                        <?php echo $purok_counts[3] ?? 0; ?>,
                        <?php echo $purok_counts[4] ?? 0; ?>,
                        <?php echo $purok_counts[5] ?? 0; ?>,
                        <?php echo $purok_counts[6] ?? 0; ?>
                    ]
                }],
                chart: {
                    type: 'bar',
                    height: 375,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: false,
                    }
                },
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: ['Purok 1', 'Purok 2', 'Purok 3', 'Purok 4', 'Purok 5', 'Purok 6'],
                },
                colors: ['#E6B800'],
                title: {
                    text: 'Purok Distribution',
                    align: 'center',
                    style: {
                        fontSize: '18px',
                        fontWeight: 'bold',
                        fontFamily: 'Poppins, sans-serif',
                        color: '#333'
                    }
                }
            };

            const purokChart = new ApexCharts(document.querySelector("#purokChart"), purokOptions);
            purokChart.render();

            // Immunization Coverage Chart
            const immunizationOptions = {
                series: [{
                    name: 'Number of Immunizations',
                    data: [
                        <?php echo $immunization_counts['bcg']; ?>,
                        <?php echo $immunization_counts['hepa_b']; ?>,
                        <?php echo $immunization_counts['penta_1']; ?>,
                        <?php echo $immunization_counts['penta_2']; ?>,
                        <?php echo $immunization_counts['penta_3']; ?>,
                        <?php echo $immunization_counts['opv_1']; ?>,
                        <?php echo $immunization_counts['opv_2']; ?>,
                        <?php echo $immunization_counts['opv_3']; ?>,
                        <?php echo $immunization_counts['pcv_1']; ?>,
                        <?php echo $immunization_counts['pcv_2']; ?>,
                        <?php echo $immunization_counts['pcv_3']; ?>,
                        <?php echo $immunization_counts['ipv']; ?>,
                        <?php echo $immunization_counts['mcv_1']; ?>,
                        <?php echo $immunization_counts['mcv_2']; ?>
                    ]
                }],
                chart: {
                    height: 375,
                    type: 'radar',
                },
                dataLabels: {
                    enabled: true
                },
                plotOptions: {
                    radar: {
                        size: 140,
                        polygons: {
                            strokeColors: '#e9e9e9',
                            fill: {
                                colors: ['#f8f8f8', '#fff']
                            }
                        }
                    }
                },
                title: {
                    text: 'Immunization Coverage',
                    align: 'center',
                    style: {
                        fontSize: '18px',
                        fontWeight: 'bold',
                        fontFamily: 'Poppins, sans-serif',
                        color: '#333'
                    }
                },
                colors: ['#D4A017'],
                markers: {
                    size: 4,
                    colors: ['#fff'],
                    strokeColor: '#D4A017',
                    strokeWidth: 2,
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val
                        }
                    }
                },
                xaxis: {
                    categories: ['BCG', 'Hepa B', 'Penta 1', 'Penta 2', 'Penta 3', 'OPV 1', 'OPV 2', 'OPV 3', 'PCV 1', 'PCV 2', 'PCV 3', 'IPV', 'MCV 1', 'MCV 2']
                },
                yaxis: {
                    tickAmount: 7,
                    labels: {
                        formatter: function(val, i) {
                            if (i % 2 === 0) {
                                return val
                            } else {
                                return ''
                            }
                        }
                    }
                }
            };

            const immunizationChart = new ApexCharts(document.querySelector("#immunizationChart"), immunizationOptions);
            immunizationChart.render();
        });
    </script>
</body>
</html>