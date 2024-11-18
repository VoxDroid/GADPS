<?php
// add_entry.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new SQLite3('../gender_dev_profiling.db');
    
    // Prepare prenatal visits array
    $prenatal_visits = [];
    for ($i = 1; $i <= 12; $i++) {
        $prenatal_visits[] = $_POST["visit_$i"] ?? '';
    }

    $stmt = $db->prepare("INSERT INTO barangay_midwifery (name, age, address, lmp, edc, prenatal_visits, date_of_birth, sex, birth_weight, birth_length, place_of_delivery) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bindValue(1, $_POST['name'], SQLITE3_TEXT);
    $stmt->bindValue(2, $_POST['age'], SQLITE3_INTEGER);
    $stmt->bindValue(3, $_POST['address'], SQLITE3_TEXT);
    $stmt->bindValue(4, $_POST['lmp'], SQLITE3_TEXT);
    $stmt->bindValue(5, $_POST['edc'], SQLITE3_TEXT);
    $stmt->bindValue(6, json_encode($prenatal_visits), SQLITE3_TEXT);
    $stmt->bindValue(7, $_POST['date_of_birth'], SQLITE3_TEXT);
    $stmt->bindValue(8, $_POST['sex'], SQLITE3_TEXT);
    $stmt->bindValue(9, $_POST['birth_weight'], SQLITE3_FLOAT);
    $stmt->bindValue(10, $_POST['birth_length'], SQLITE3_FLOAT);
    $stmt->bindValue(11, $_POST['place_of_delivery'], SQLITE3_TEXT);
    
    $stmt->execute();
    $db->close();
    
    header('Location: barangay_midwifery.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Entry - Barangay Midwifery Form</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
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
            max-width: 1000px;
            margin: 2rem auto;
            overflow: hidden;
            position: relative;
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

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-color);
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--accent-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--shadow-color);
        }

        .prenatal-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .trimester {
            background: var(--accent-color);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .trimester:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .trimester h3 {
            margin-top: 0;
            color: var(--primary-color);
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            margin-bottom: 15px;
        }

        .button {
            background-color: var(--primary-color);
            border: none;
            color: white;
            padding: 12px 24px;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            margin: 4px 8px;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .button i {
            margin-right: 8px;
        }

        .button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .button-container {
            text-align: center;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .prenatal-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../assets/html/header.html'; ?>

    <div class="container">
        <h1>Add New Entry - Barangay Midwifery</h1>
        <form method="POST">
            <div class="form-group">
                <label for="name">Name of Pregnant:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" id="age" name="age" required>
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" required>
            </div>
            <div class="form-group">
                <label for="lmp">LMP:</label>
                <input type="date" id="lmp" name="lmp" required>
            </div>
            <div class="form-group">
                <label for="edc">EDC:</label>
                <input type="date" id="edc" name="edc" required>
            </div>
            
            <div class="form-group">
                <label>Prenatal Visits:</label>
                <div class="prenatal-grid">
                    <div class="trimester">
                        <h3>1st Trimester</h3>
                        <?php for($i = 1; $i <= 3; $i++): ?>
                            <div class="form-group">
                                <label for="visit_<?php echo $i; ?>">Visit <?php echo $i; ?>:</label>
                                <input type="date" id="visit_<?php echo $i; ?>" name="visit_<?php echo $i; ?>">
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="trimester">
                        <h3>2nd Trimester</h3>
                        <?php for($i = 4; $i <= 6; $i++): ?>
                            <div class="form-group">
                                <label for="visit_<?php echo $i; ?>">Visit <?php echo $i; ?>:</label>
                                <input type="date" id="visit_<?php echo $i; ?>" name="visit_<?php echo $i; ?>">
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="trimester">
                        <h3>3rd Trimester</h3>
                        <?php for($i = 7; $i <= 12; $i++): ?>
                            <div class="form-group">
                                <label for="visit_<?php echo $i; ?>">Visit <?php echo $i; ?>:</label>
                                <input type="date" id="visit_<?php echo $i; ?>" name="visit_<?php echo $i; ?>">
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="date_of_birth">Date of Birth:</label>
                <input type="date" id="date_of_birth" name="date_of_birth">
            </div>
            <div class="form-group">
                <label for="sex">Sex:</label>
                <select id="sex" name="sex">
                    <option value="not_specified">Not Specified</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="birth_weight">Birth Weight (kg):</label>
                <input type="number" id="birth_weight" name="birth_weight" step="0.01">
            </div>
            <div class="form-group">
                <label for="birth_length">Birth Length (cm):</label>
                <input type="number" id="birth_length" name="birth_length" step="0.1">
            </div>
            <div class="form-group">
                <label for="place_of_delivery">Place of Delivery:</label>
                <select id="place_of_delivery" name="place_of_delivery">
                    <option value="not_specified">Not Specified</option>
                    <option value="hospital">Hospital</option>
                    <option value="rhu">RHU</option>
                    <option value="lying_in">Lying In</option>
                    <option value="home">Home</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="button-container">
                <a href="barangay_midwifery.php" class="button"><i class="fas fa-times"></i> Cancel</a>
                <button type="submit" class="button"><i class="fas fa-save"></i> Save Entry</button>
            </div>
        </form>
    </div>

    <?php include '../assets/html/footer.html'; ?>
</body>
</html>