<?php
// edit_entry.php for child_immunization
$db = new SQLite3('../gender_dev_profiling.db');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE child_immunization SET 
        name=?, age=?, birthdate=?, mother_name=?, bcg=?, hepa_b=?, penta_1=?, penta_2=?, penta_3=?,
        opv_1=?, opv_2=?, opv_3=?, pcv_1=?, pcv_2=?, pcv_3=?, ipv=?, mcv_1=?, mcv_2=?
        WHERE id=?");
    
    $stmt->bindValue(1, $_POST['name'], SQLITE3_TEXT);
    $stmt->bindValue(2, $_POST['age'], SQLITE3_INTEGER);
    $stmt->bindValue(3, $_POST['birthdate'], SQLITE3_TEXT);
    $stmt->bindValue(4, $_POST['mother_name'], SQLITE3_TEXT);
    $stmt->bindValue(5, $_POST['bcg'], SQLITE3_TEXT);
    $stmt->bindValue(6, $_POST['hepa_b'], SQLITE3_TEXT);
    $stmt->bindValue(7, $_POST['penta_1'], SQLITE3_TEXT);
    $stmt->bindValue(8, $_POST['penta_2'], SQLITE3_TEXT);
    $stmt->bindValue(9, $_POST['penta_3'], SQLITE3_TEXT);
    $stmt->bindValue(10, $_POST['opv_1'], SQLITE3_TEXT);
    $stmt->bindValue(11, $_POST['opv_2'], SQLITE3_TEXT);
    $stmt->bindValue(12, $_POST['opv_3'], SQLITE3_TEXT);
    $stmt->bindValue(13, $_POST['pcv_1'], SQLITE3_TEXT);
    $stmt->bindValue(14, $_POST['pcv_2'], SQLITE3_TEXT);
    $stmt->bindValue(15, $_POST['pcv_3'], SQLITE3_TEXT);
    $stmt->bindValue(16, $_POST['ipv'], SQLITE3_TEXT);
    $stmt->bindValue(17, $_POST['mcv_1'], SQLITE3_TEXT);
    $stmt->bindValue(18, $_POST['mcv_2'], SQLITE3_TEXT);
    $stmt->bindValue(19, $_POST['id'], SQLITE3_INTEGER);
    
    $stmt->execute();
    
    header('Location: child_immunization.php');
    exit;
}

// Get existing entry data
$stmt = $db->prepare("SELECT * FROM child_immunization WHERE id = ?");
$stmt->bindValue(1, $_GET['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$entry = $result->fetchArray(SQLITE3_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../assets/html/disable-caching.html'; ?>
    <title>Edit Entry - Child Immunization Form</title>
    <?php include '../assets/html/icon.html'; ?>
    <?php include '../assets/html/styling.html'; ?>
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

        .immunization-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .immunization-group {
            background: var(--accent-color);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .immunization-group:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .immunization-group h3 {
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
            .immunization-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../assets/html/header.html'; ?>

    <div class="container">
        <h1>Edit Entry - Child Immunization</h1>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
            
            <div class="form-group">
                <label for="name">Name of Child:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($entry['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($entry['age']); ?>" required>
            </div>
            <div class="form-group">
                <label for="birthdate">Birthdate:</label>
                <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($entry['birthdate']); ?>" required>
            </div>
            <div class="form-group">
                <label for="mother_name">Name of Mother:</label>
                <input type="text" id="mother_name" name="mother_name" value="<?php echo htmlspecialchars($entry['mother_name']); ?>" required>
            </div>
            
            <div class="immunization-grid">
                <div class="immunization-group">
                    <h3>Basic Immunizations</h3>
                    <div class="form-group">
                        <label for="bcg">BCG:</label>
                        <input type="date" id="bcg" name="bcg" value="<?php echo htmlspecialchars($entry['bcg']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="hepa_b">Hepatitis B:</label>
                        <input type="date" id="hepa_b" name="hepa_b" value="<?php echo htmlspecialchars($entry['hepa_b']); ?>">
                    </div>
                </div>
                <div class="immunization-group">
                    <h3>Pentavalent Vaccine</h3>
                    <div class="form-group">
                        <label for="penta_1">Penta 1:</label>
                        <input type="date" id="penta_1" name="penta_1" value="<?php echo htmlspecialchars($entry['penta_1']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="penta_2">Penta 2:</label>
                        <input type="date" id="penta_2" name="penta_2" value="<?php echo htmlspecialchars($entry['penta_2']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="penta_3">Penta 3:</label>
                        <input type="date" id="penta_3" name="penta_3" value="<?php echo htmlspecialchars($entry['penta_3']); ?>">
                    </div>
                </div>
                <div class="immunization-group">
                    <h3>Oral Polio Vaccine</h3>
                    <div class="form-group">
                        <label for="opv_1">OPV 1:</label>
                        <input type="date" id="opv_1" name="opv_1" value="<?php echo htmlspecialchars($entry['opv_1']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="opv_2">OPV 2:</label>
                        <input type="date" id="opv_2" name="opv_2" value="<?php echo htmlspecialchars($entry['opv_2']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="opv_3">OPV 3:</label>
                        <input type="date" id="opv_3" name="opv_3" value="<?php echo htmlspecialchars($entry['opv_3']); ?>">
                    </div>
                </div>
                <div class="immunization-group">
                    <h3>Pneumococcal Conjugate Vaccine</h3>
                    <div class="form-group">
                        <label for="pcv_1">PCV 1:</label>
                        <input type="date" id="pcv_1" name="pcv_1" value="<?php echo htmlspecialchars($entry['pcv_1']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="pcv_2">PCV 2:</label>
                        <input type="date" id="pcv_2" name="pcv_2" value="<?php echo htmlspecialchars($entry['pcv_2']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="pcv_3">PCV 3:</label>
                        <input type="date" id="pcv_3" name="pcv_3" value="<?php echo htmlspecialchars($entry['pcv_3']); ?>">
                    </div>
                </div>
                <div class="immunization-group">
                    <h3>Other Vaccines</h3>
                    <div class="form-group">
                        <label for="ipv">IPV:</label>
                        <input type="date" id="ipv" name="ipv" value="<?php echo htmlspecialchars($entry['ipv']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="mcv_1">MCV 1:</label>
                        <input type="date" id="mcv_1" name="mcv_1" value="<?php echo htmlspecialchars($entry['mcv_1']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="mcv_2">MCV 2:</label>
                        <input type="date" id="mcv_2" name="mcv_2" value="<?php echo htmlspecialchars($entry['mcv_2']); ?>">
                    </div>
                </div>
            </div>

            <div class="button-container">
                <a href="child_immunization.php" class="button"><i class="fas fa-times"></i> Cancel</a>
                <button type="submit" class="button"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>

    <?php include '../assets/html/footer.html'; ?>
</body>
</html>