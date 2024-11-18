<?php
// Ensure no output before headers are sent
ob_start();

// Error reporting for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Required libraries
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/tcpdf');

// Database connection
try {
    $db_file = '../gender_dev_profiling.db';
    $db = new SQLite3($db_file);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch data (only active items)
try {
    $query = "SELECT * FROM child_immunization WHERE item_status = 'active'";
    $results = $db->query($query);
    $data = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }
} catch (Exception $e) {
    die("Data fetch failed: " . $e->getMessage());
}
$db->close();

// Handle export requests
if (isset($_POST['export'])) {
    $format = $_POST['format'];
    $filename = $_POST['filename'];
    
    try {
        switch($format) {
            case 'xlsx':
            case 'csv':
                exportSpreadsheet($data, $format, $_POST);
                break;
            case 'pdf':
                exportPDF($data, $_POST);
                break;
            default:
                throw new Exception("Invalid export format");
        }
    } catch (Exception $e) {
        die("Export failed: " . $e->getMessage());
    }
    exit;
}

function exportSpreadsheet($data, $format, $options) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set custom column widths
    $baseWidth = isset($options['columnWidth']) ? (int)$options['columnWidth'] : 15;
    $columnWidths = [
        'A' => $baseWidth * 2,    // Name
        'B' => $baseWidth * 0.8,  // Age
        'C' => $baseWidth * 1.2,  // Birthdate
        'D' => $baseWidth * 2,    // Mother's Name
    ];
    
    // Set immunization columns (E-R)
    foreach (range('E', 'R') as $col) {
        $columnWidths[$col] = $baseWidth;
    }
    
    // Apply column widths
    foreach ($columnWidths as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }
    
    // Style configuration
    $headerColor = $options['headerColor'] ?? '#E2EFDA';
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => '000000'],
            'size' => 11,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => substr($headerColor, 1)],
        ],
    ];
    
    // Set headers with proper merging
    $sheet->mergeCells('E1:R1'); // Immunization
    
    // Main headers
    $mainHeaders = [
        'A1' => 'Name of Child',
        'B1' => 'Age',
        'C1' => 'Birthdate',
        'D1' => 'Name of Mother',
        'E1' => 'Immunization'
    ];
    
    foreach ($mainHeaders as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Immunization sub-headers
    $immunizationHeaders = [
        'E2' => 'BCG', 'F2' => 'HEPA B', 'G2' => 'PENTA 1', 'H2' => 'PENTA 2', 'I2' => 'PENTA 3',
        'J2' => 'OPV 1', 'K2' => 'OPV 2', 'L2' => 'OPV 3', 'M2' => 'PCV 1', 'N2' => 'PCV 2',
        'O2' => 'PCV 3', 'P2' => 'IPV', 'Q2' => 'MCV 1', 'R2' => 'MCV 2'
    ];
    
    foreach ($immunizationHeaders as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Apply styles
    $sheet->getStyle('A1:R2')->applyFromArray($headerStyle);
    
    // Add data
    $row = 3;
    foreach ($data as $record) {
        $sheet->setCellValue('A'.$row, $record['name']);
        $sheet->setCellValue('B'.$row, $record['age']);
        $sheet->setCellValue('C'.$row, $record['birthdate']);
        $sheet->setCellValue('D'.$row, $record['mother_name']);
        $sheet->setCellValue('E'.$row, $record['bcg']);
        $sheet->setCellValue('F'.$row, $record['hepa_b']);
        $sheet->setCellValue('G'.$row, $record['penta_1']);
        $sheet->setCellValue('H'.$row, $record['penta_2']);
        $sheet->setCellValue('I'.$row, $record['penta_3']);
        $sheet->setCellValue('J'.$row, $record['opv_1']);
        $sheet->setCellValue('K'.$row, $record['opv_2']);
        $sheet->setCellValue('L'.$row, $record['opv_3']);
        $sheet->setCellValue('M'.$row, $record['pcv_1']);
        $sheet->setCellValue('N'.$row, $record['pcv_2']);
        $sheet->setCellValue('O'.$row, $record['pcv_3']);
        $sheet->setCellValue('P'.$row, $record['ipv']);
        $sheet->setCellValue('Q'.$row, $record['mcv_1']);
        $sheet->setCellValue('R'.$row, $record['mcv_2']);
        $row++;
    }
    
    // Create writer based on format
    if ($format === 'xlsx') {
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $ext = 'xlsx';
    } else {
        $writer = new Csv($spreadsheet);
        $writer->setDelimiter($options['delimiter'] ?? ',');
        $writer->setEnclosure($options['enclosure'] ?? '"');
        $writer->setLineEnding("\r\n");
        header('Content-Type: text/csv');
        $ext = 'csv';
    }
    
    header('Content-Disposition: attachment;filename="'.$options['filename'].'.'.$ext.'"');
    header('Cache-Control: max-age=0');
    
    ob_end_clean(); // Clear output buffer
    $writer->save('php://output');
}

function exportPDF($data, $options) {
    if (!class_exists('TCPDF')) {
        if (!file_exists(__DIR__ . '/tcpdf/tcpdf.php')) {
            die("TCPDF library not found. Please make sure it's installed in the 'tcpdf' directory.");
        }
        require_once('tcpdf.php');
    }
    
    // Create new PDF document with custom page size
    $pageWidth = isset($options['customPageWidth']) ? (float)$options['customPageWidth'] : 594; // Default to A3 width
    $pageHeight = isset($options['customPageHeight']) ? (float)$options['customPageHeight'] : 420; // Default to A3 height
    $pdf = new TCPDF('L', 'mm', array($pageWidth, $pageHeight), true, 'UTF-8');
    
    // Set document information
    $pdf->SetCreator('Child Immunization System');
    $pdf->SetAuthor('Administrator');
    $pdf->SetTitle('Child Immunization Data Export');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(10, 10, 10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 10);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $fontFamily = isset($options['fontFamily']) ? $options['fontFamily'] : 'helvetica';
    $pdf->SetFont($fontFamily, '', 8);
    
    // Calculate column widths
    $pageWidth = $pdf->getPageWidth() - 20;
    $cols = [
        ['width' => $pageWidth * 0.15, 'title' => 'Name of Child'],
        ['width' => $pageWidth * 0.05, 'title' => 'Age'],
        ['width' => $pageWidth * 0.10, 'title' => 'Birthdate'],
        ['width' => $pageWidth * 0.15, 'title' => 'Name of Mother']
    ];
    
    // Add immunization columns
    $immunizationWidth = $pageWidth * 0.55; // 55% of page width for immunizations
    $immunizationCols = ['BCG', 'HEPA B', 'PENTA 1', 'PENTA 2', 'PENTA 3', 'OPV 1', 'OPV 2', 'OPV 3', 'PCV 1', 'PCV 2', 'PCV 3', 'IPV', 'MCV 1', 'MCV 2'];
    foreach ($immunizationCols as $col) {
        $cols[] = ['width' => $immunizationWidth / count($immunizationCols), 'title' => $col];
    }
    
    // Draw nested header
    $x = 10;
    $y = 10;
    $headerHeight = 20;
    $subHeaderHeight = 10;
    
    // Main headers
    $headerColor = $options['pdfHeaderColor'] ?? '#E2EFDA';
    list($r, $g, $b) = sscanf($headerColor, "#%02x%02x%02x");
    $pdf->SetFillColor($r, $g, $b);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(128, 128, 128);
    $pdf->SetLineWidth(0.3);
    $pdf->SetFont($fontFamily, 'B', 9);
    
    $pdf->SetXY($x, $y);
    $pdf->Cell($cols[0]['width'] + $cols[1]['width'] + $cols[2]['width'] + $cols[3]['width'], $headerHeight, 'Patient Information', 1, 0, 'C', 1);
    $x += $cols[0]['width'] + $cols[1]['width'] + $cols[2]['width'] + $cols[3]['width'];
    
    $pdf->SetXY($x, $y);
    $pdf->Cell($immunizationWidth, $headerHeight / 2, 'Immunization', 1, 0, 'C', 1);
    
    // Sub headers
    $y += $headerHeight / 2;
    $x = 10;
    $pdf->SetXY($x, $y);
    $pdf->SetFont($fontFamily, 'B', 8);
    
    foreach ($cols as $col) {
        $pdf->Cell($col['width'], $subHeaderHeight, $col['title'], 1, 0, 'C', 1);
        $x += $col['width'];
    }
    
    // Draw data
    $y += $subHeaderHeight;
    $pdf->SetFont($fontFamily, '', 8);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach ($data as $record) {
        if ($y > $pdf->getPageHeight() - 20) {
            $pdf->AddPage();
            $y = 10;
            // Redraw headers on new page
            $x = 10;
            $headerHeight = 20;
            $subHeaderHeight = 10;
        
            // Main headers
            $pdf->SetFillColor($r, $g, $b);
            $pdf->SetTextColor(0);
            $pdf->SetDrawColor(128, 128, 128);
            $pdf->SetLineWidth(0.3);
            $pdf->SetFont($fontFamily, 'B', 9);
        
            $pdf->SetXY($x, $y);
            $pdf->Cell($cols[0]['width'] + $cols[1]['width'] + $cols[2]['width'] + $cols[3]['width'], $headerHeight, 'Patient Information', 1, 0, 'C', 1);
            $x += $cols[0]['width'] + $cols[1]['width'] + $cols[2]['width'] + $cols[3]['width'];
        
            $pdf->SetXY($x, $y);
            $pdf->Cell($immunizationWidth, $headerHeight / 2, 'Immunization', 1, 0, 'C', 1);
        
            // Sub headers
            $y += $headerHeight / 2;
            $x = 10;
            $pdf->SetXY($x, $y);
            $pdf->SetFont($fontFamily, 'B', 8);
        
            foreach ($cols as $col) {
                $pdf->Cell($col['width'], $subHeaderHeight, $col['title'], 1, 0, 'C', 1);
                $x += $col['width'];
            }
        
            $y += $subHeaderHeight;
            $pdf->SetFont($fontFamily, '', 8);
            $pdf->SetFillColor(255, 255, 255);
        }
        
        $x = 10;
        $pdf->SetXY($x, $y);
        
        // Draw cells
        $pdf->Cell($cols[0]['width'], 10, $record['name'], 1, 0, 'L', 1);
        $x += $cols[0]['width'];
        
        $pdf->SetX($x);
        $pdf->Cell($cols[1]['width'], 10, $record['age'], 1, 0, 'C', 1);
        $x += $cols[1]['width'];
        
        $pdf->SetX($x);
        $pdf->Cell($cols[2]['width'], 10, $record['birthdate'], 1, 0, 'C', 1);
        $x += $cols[2]['width'];
        
        $pdf->SetX($x);
        $pdf->Cell($cols[3]['width'], 10, $record['mother_name'], 1, 0, 'L', 1);
        $x += $cols[3]['width'];
        
        // Immunization data
        $immunizationData = [
            $record['bcg'], $record['hepa_b'], $record['penta_1'], $record['penta_2'], $record['penta_3'],
            $record['opv_1'], $record['opv_2'], $record['opv_3'], $record['pcv_1'], $record['pcv_2'],
            $record['pcv_3'], $record['ipv'], $record['mcv_1'], $record['mcv_2']
        ];
        
        foreach ($immunizationData as $index => $value) {
            $pdf->SetX($x);
            $pdf->Cell($cols[4 + $index]['width'], 10, $value, 1, 0, 'C', 1);
            $x += $cols[4 + $index]['width'];
        }
        
        $y += 10;
    }
    
    // Output PDF
    ob_end_clean(); // Clear output buffer
    $pdf->Output($options['filename'].'.pdf', 'D');
}

// Clear any remaining output buffer
while (ob_get_level()) {
    ob_end_clean();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Immunization Form - Export Options</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .container {
            flex: 1;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .export-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .option-group {
            background-color: var(--accent-color);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .option-group:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .option-group h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="number"],
        input[type="color"],
        select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--primary-color);
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="color"]:focus,
        select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px var(--shadow-color);
        }

        input[type="color"] {
            height: 40px;
            padding: 5px;
            cursor: pointer;
        }

        .button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .preview-container {
            margin-top: 2rem;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .preview-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .preview-header h2 {
            margin: 0;
            font-size: 1.2rem;
        }

        .table-wrapper {
            position: relative;
            overflow: hidden;
        }

        .table-scroll-container {
            overflow-x: auto;
            margin-bottom: 16px;
        }

        .horizontal-scroll {
            height: 12px;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .horizontal-scroll-content {
            height: 1px;
        }

        .preview-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
        }

        .preview-table th,
        .preview-table td {
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
            text-align: left;
        }

        .preview-table th {
            background-color: #f7fafc;
            font-weight: 600;
            color: var(--primary-color);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .preview-table th:first-child,
        .preview-table td:first-child {
            position: sticky;
            left: 0;
            z-index: 20;
            background-color: white;
        }

        .preview-table th:first-child {
            z-index: 30;
        }

        .preview-table tbody tr:nth-child(even) {
            background-color: #f7fafc;
        }

        .preview-table tbody tr:hover {
            background-color: #edf2f7;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .export-options {
                grid-template-columns: 1fr;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <?php include '../assets/html/header.html'; ?>

    <div class="container fade-in">
        <h1>Child Immunization Form - Export Options</h1>
        
        <form method="post" id="exportForm">
            <div class="export-options">
                <div class="option-group">
                    <h2><i class="fas fa-cog"></i> Basic Settings</h2>
                    <div class="form-group">
                        <label for="filename">Filename:</label>
                        <input type="text" id="filename" name="filename" value="child_immunization_data" required>
                    </div>
                    <div class="form-group">
                        <label for="format">Export Format:</label>
                        <select id="format" name="format" required onchange="updateOptions()">
                            <option value="xlsx">Excel (XLSX)</option>
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                </div>

                <div class="option-group" id="spreadsheetOptions">
                    <h2><i class="fas fa-table"></i> Spreadsheet Options</h2>
                    <div class="form-group">
                        <label for="columnWidth">Base Column Width:</label>
                        <input type="number" id="columnWidth" name="columnWidth" value="15" min="5" max="50">
                    </div>
                    <div class="form-group">
                        <label for="fontSize">Font Size:</label>
                        <input type="number" id="fontSize" name="fontSize" value="11" min="8" max="16">
                    </div>
                    <div class="form-group">
                        <label for="headerColor">Header Color:</label>
                        <input type="color" id="headerColor" name="headerColor" value="#E2EFDA">
                    </div>
                </div>

                <div class="option-group" id="csvOptions" style="display: none;">
                    <h2><i class="fas fa-file-csv"></i> CSV Options</h2>
                    <div class="form-group">
                        <label for="delimiter">Delimiter:</label>
                        <select id="delimiter" name="delimiter">
                            <option value=",">Comma (,)</option>
                            <option value=";">Semicolon (;)</option>
                            <option value="\t">Tab</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="enclosure">Text Enclosure:</label>
                        <select id="enclosure" name="enclosure">
                            <option value='"'>Double Quote (")</option>
                            <option value="'">Single Quote (')</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="csvHeaderColor">Header Color:</label>
                        <input type="color" id="csvHeaderColor" name="csvHeaderColor" value="#E2EFDA">
                    </div>
                </div>

                <div class="option-group" id="pdfOptions" style="display: none;">
                    <h2><i class="fas fa-file-pdf"></i> PDF Options</h2>
                    <div class="form-group">
                        <label for="customPageWidth">Custom Page Width (mm):</label>
                        <input type="number" id="customPageWidth" name="customPageWidth" value="1188" min="100" max="2000">
                    </div>
                    <div class="form-group">
                        <label for="customPageHeight">Custom Page Height (mm):</label>
                        <input type="number" id="customPageHeight" name="customPageHeight" value="420" min="100" max="2000">
                    </div>
                    <div class="form-group">
                        <label for="fontFamily">Font Family:</label>
                        <select id="fontFamily" name="fontFamily">
                            <?php
                            $fontDirectory = '../vendor/tecnickcom/tcpdf/fonts';
                            $defaultFont = 'helvetica';
                            if (is_dir($fontDirectory)) {
                                $fonts = array_diff(scandir($fontDirectory), array('.', '..'));
                                foreach ($fonts as $fontFile) {
                                    $fontName = pathinfo($fontFile, PATHINFO_FILENAME);
                                    if (pathinfo($fontFile, PATHINFO_EXTENSION) === 'php') {
                                        $isSelected = ($fontName === $defaultFont) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($fontName) . '" ' . $isSelected . '>' . htmlspecialchars(ucwords($fontName)) . '</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pdfHeaderColor">Header Color:</label>
                        <input type="color" id="pdfHeaderColor" name="pdfHeaderColor" value="#E2EFDA">
                    </div>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="child_immunization.php" class="button" style="margin-right: 10px;">
                    <i class="fas fa-arrow-left"></i> Back to Child Immunization Form
                </a>
                <button type="submit" name="export" class="button">
                    <i class="fas fa-file-export"></i> Export Data
                </button>
            </div>
        </form>

        <div class="preview-container">
            <div class="preview-header">
                <i class="fas fa-eye"></i>
                <h2>Preview (First 10 Rows)</h2>
            </div>
            <div class="table-wrapper">
                <div class="table-scroll-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th rowspan="2">Name of Child</th>
                                <th rowspan="2">Age</th>
                                <th rowspan="2">Birthdate</th>
                                <th rowspan="2">Name of Mother</th>
                                <th colspan="14">Immunization</th>
                            </tr>
                            <tr>
                                <th>BCG</th>
                                <th>HEPA B</th>
                                <th>PENTA 1</th>
                                <th>PENTA 2</th>
                                <th>PENTA 3</th>
                                <th>OPV 1</th>
                                <th>OPV 2</th>
                                <th>OPV 3</th>
                                <th>PCV 1</th>
                                <th>PCV 2</th>
                                <th>PCV 3</th>
                                <th>IPV</th>
                                <th>MCV 1</th>
                                <th>MCV 2</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $previewData = array_slice($data, 0, 10);
                            foreach ($previewData as $row):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['age']); ?></td>
                                <td><?php echo htmlspecialchars($row['birthdate']); ?></td>
                                <td><?php echo htmlspecialchars($row['mother_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['bcg']); ?></td>
                                <td><?php echo htmlspecialchars($row['hepa_b']); ?></td>
                                <td><?php echo htmlspecialchars($row['penta_1']); ?></td>
                                <td><?php echo htmlspecialchars($row['penta_2']); ?></td>
                                <td><?php echo htmlspecialchars($row['penta_3']); ?></td>
                                <td><?php echo htmlspecialchars($row['opv_1']); ?></td>
                                <td><?php echo htmlspecialchars($row['opv_2']); ?></td>
                                <td><?php echo htmlspecialchars($row['opv_3']); ?></td>
                                <td><?php echo htmlspecialchars($row['pcv_1']); ?></td>
                                <td><?php echo htmlspecialchars($row['pcv_2']); ?></td>
                                <td><?php echo htmlspecialchars($row['pcv_3']); ?></td>
                                <td><?php echo htmlspecialchars($row['ipv']); ?></td>
                                <td><?php echo htmlspecialchars($row['mcv_1']); ?></td>
                                <td><?php echo htmlspecialchars($row['mcv_2']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="horizontal-scroll">
                    <div class="horizontal-scroll-content"></div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../assets/html/footer.html'; ?>

    <script>
        function updateOptions() {
            const format = document.getElementById('format').value;
            const spreadsheetOptions = document.getElementById('spreadsheetOptions');
            const csvOptions = document.getElementById('csvOptions');
            const pdfOptions = document.getElementById('pdfOptions');
            
            spreadsheetOptions.style.display = format === 'xlsx' ? 'block' : 'none';
            csvOptions.style.display = format === 'csv' ? 'block' : 'none';
            pdfOptions.style.display = format === 'pdf' ? 'block' : 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const tableScrollContainer = document.querySelector('.table-scroll-container');
            const horizontalScroll = document.querySelector('.horizontal-scroll');
            const horizontalScrollContent = document.querySelector('.horizontal-scroll-content');

            function updateScrollbarWidth() {
                const tableWidth = tableScrollContainer.scrollWidth;
                horizontalScrollContent.style.width = tableWidth + 'px';
            }

            updateScrollbarWidth();
            window.addEventListener('resize', updateScrollbarWidth);

            tableScrollContainer.addEventListener('wheel', function(e) {
                if (e.deltaY !== 0) {
                    e.preventDefault();
                    this.scrollLeft += e.deltaY;
                    horizontalScroll.scrollLeft = this.scrollLeft;
                }
            });

            horizontalScroll.addEventListener('scroll', function() {
                tableScrollContainer.scrollLeft = this.scrollLeft;
            });
        });

        // Initial call to set the correct options visibility
        updateOptions();
    </script>
</body>
</html>