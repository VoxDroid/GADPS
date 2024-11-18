<?php
// add_entry.php for purok_selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new SQLite3('../gender_dev_profiling.db');
    
    $stmt = $db->prepare("INSERT INTO purok_selection (name, birthday, age, gender, civil_status, occupation, sc, pwd, hypertension, diabetes, f_planning, t_pregnancy, poso, nawasa, mineral, segregation, composition, purok) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
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
    
    $stmt->execute();
    $db->close();
    
    header('Location: purok_selection.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Entry - Purok Selection Form</title>
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
        <h1>Add New Entry</h1>
        <form method="POST">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="birthday">Birthday:</label>
                <input type="date" id="birthday" name="birthday" required>
            </div>
            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" id="age" name="age" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender:</label>
                <select id="gender" name="gender" required>
                    <option value="not_specified">Not Specified</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="civil_status">Civil Status:</label>
                <select id="civil_status" name="civil_status" required>
                    <option value="not_specified">Not Specified</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Widowed">Widowed</option>
                    <option value="Divorced">Divorced</option>
                    <option value="Separated">Separated</option>
                </select>
            </div>
            <div class="form-group">
                <label for="occupation">Occupation:</label>
                <input type="text" id="occupation" name="occupation" required>
            </div>
            <div class="form-group">
                <label>Health Condition:</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="sc" name="sc">
                        <label for="sc">SC</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="pwd" name="pwd">
                        <label for="pwd">PWD</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="hypertension" name="hypertension">
                        <label for="hypertension">Hypertension</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="diabetes" name="diabetes">
                        <label for="diabetes">Diabetes</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="f_planning" name="f_planning">
                        <label for="f_planning">F. Planning</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="t_pregnancy" name="t_pregnancy">
                        <label for="t_pregnancy">T. Pregnancy</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Health and Sanitation:</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="poso" name="poso">
                        <label for="poso">POSO</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="nawasa" name="nawasa">
                        <label for="nawasa">NAWASA</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="mineral" name="mineral">
                        <label for="mineral">MINERAL</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Zero Waste Management:</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="segregation" name="segregation">
                        <label for="segregation">Segregation</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="composition" name="composition">
                        <label for="composition">Composition</label>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="purok">Purok:</label>
                <select id="purok" name="purok" required>
                    <option value="1">Purok 1</option>
                    <option value="2">Purok 2</option>
                    <option value="3">Purok 3</option>
                    <option value="4">Purok 4</option>
                    <option value="5">Purok 5</option>
                    <option value="6">Purok 6</option>
                </select>
            </div>
            <div class="button-container">
                <a href="purok_selection.php" class="button"><i class="fas fa-times"></i> Cancel</a>
                <button type="submit" class="button"><i class="fas fa-save"></i> Save Entry</button>
            </div>
        </form>
    </div>

    <?php include '../assets/html/footer.html'; ?>
</body>
</html>