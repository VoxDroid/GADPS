<?php
// edit_entry.php for purok_selection
$db = new SQLite3('../gender_dev_profiling.db');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE purok_selection SET 
        name=?, birthday=?, age=?, gender=?, civil_status=?, occupation=?,
        sc=?, pwd=?, hypertension=?, diabetes=?, f_planning=?, t_pregnancy=?,
        poso=?, nawasa=?, mineral=?, segregation=?, composition=?, purok=?
        WHERE id=?");
    
    $stmt->bindValue(1, $_POST['name'], SQLITE3_TEXT);
    $stmt->bindValue(2, $_POST['birthday'], SQLITE3_TEXT);
    $stmt->bindValue(3, $_POST['age'], SQLITE3_INTEGER);
    $stmt->bindValue(4, $_POST['gender'], SQLITE3_TEXT);
    $stmt->bindValue(5, $_POST['civil_status'], SQLITE3_TEXT);
    $stmt->bindValue(6, $_POST['occupation'], SQLITE3_TEXT);
    $stmt->bindValue(7, isset($_POST['sc']) ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(8, isset($_POST['pwd']) ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(9, isset($_POST['hypertension']) ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(10, isset($_POST['diabetes']) ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(11, isset($_POST['f_planning']) ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(12, isset($_POST['t_pregnancy']) ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(13, isset($_POST['poso']) ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(14, isset($_POST['nawasa']) ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(15, isset($_POST['mineral']) ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(16, isset($_POST['segregation']) ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(17, isset($_POST['composition']) ? 1 : 0, SQLITE3_INTEGER);
    $stmt->bindValue(18, $_POST['purok'], SQLITE3_INTEGER);
    $stmt->bindValue(19, $_POST['id'], SQLITE3_INTEGER);
    
    $stmt->execute();
    
    header('Location: purok_selection.php');
    exit;
}

// Get existing entry data
$stmt = $db->prepare("SELECT * FROM purok_selection WHERE id = ?");
$stmt->bindValue(1, $_GET['id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$entry = $result->fetchArray(SQLITE3_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Entry - Purok Selection Form</title>
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

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .checkbox-item input[type="checkbox"] {
            width: auto;
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
    </style>
</head>
<body>
    <?php include '../assets/html/header.html'; ?>

    <div class="container">
        <h1>Edit Entry</h1>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
            
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($entry['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="birthday">Birthday:</label>
                <input type="date" id="birthday" name="birthday" value="<?php echo htmlspecialchars($entry['birthday']); ?>" required>
            </div>
            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($entry['age']); ?>" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender:</label>
                <select id="gender" name="gender" required>
                    <option value="not_specified" <?php echo $entry['gender'] === 'not_specified' ? 'selected' : ''; ?>>Not Specified</option>
                    <option value="Male" <?php echo $entry['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $entry['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo $entry['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="civil_status">Civil Status:</label>
                <select id="civil_status" name="civil_status" required>
                    <option value="not_specified" <?php echo $entry['civil_status'] === 'not_specified' ? 'selected' : ''; ?>>Not Specified</option>
                    <option value="Single" <?php echo $entry['civil_status'] === 'Single' ? 'selected' : ''; ?>>Single</option>
                    <option value="Married" <?php echo $entry['civil_status'] === 'Married' ? 'selected' : ''; ?>>Married</option>
                    <option value="Widowed" <?php echo $entry['civil_status'] === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                    <option value="Divorced" <?php echo $entry['civil_status'] === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                    <option value="Separated" <?php echo $entry['civil_status'] === 'Separated' ? 'selected' : ''; ?>>Separated</option>
                </select>
            </div>
            <div class="form-group">
                <label for="occupation">Occupation:</label>
                <input type="text" id="occupation" name="occupation" value="<?php echo htmlspecialchars($entry['occupation']); ?>" required>
            </div>
            <div class="form-group">
                <label>Health Condition:</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="sc" name="sc" <?php echo $entry['sc'] ? 'checked' : ''; ?>>
                        <label for="sc">SC</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="pwd" name="pwd" <?php echo $entry['pwd'] ? 'checked' : ''; ?>>
                        <label for="pwd">PWD</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="hypertension" name="hypertension" <?php echo $entry['hypertension'] ? 'checked' : ''; ?>>
                        <label for="hypertension">Hypertension</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="diabetes" name="diabetes" <?php echo $entry['diabetes'] ? 'checked' : ''; ?>>
                        <label for="diabetes">Diabetes</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="f_planning" name="f_planning" <?php echo $entry['f_planning'] ? 'checked' : ''; ?>>
                        <label for="f_planning">F. Planning</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="t_pregnancy" name="t_pregnancy" <?php echo $entry['t_pregnancy'] ? 'checked' : ''; ?>>
                        <label for="t_pregnancy">T. Pregnancy</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Health and Sanitation:</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="poso" name="poso" <?php echo $entry['poso'] ? 'checked' : ''; ?>>
                        <label for="poso">POSO</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="nawasa" name="nawasa" <?php echo $entry['nawasa'] ? 'checked' : ''; ?>>
                        <label for="nawasa">NAWASA</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="mineral" name="mineral" <?php echo $entry['mineral'] ? 'checked' : ''; ?>>
                        <label for="mineral">MINERAL</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Zero Waste Management:</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="segregation" name="segregation" <?php echo $entry['segregation'] ? 'checked' : ''; ?>>
                        <label for="segregation">Segregation</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="composition" name="composition" <?php echo $entry['composition'] ? 'checked' : ''; ?>>
                        <label for="composition">Composition</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="purok">Purok:</label>
                <select id="purok" name="purok" required>
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $entry['purok'] == $i ? 'selected' : ''; ?>>Purok <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="button-container">
                <a href="purok_selection.php" class="button"><i class="fas fa-times"></i> Cancel</a>
                <button type="submit" class="button"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>

    <?php include '../assets/html/footer.html'; ?>
</body>
</html>