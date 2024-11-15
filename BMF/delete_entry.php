<?php
// delete_entry.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $db = new SQLite3('../gender_dev_profiling.db');
    
    $stmt = $db->prepare("DELETE FROM barangay_midwifery WHERE id = ?");
    $stmt->bindValue(1, $_POST['id'], SQLITE3_INTEGER);
    $stmt->execute();
    
    $db->close();
}

header('Location: barangay_midwifery.php');
exit;
?>