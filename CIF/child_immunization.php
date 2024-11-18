<?php
// child_immunization.php
// Database initialization
$db_file = '../gender_dev_profiling.db';
$db = new SQLite3($db_file);

// Create table if it doesn't exist
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

// Get pagination parameters
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Calculate offset
$offset = ($page - 1) * $entries_per_page;

// Prepare search condition
$search_condition = '';
if ($search) {
    $search_condition = " AND (name LIKE '%$search%' OR mother_name LIKE '%$search%')";
}

// Get total entries count
$total_entries = $db->querySingle("SELECT COUNT(*) FROM child_immunization WHERE item_status = 'active'" . $search_condition);

// Prepare the ORDER BY clause
$order_by = " ORDER BY $sort_column $sort_order";

// Get entries for current page
$query = "SELECT * FROM child_immunization WHERE item_status = 'active'" . $search_condition . $order_by . " LIMIT $entries_per_page OFFSET $offset";
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
    $stmt = $db->prepare("UPDATE child_immunization SET item_status = 'inactive' WHERE id = ?");
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $stmt->execute();
}

// Handle archive action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
    archiveEntry($_POST['archive_id']);
    header('Location: child_immunization.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Immunization Form</title>
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

        .nested-header {
            background-color: var(--accent-color) !important;
            text-align: center;
            font-size: 0.85em;
            color: var(--primary-color);
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

        .nested-cell {
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
        <h1>Child Immunization Form</h1>
        
        <div class="controls-container">
            <div class="button-container">
                <a href="add_entry.php" class="button"><i class="fas fa-plus"></i> Add New Entry</a>
                <a href="export.php" class="button button-export"><i class="fas fa-file-export"></i> Export</a>
                <a href="ci_archive.php" class="button"><i class="fas fa-archive"></i> View Archive</a>
                <span class="reset-sort" onclick="resetSort()"><i class="fas fa-undo"></i> Reset Sort</span>
            </div>
            
            <div class="search-container">
                <span class="total-entries">Total Active Entries: <?php echo $total_entries; ?></span>
                <select class="entries-select" onchange="changeEntries(this.value)">
                    <?php
                    $options = [10, 20, 30, 40, 50];
                    foreach ($options as $option) {
                        $selected = $entries_per_page == $option ? 'selected' : '';
                        echo "<option value='$option' $selected>$option entries</option>";
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
                                Name of Child
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'name' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'name' ? 'active' : ''; ?>"></i>
                            </th>
                            <th rowspan="2" onclick="sortTable('age')">
                                Age
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'age' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'age' ? 'active' : ''; ?>"></i>
                            </th>
                            <th rowspan="2" onclick="sortTable('birthdate')">
                                Birthdate
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'birthdate' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'birthdate' ? 'active' : ''; ?>"></i>
                            </th>
                            <th rowspan="2" onclick="sortTable('mother_name')">
                                Name of Mother
                                <i class="fas fa-sort sort-icon <?php echo $sort_column === 'mother_name' ? ($sort_order === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : ''; ?> <?php echo $sort_column === 'mother_name' ? 'active' : ''; ?>"></i>
                            </th>
                            <th colspan="14" class="nested-header">Immunization</th>
                            <th rowspan="2">Actions</th>
                        </tr>
                        <tr>
                            <th class="nested-header">BCG</th>
                            <th class="nested-header">HEPA B</th>
                            <th class="nested-header">PENTA 1</th>
                            <th class="nested-header">PENTA 2</th>
                            <th class="nested-header">PENTA 3</th>
                            <th class="nested-header">OPV 1</th>
                            <th class="nested-header">OPV 2</th>
                            <th class="nested-header">OPV 3</th>
                            <th class="nested-header">PCV 1</th>
                            <th class="nested-header">PCV 2</th>
                            <th class="nested-header">PCV 3</th>
                            <th class="nested-header">IPV</th>
                            <th class="nested-header">MCV 1</th>
                            <th class="nested-header">MCV 2</th>
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
                                <td><?php echo htmlspecialchars($row['age']); ?></td>
                                <td><?php echo htmlspecialchars($row['birthdate']); ?></td>
                                <td><?php echo htmlspecialchars($row['mother_name']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['bcg']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['hepa_b']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['penta_1']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['penta_2']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['penta_3']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['opv_1']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['opv_2']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['opv_3']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['pcv_1']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['pcv_2']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['pcv_3']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['ipv']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['mcv_1']); ?></td>
                                <td class="nested-cell"><?php echo htmlspecialchars($row['mcv_2']); ?></td>
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
                                <?php for ($i = 0; $i < 14; $i++) echo '<td class="nested-cell">-</td>'; ?>
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
        }

        // Function to restore the scroll positions and navigate to the saved page
        function restoreState() {
            const savedVerticalPosition = localStorage.getItem('verticalScrollPosition');
            const savedHorizontalPosition = localStorage.getItem('horizontalScrollPosition');

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
            const viewArchiveLink = document.querySelector('a[href="ci_archive.php"]');
            const backToActiveLink = document.querySelector('a[href="child_immunization.php"]');
            
            if (viewArchiveLink) {
                viewArchiveLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    clearLocalStorage();
                    window.location.href = 'ci_archive.php?page=1';
                });
            }
            
            if (backToActiveLink) {
                backToActiveLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    clearLocalStorage();
                    window.location.href = 'child_immunization.php?page=1';
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

            // Mouse wheel horizontal scrolling
            tableScrollContainer.addEventListener('wheel', function(e) {
                if (e.deltaY !== 0) {
                    e.preventDefault();
                    tableScrollContainer.scrollLeft += e.deltaY;
                    scrollContainer.scrollLeft = tableScrollContainer.scrollLeft;
                    saveState();
                }
            });

            // Restore scroll positions after page load
            restoreState();
        });

        // Call restoreState when the page loads
        window.addEventListener('load', restoreState);

        // Save state when the user leaves the page
        window.addEventListener('beforeunload', saveState);
    </script>
</body>
</html>