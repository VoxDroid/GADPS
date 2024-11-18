<?php
// purok_selection.php
// Database initialization
$db_file = '../gender_dev_profiling.db';
$db = new SQLite3($db_file);

// Create table if it doesn't exist
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

// Get pagination parameters
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
$selected_purok = isset($_GET['purok']) ? (int)$_GET['purok'] : 1;

// Calculate offset
$offset = ($page - 1) * $entries_per_page;

// Prepare search condition
$search_condition = '';
if ($search) {
    $search_condition = " AND (name LIKE '%$search%' OR occupation LIKE '%$search%')";
}

// Get total entries count
$total_entries = $db->querySingle("SELECT COUNT(*) FROM purok_selection WHERE item_status = 'active' AND purok = $selected_purok" . $search_condition);

$purok_counts = [];
for ($i = 1; $i <= 6; $i++) {
    $purok_counts[$i] = $db->querySingle("SELECT COUNT(*) FROM purok_selection WHERE item_status = 'active' AND purok = $i");
}

// Get total active entries count
$total_active_entries = array_sum($purok_counts);

// Prepare the ORDER BY clause
$order_by = " ORDER BY $sort_column $sort_order";

// Get entries for current page
$query = "SELECT * FROM purok_selection WHERE item_status = 'active' AND purok = $selected_purok" . $search_condition . $order_by . " LIMIT $entries_per_page OFFSET $offset";
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

// Function to archive an entry
function archiveEntry($id) {
    global $db;
    $stmt = $db->prepare("UPDATE purok_selection SET item_status = 'inactive' WHERE id = ?");
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $stmt->execute();
}

// Handle archive action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
    archiveEntry($_POST['archive_id']);
    header('Location: purok_selection.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purok Selection Form</title>
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

        .button-edit, .button-archive {
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            padding: 8px 16px;
            margin: 2px;
            min-width: 100px;
        }

        .button-edit {
            background-color: var(--primary-color);
        }

        .button-archive {
            background-color: #ffa500;
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
        <h1>Purok Selection Form</h1>
        <div class="purok-stats">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <div class="purok-stat <?php echo $selected_purok == $i ? 'active' : ''; ?>">
                    <i class="fas fa-map-marker-alt"></i>
                    Purok <?php echo $i; ?>: <?php echo $purok_counts[$i]; ?>
                </div>
            <?php endfor; ?>
            <div class="total-entries">
                <i class="fas fa-users"></i>
                Total Active: <?php echo $total_active_entries; ?>
            </div>
        </div>
        <div class="controls-container">
            <div class="button-container">
                <a href="add_entry.php" class="button"><i class="fas fa-plus"></i> Add New Entry</a>
                <a href="export.php" class="button button-export"><i class="fas fa-file-export"></i> Export</a>
                <a href="ps_archive.php" class="button"><i class="fas fa-archive"></i> View Archive</a>
                <span class="reset-sort" onclick="resetSort()"><i class="fas fa-undo"></i> Reset Sort</span>
            </div>
            
            <div class="search-container">
                <span class="total-entries">Active Entries in Purok <?php echo $selected_purok; ?>: <?php echo $total_entries; ?></span>
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
                                <td>
                                    <a href="edit_entry.php?id=<?php echo $row['id']; ?>" class="button button-edit"><i class="fas fa-edit"></i> Edit</a>
                                    <button onclick="showArchiveModal(<?php echo $row['id']; ?>)" class="button button-archive"><i class="fas fa-archive"></i> Archive</button>
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

    <div id="archiveModal" class="modal">
        <div class="modal-content">
            <h2><i class="fas fa-archive" style="color: #ffa500;"></i> Confirm Archive</h2>
            <p>Are you sure you want to archive this entry?</p>
            <div class="modal-buttons">
                <button type="button" onclick="hideArchiveModal()" class="button">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <form id="archive-form" method="POST" style="display: inline;">
                    <input type="hidden" name="archive_id" id="archiveId">
                    <button type="submit" class="button button-archive">
                        <i class="fas fa-check"></i> Confirm Archive
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include '../assets/html/footer.html'; ?>

    <script>
        // Add the following new function for changing purok:
        function changePurok(purok) {
            saveState();
            window.location.href = updateQueryString({ purok: purok, page: 1 });
        }


        // Store the current scroll positions and page number
        let currentVerticalScrollPosition = 0;
        let currentHorizontalScrollPosition = 0;
        let currentPage = <?php echo $page; ?>;

        // Function to save the current scroll positions and page number
        function saveState() {
            currentVerticalScrollPosition = window.pageYOffset;
            const tableScrollContainer = document.querySelector('.table-scroll-container');
            currentHorizontalScrollPosition = tableScrollContainer ? tableScrollContainer.scrollLeft : 0;
            localStorage.setItem('verticalScrollPosition', currentVerticalScrollPosition);
            localStorage.setItem('horizontalScrollPosition', currentHorizontalScrollPosition);
            // Remove this line:
            // localStorage.setItem('currentPage', currentPage);
        }

        // Function to restore the scroll positions and navigate to the saved page
        function restoreState() {
            const savedVerticalPosition = localStorage.getItem('verticalScrollPosition');
            const savedHorizontalPosition = localStorage.getItem('horizontalScrollPosition');
            // Remove these lines:
            // const savedPage = localStorage.getItem('currentPage');
            // if (savedPage && savedPage !== '<?php echo $page; ?>') {
            //     window.location.href = updateQueryString({ page: savedPage });
            //     return;
            // }

            if (savedVerticalPosition) {
                window.scrollTo(0, parseInt(savedVerticalPosition));
            }
            if (savedHorizontalPosition) {
                const tableScrollContainer = document.querySelector('.table-scroll-container');
                if (tableScrollContainer) {
                    tableScrollContainer.scrollLeft = parseInt(savedHorizontalPosition);
                }
            }
        }

        // Function to update query string
        function updateQueryString(params) {
            const searchParams = new URLSearchParams(window.location.search);
            for (const [key, value] of Object.entries(params)) {
                searchParams.set(key, value);
            }
            return `${window.location.pathname}?${searchParams.toString()}`;
        }

        // Modify existing functions to save state
        function showArchiveModal(id) {
            saveState();
            const modal = document.getElementById('archiveModal');
            const archiveId = document.getElementById('archiveId');
            modal.style.display = 'block';
            archiveId.value = id;
        }

        function hideArchiveModal() {
            const modal = document.getElementById('archiveModal');
            modal.style.display = 'none';
        }

        function changeEntries(entries) {
            saveState();
            window.location.href = updateQueryString({ entries: entries, page: 1 });
        }

        function changePage(page) {
            // Remove this line:
            // currentPage = page;
            // saveState(); // Remove this line as well
            window.location.href = updateQueryString({ page: page });
        }

        function clearLocalStorage() {
            localStorage.removeItem('verticalScrollPosition');
            localStorage.removeItem('horizontalScrollPosition');
        }

        let debounceTimer;
        function debounceSearch(value) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                saveState();
                window.location.href = updateQueryString({ search: value, page: 1 });
            }, 300);
        }

        function sortTable(column) {
            saveState();
            const currentSort = '<?php echo $sort_column; ?>';
            const currentOrder = '<?php echo $sort_order; ?>';
            let newOrder = 'ASC';

            if (column === currentSort) {
                newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            }

            window.location.href = updateQueryString({ sort: column, order: newOrder, page: currentPage });
        }

        function resetSort() {
            saveState();
            window.location.href = updateQueryString({ sort: 'id', order: 'ASC', page: currentPage });
        }

        // Modify the links to switch between active and archived views
        document.addEventListener('DOMContentLoaded', function() {
            const viewArchiveLink = document.querySelector('a[href="ps_archive.php"]');
            const backToActiveLink = document.querySelector('a[href="purok_selection.php"]');
            
            if (viewArchiveLink) {
                viewArchiveLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    clearLocalStorage();
                    window.location.href = 'ps_archive.php?page=1';
                });
            }
            
            if (backToActiveLink) {
                backToActiveLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    clearLocalStorage();
                    window.location.href = 'purok_selection.php?page=1';
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
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

            // Restore state after page load
            restoreState();

            // Add event listeners for pagination buttons
            const paginationButtons = document.querySelectorAll('.pagination-button');
            paginationButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.dataset.page;
                    if (page) {
                        changePage(page);
                    }
                });
            });

            // Save state before unload
            window.addEventListener('beforeunload', saveState);

            // Handle archive form submission
            const archiveForm = document.getElementById('archive-form');
            if (archiveForm) {
                archiveForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    fetch(window.location.pathname, {
                        method: 'POST',
                        body: formData
                    }).then(response => {
                        if (response.ok) {
                            // Hide the modal
                            hideArchiveModal();
                            // Reload the page content
                            window.location.reload();
                        } else {
                            console.error('Archive operation failed');
                            alert('Failed to archive the item. Please try again.');
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            }
        });

        // Handle modal close on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('archiveModal');
            if (event.target === modal) {
                hideArchiveModal();
            }
        }
    </script>
    </script>
</body>
</html>