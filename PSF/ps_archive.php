<?php
// ps_archive.php
// Database connection
$db_file = '../gender_dev_profiling.db';
$db = new SQLite3($db_file);

// Get pagination parameters
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$selected_purok = isset($_GET['purok']) ? (int)$_GET['purok'] : 0; // 0 means all puroks

// Calculate offset
$offset = ($page - 1) * $entries_per_page;

// Prepare search condition
$search_condition = '';
if ($search) {
    $search_condition = " AND (name LIKE '%$search%' OR occupation LIKE '%$search%')";
}

// Prepare purok condition
$purok_condition = $selected_purok ? " AND purok = $selected_purok" : "";

// Get total entries count
$total_entries = $db->querySingle("SELECT COUNT(*) FROM purok_selection WHERE item_status = 'inactive'" . $search_condition . $purok_condition);

$purok_counts = [];
for ($i = 1; $i <= 6; $i++) {
    $purok_counts[$i] = $db->querySingle("SELECT COUNT(*) FROM purok_selection WHERE item_status = 'inactive' AND purok = $i");
}

// Get total inactive entries count
$total_inactive_entries = array_sum($purok_counts);

// Prepare the ORDER BY clause
$order_by = " ORDER BY $sort_column $sort_order";

// Get entries for current page
$query = "SELECT * FROM purok_selection WHERE item_status = 'inactive'" . $search_condition . $purok_condition . $order_by . " LIMIT $entries_per_page OFFSET $offset";
$results = $db->query($query);

// Calculate total pages
$total_pages = ceil($total_entries / $entries_per_page);

// Function to generate sort URL
function getSortUrl($column) {
    global $sort_column, $sort_order;
    $new_order = ($sort_column === $column && $sort_order === 'ASC') ? 'DESC' : 'ASC';
    return updateQueryString(['sort' => $column, 'order' => $new_order]);
}

// Function to update query string
function updateQueryString($params) {
    $current_params = $_GET;
    foreach ($params as $key => $value) {
        $current_params[$key] = $value;
    }
    return '?' . http_build_query($current_params);
}

// Function to restore an entry
function restoreEntry($id) {
    global $db;
    $stmt = $db->prepare("UPDATE purok_selection SET item_status = 'active' WHERE id = ?");
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $stmt->execute();
}

// Function to permanently delete an entry
function permanentlyDeleteEntry($id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM purok_selection WHERE id = ?");
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $stmt->execute();
}

// Handle restore and delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['restore_id'])) {
        restoreEntry($_POST['restore_id']);
    } elseif (isset($_POST['delete_id'])) {
        permanentlyDeleteEntry($_POST['delete_id']);
    }
    header('Location: ps_archive.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include '../assets/html/disable-caching.html'; ?>
    <title>Archived Entries - Purok Selection Form</title>
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

        .button-container {
            margin-bottom: 30px;
            position: sticky;
            left: 0;
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

        .table-wrapper {
            overflow: hidden;
            margin: 0 -20px;
            padding: 0 20px;
            position: relative;
        }

        .table-scroll-container {
            overflow-x: auto;
            margin-bottom: 16px;
        }

        table {
            border-collapse: separate;
            border-spacing: 0;
            min-width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 
                0 4px 6px rgba(0, 0, 0, 0.1),
                0 1px 3px rgba(0, 0, 0, 0.08);
            border: 2px solid var(--primary-color);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--primary-color);
            border-right: 1px solid var(--primary-color);
            white-space: nowrap;
            font-size: 14px;
        }

        th {
            background-color: var(--accent-color) !important;
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--primary-color);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .prenatal-header {
            background-color: var(--accent-color) !important;
            text-align: center;
            color: var(--primary-color);
            border-left: 2px solid var(--primary-color);
        }

        .nested-header {
            background-color: var(--accent-color) !important;
            text-align: center;
            font-size: 0.85em;
            color: var(--primary-color);
            padding: 10px;
            border-left: 1px solid var(--primary-color);
            white-space: nowrap;
        }

        .nested-cell {
            text-align: center;
            padding: 10px;
            border-left: 1px solid var(--primary-color);
        }

        th:first-child,
        td:first-child {
            position: sticky;
            left: 0;
            z-index: 2;
            border-right: 2px solid var(--primary-color);
        }

        th:first-child {
            z-index: 11;
            background-color: var(--accent-color) !important;
        }

        td:first-child {
            background-color: white !important;
        }

        tr:hover td:first-child {
            background-color: #f7fafc !important;
        }

        .visit-cell {
            text-align: center;
            min-width: 100px;
        }

        tr:hover {
            background-color: #f7fafc;
        }

        .button-restore, .button-delete {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            padding: 8px 16px;
            margin: 2px;
            min-width: 100px;
        }

        .button-restore {
            background-color: #4CAF50;
        }

        .button-delete {
            background-color: #f44336;
        }

        .controls-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 20px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .search-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-input {
            padding: 8px 12px;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            font-size: 14px;
            min-width: 200px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px var(--shadow-color);
        }

        .entries-select {
            padding: 8px 12px;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .entries-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px var(--shadow-color);
        }

        .pagination {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .pagination-button {
            padding: 8px 16px;
            border: 2px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .pagination-button:hover, .pagination-button.active {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 400px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .modal-content h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .modal-content p {
            margin-bottom: 25px;
            color: #666;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-buttons button {
            min-width: 120px;
            font-family: 'Poppins', sans-serif;
        }

        .empty-row td {
            text-align: center;
            color: #a0aec0;
            font-style: italic;
            background-color: white !important;
        }

        .empty-row:hover td {
            background-color: #f7fafc !important;
        }

        .table-wrapper {
            position: relative;
        }

        .table-scroll-container {
            overflow-x: auto;
            margin-bottom: 16px;
            padding-bottom: 16px;
        }

        .horizontal-scroll {
            height: 16px;
            overflow-x: auto;
            overflow-y: hidden;
            margin-bottom: 16px;
        }

        .horizontal-scroll-content {
            height: 1px;
        }

        table {
            margin-bottom: 0;
        }

        th:first-child,
        td:first-child {
            position: sticky;
            left: 0;
            z-index: 2;
            background-color: white;
        }

        th:first-child {
            z-index: 3;
        }

        tr:hover td:first-child {
            background-color: #f7fafc;
        }

        .sort-icon {
            margin-left: 5px;
        }
        .sort-icon.active {
            color: var(--primary-color);
        }
        th {
            cursor: pointer;
        }
        th:hover {
            background-color: var(--accent-color);
        }
        .reset-sort {
            margin-left: 10px;
            color: var(--primary-color);
            cursor: pointer;
        }
        .reset-sort:hover {
            text-decoration: underline;
        }

        .purok-select {
            padding: 8px 12px;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: white;
            color: var(--text-color);
            cursor: pointer;
        }

        .purok-select:focus {
            outline: none;
            box-shadow: 0 0 0 3px var(--shadow-color);
        }

        .purok-select:hover {
            border-color: var(--secondary-color);
        }

        .purok-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .purok-stat {
            background-color: var(--accent-color);
            color: var(--primary-color);
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .purok-stat.active {
            background-color: var(--primary-color);
            color: white;
        }

        .total-entries {
            background-color: var(--secondary-color);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            margin-right: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <?php include '../assets/html/header.html'; ?>

    <div class="container">
        <h1>Archived Entries - Purok Selection Form</h1>
        <div class="purok-stats">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <div class="purok-stat <?php echo $selected_purok == $i ? 'active' : ''; ?>">
                    <i class="fas fa-map-marker-alt"></i>
                    Purok <?php echo $i; ?>: <?php echo $purok_counts[$i]; ?>
                </div>
            <?php endfor; ?>
            <div class="total-entries">
                <i class="fas fa-users"></i>
                Total Archived: <?php echo $total_inactive_entries; ?>
            </div>
        </div>
        <div class="controls-container">
            <div class="button-container">
                <a href="purok_selection.php" class="button"><i class="fas fa-arrow-left"></i> Back to Active Entries</a>
                <span class="reset-sort" onclick="resetSort()"><i class="fas fa-undo"></i> Reset Sort</span>
            </div>
            
            <div class="search-container">
                <span class="total-entries">Archived Entries: <?php echo $total_entries; ?></span>
                <select class="entries-select" onchange="changeEntries(this.value)">
                    <?php
                    $options = [10, 20, 30, 40, 50];
                    foreach ($options as $option) {
                        $selected = $entries_per_page == $option ? 'selected' : '';
                        echo "<option value='$option' $selected>$option entries</option>";
                    }
                    ?>
                </select>
                
                <select class="purok-select" onchange="changePurok(this.value)">
                    <option value="0" <?php echo $selected_purok == 0 ? 'selected' : ''; ?>>All Puroks</option>
                    <?php
                    for ($i = 1; $i <= 6; $i++) {
                        $selected = $selected_purok == $i ? 'selected' : '';
                        echo "<option value='$i' $selected>Purok $i</option>";
                    }
                    ?>
                </select>
                
                <input type="text" 
                       class="search-input" 
                       placeholder="Search..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       onkeyup="debounceSearch(this.value)">
                <i class="fas fa-search" style="color: var(--primary-color);"></i>
            </div>
        </div>

        <div class="table-wrapper">
            <div class="horizontal-scroll">
                <div class="horizontal-scroll-content"></div>
            </div>
            <div class="table-scroll-container">
                <table>
                    <thead>
                        <tr>
                            <th rowspan="2" onclick="sortTable('id')">
                                ID
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'id' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'id' ? 'active' : ''; ?>"></i>
                            </th>
                            <th rowspan="2" onclick="sortTable('name')">
                                Name
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'name' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'name' ? 'active' : ''; ?>"></i>
                            </th>
                            <th rowspan="2" onclick="sortTable('birthday')">
                                Birthday
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'birthday' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'birthday' ? 'active' : ''; ?>"></i>
                            </th>
                            <th rowspan="2" onclick="sortTable('age')">
                                Age
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'age' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'age' ? 'active' : ''; ?>"></i>
                            </th>
                            <th rowspan="2" onclick="sortTable('gender')">
                                Gender
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'gender' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'gender' ? 'active' : ''; ?>"></i>
                            </th>
                            <th rowspan="2" onclick="sortTable('civil_status')">
                                Civil Status
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'civil_status' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'civil_status' ? 'active' : ''; ?>"></i>
                            </th>
                            <th rowspan="2" onclick="sortTable('occupation')">
                                Occupation
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'occupation' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'occupation' ? 'active' : ''; ?>"></i>
                            </th>
                            <th colspan="6" class="nested-header">Health Condition</th>
                            <th colspan="3" class="nested-header">Health and Sanitation</th>
                            <th colspan="2" class="nested-header">Zero Waste Management</th>
                            <th rowspan="2" onclick="sortTable('purok')">
                                Purok
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'purok' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'purok' ? 'active' : ''; ?>"></i>
                            </th>
                            <th rowspan="2">Actions</th>
                        </tr>
                        <tr>
                            <th class="nested-header">SC</th>
                            <th class="nested-header">PWD</th>
                            <th class="nested-header">Hypertension</th>
                            <th class="nested-header">Diabetes</th>
                            <th class="nested-header">F. Planning</th>
                            <th class="nested-header">T. Pregnancy</th>
                            <th class="nested-header">POSO</th>
                            <th class="nested-header">NAWASA</th>
                            <th class="nested-header">MINERAL</th>
                            <th class="nested-header">Segregation</th>
                            <th class="nested-header">Composition</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $row_count = 0;
                        while ($row = $results->fetchArray(SQLITE3_ASSOC)): 
                            $row_count++;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['birthday']); ?></td>
                                <td><?php echo htmlspecialchars($row['age']); ?></td>
                                <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                <td><?php echo htmlspecialchars($row['civil_status']); ?></td>
                                <td><?php echo htmlspecialchars($row['occupation']); ?></td>
                                <td class="nested-cell"><?php echo $row['sc'] ? '✓' : '✗'; ?></td>
                                <td class="nested-cell"><?php echo $row['pwd'] ? '✓' : '✗'; ?></td>
                                <td class="nested-cell"><?php echo $row['hypertension'] ? '✓' : '✗'; ?></td>
                                <td class="nested-cell"><?php echo $row['diabetes'] ? '✓' : '✗'; ?></td>
                                <td class="nested-cell"><?php echo $row['f_planning'] ? '✓' : '✗'; ?></td>
                                <td class="nested-cell"><?php echo $row['t_pregnancy'] ? '✓' : '✗'; ?></td>
                                <td class="nested-cell"><?php echo $row['poso'] ? '✓' : '✗'; ?></td>
                                <td class="nested-cell"><?php echo $row['nawasa'] ? '✓' : '✗'; ?></td>
                                <td class="nested-cell"><?php echo $row['mineral'] ? '✓' : '✗'; ?></td>
                                <td class="nested-cell"><?php echo $row['segregation'] ? '✓' : '✗'; ?></td>
                                <td class="nested-cell"><?php echo $row['composition'] ? '✓' : '✗'; ?></td>
                                <td><?php echo htmlspecialchars($row['purok']); ?></td>
                                <td>
                                    <button onclick="showRestoreModal(<?php echo $row['id']; ?>)" class="button button-restore"><i class="fas fa-undo"></i> Restore</button>
                                    <button onclick="showDeleteModal(<?php echo $row['id']; ?>)" class="button button-delete"><i class="fas fa-trash-alt"></i> Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; 
                        
                        while ($row_count < $entries_per_page): 
                            $row_count++;
                        ?>
                            <tr class="empty-row">
                                <td>#</td>
                                <td>No data</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td>-</td>
                                <td class="nested-cell">-</td>
                                <td class="nested-cell">-</td>
                                <td class="nested-cell">-</td>
                                <td class="nested-cell">-</td>
                                <td class="nested-cell">-</td>
                                <td class="nested-cell">-</td>
                                <td class="nested-cell">-</td>
                                <td class="nested-cell">-</td>
                                <td class="nested-cell">-</td>
                                <td class="nested-cell">-</td>
                                <td class="nested-cell">-</td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <button onclick="changePage(1)" class="pagination-button"><i class="fas fa-angle-double-left"></i></button>
                <button onclick="changePage(<?php echo $page - 1; ?>)" class="pagination-button"><i class="fas fa-angle-left"></i></button>
            <?php endif; ?>

            <?php
            for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
                $active = $i === $page ? 'active' : '';
                echo "<button onclick='changePage($i)' class='pagination-button $active'>$i</button>";
            }
            ?>

            <?php if ($page < $total_pages): ?>
                <button onclick="changePage(<?php echo $page + 1; ?>)" class="pagination-button"><i class="fas fa-angle-right"></i></button>
                <button onclick="changePage(<?php echo $total_pages; ?>)" class="pagination-button"><i class="fas fa-angle-double-right"></i></button>
            <?php endif; ?>
        </div>
    </div>

    <div id="restoreModal" class="modal">
        <div class="modal-content">
            <h2><i class="fas fa-undo" style="color: #4CAF50;"></i> Confirm Restore</h2>
            <p>Are you sure you want to restore this entry?</p>
            <div class="modal-buttons">
                <button type="button" onclick="hideRestoreModal()" class="button">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form id="restore-form" method="POST" style="display: inline;">
                    <input type="hidden" name="restore_id" id="restoreId">
                    <button type="submit" class="button button-restore">
                        <i class="fas fa-check"></i> Confirm Restore
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h2><i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i> Confirm Permanent Delete</h2>
            <p>Are you sure you want to permanently delete this entry? This action cannot be undone.</p>
            <div class="modal-buttons">
                <button type="button" onclick="hideDeleteModal()" class="button">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form id="delete-form" method="POST" style="display: inline;">
                    <input type="hidden" name="delete_id" id="deleteId">
                    <button type="submit" class="button button-delete">
                        <i class="fas fa-check"></i> Confirm Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include '../assets/html/footer.html'; ?>

    <script>
        let debounceTimer;
        const savedState = JSON.parse(localStorage.getItem('ps_archive_state')) || {};

        function saveState() {
            const state = {
                search: document.querySelector('.search-input').value,
                entries: document.querySelector('.entries-select').value,
                purok: document.querySelector('.purok-select').value,
                sort: '<?php echo $sort_column; ?>',
                order: '<?php echo $sort_order; ?>',
                page: '<?php echo $page; ?>',
                scrollLeft: document.querySelector('.table-scroll-container').scrollLeft
            };
            localStorage.setItem('ps_archive_state', JSON.stringify(state));
        }

        function restoreState() {
            if (savedState.search) document.querySelector('.search-input').value = savedState.search;
            if (savedState.entries) document.querySelector('.entries-select').value = savedState.entries;
            if (savedState.purok) document.querySelector('.purok-select').value = savedState.purok;
            if (savedState.scrollLeft) {
                document.querySelector('.table-scroll-container').scrollLeft = savedState.scrollLeft;
                document.querySelector('.horizontal-scroll').scrollLeft = savedState.scrollLeft;
            }
        }

        function updateTable() {
            const searchValue = document.querySelector('.search-input').value;
            const entriesValue = document.querySelector('.entries-select').value;
            const purokValue = document.querySelector('.purok-select').value;
            const sortColumn = '<?php echo $sort_column; ?>';
            const sortOrder = '<?php echo $sort_order; ?>';
            const currentPage = '<?php echo $page; ?>';

            const url = new URL(window.location.href);
            url.searchParams.set('search', searchValue);
            url.searchParams.set('entries', entriesValue);
            url.searchParams.set('purok', purokValue);
            url.searchParams.set('sort', sortColumn);
            url.searchParams.set('order', sortOrder);
            url.searchParams.set('page', currentPage);

            window.location.href = url.toString();
        }

        function debounceSearch(value) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                saveState();
                updateTable();
            }, 300);
        }

        function changeEntries(value) {
            saveState();
            updateTable();
        }

        function changePurok(value) {
            saveState();
            updateTable();
        }

        function changePage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            saveState();
            window.location.href = url.toString();
        }

        function sortTable(column) {
            const currentSort = '<?php echo $sort_column; ?>';
            const currentOrder = '<?php echo $sort_order; ?>';
            let newOrder = 'ASC';

            if (column === currentSort) {
                newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            }

            const url = new URL(window.location.href);
            url.searchParams.set('sort', column);
            url.searchParams.set('order', newOrder);
            url.searchParams.set('page', 1);
            saveState();
            window.location.href = url.toString();
        }

        function resetSort() {
            const url = new URL(window.location.href);
            url.searchParams.delete('sort');
            url.searchParams.delete('order');
            saveState();
            window.location.href = url.toString();
        }

        function initializeHorizontalScroll() {
            const table = document.querySelector('table');
            const scrollContainer = document.querySelector('.horizontal-scroll');
            const scrollContent = scrollContainer.querySelector('.horizontal-scroll-content');
            const tableScrollContainer = document.querySelector('.table-scroll-container');

            function updateScrollbar() {
                scrollContent.style.width = table.offsetWidth + 'px';
            }

            updateScrollbar();
            window.addEventListener('resize', updateScrollbar);

            scrollContainer.addEventListener('scroll', function() {
                tableScrollContainer.scrollLeft = scrollContainer.scrollLeft;
                saveState();
            });

            tableScrollContainer.addEventListener('scroll', function() {
                scrollContainer.scrollLeft = tableScrollContainer.scrollLeft;
                saveState();
            });

            // Add mouse wheel horizontal scrolling
            tableScrollContainer.addEventListener('wheel', function(e) {
                if (e.deltaY !== 0) {
                    e.preventDefault();
                    tableScrollContainer.scrollLeft += e.deltaY;
                    scrollContainer.scrollLeft = tableScrollContainer.scrollLeft;
                    saveState();
                }
            });

            tableScrollContainer.addEventListener('mouseleave', function() {
                this.style.outline = 'none';
            });
        }

        function showRestoreModal(id) {
            saveState();
            const modal = document.getElementById('restoreModal');
            const restoreId = document.getElementById('restoreId');
            modal.style.display = 'block';
            restoreId.value = id;
        }

        function hideRestoreModal() {
            const modal = document.getElementById('restoreModal');
            modal.style.display = 'none';
        }

        function showDeleteModal(id) {
            saveState();
            const modal = document.getElementById('deleteModal');
            const deleteId = document.getElementById('deleteId');
            modal.style.display = 'block';
            deleteId.value = id;
        }

        function hideDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            restoreState();
            initializeHorizontalScroll();

            const restoreForm = document.getElementById('restore-form');
            const deleteForm = document.getElementById('delete-form');

            restoreForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    if (response.ok) {
                        hideRestoreModal();
                        window.location.reload();
                    } else {
                        console.error('Restore operation failed');
                        alert('Failed to restore the item. Please try again.');
                    }
                }).catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });

            deleteForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    if (response.ok) {
                        hideDeleteModal();
                        window.location.reload();
                    } else {
                        console.error('Delete operation failed');
                        alert('Failed to delete the item. Please try again.');
                    }
                }).catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        });

        // Handle modal close on outside click
        window.onclick = function(event) {
            const restoreModal = document.getElementById('restoreModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === restoreModal) {
                hideRestoreModal();
            }
            if (event.target === deleteModal) {
                hideDeleteModal();
            }
        }
    </script>
</body>
</html>