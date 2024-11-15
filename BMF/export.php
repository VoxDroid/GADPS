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

// Fetch data
try {
    $query = "SELECT * FROM barangay_midwifery";
    $results = $db->query($query);
    $data = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $row['prenatal_visits'] = json_decode($row['prenatal_visits'], true);
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
        'C' => $baseWidth * 3,    // Address
        'D' => $baseWidth * 1.2,  // LMP
        'E' => $baseWidth * 1.2,  // EDC
    ];
    
    // Set prenatal visit columns (F-Q)
    foreach (range('F', 'Q') as $col) {
        $columnWidths[$col] = $baseWidth;
    }
    
    // Set remaining columns
    $columnWidths['R'] = $baseWidth * 1.2; // Date of Birth
    $columnWidths['S'] = $baseWidth * 0.8; // Sex
    $columnWidths['T'] = $baseWidth;       // Birth Weight
    $columnWidths['U'] = $baseWidth;       // Birth Length
    $columnWidths['V'] = $baseWidth * 1.5; // Place of Delivery
    
    // Apply column widths
    foreach ($columnWidths as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }
    
    // Style configuration
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
            'startColor' => ['rgb' => 'E2EFDA'],
        ],
    ];
    
    // Set headers with proper merging
    $sheet->mergeCells('F1:Q1'); // Prenatal Visits
    $sheet->mergeCells('F2:H2'); // 1st Trimester
    $sheet->mergeCells('I2:K2'); // 2nd Trimester
    $sheet->mergeCells('L2:Q2'); // 3rd Trimester
    
    // Main headers
    $mainHeaders = [
        'A1' => 'Name',
        'B1' => 'Age',
        'C1' => 'Address',
        'D1' => 'LMP',
        'E1' => 'EDC',
        'F1' => 'Prenatal Visits',
        'R1' => 'Date of Birth',
        'S1' => 'Sex',
        'T1' => 'Birth Weight',
        'U1' => 'Birth Length',
        'V1' => 'Place of Delivery'
    ];
    
    foreach ($mainHeaders as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Trimester headers
    $trimesterHeaders = [
        'F2' => '1st Trimester',
        'I2' => '2nd Trimester',
        'L2' => '3rd Trimester'
    ];
    
    foreach ($trimesterHeaders as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Visit numbers (1-12)
    $visitCols = range('F', 'Q');
    for ($i = 0; $i < 12; $i++) {
        $sheet->setCellValue($visitCols[$i].'3', $i + 1);
    }
    
    // Apply styles
    $sheet->getStyle('A1:V3')->applyFromArray($headerStyle);
    
    // Add data
    $row = 4;
    foreach ($data as $record) {
        $sheet->setCellValue('A'.$row, $record['name']);
        $sheet->setCellValue('B'.$row, $record['age']);
        $sheet->setCellValue('C'.$row, $record['address']);
        $sheet->setCellValue('D'.$row, $record['lmp']);
        $sheet->setCellValue('E'.$row, $record['edc']);
        
        // Prenatal visits
        foreach (range('F', 'Q') as $index => $col) {
            $sheet->setCellValue($col.$row, $record['prenatal_visits'][$index] ?? '');
        }
        
        $sheet->setCellValue('R'.$row, $record['date_of_birth']);
        $sheet->setCellValue('S'.$row, $record['sex']);
        $sheet->setCellValue('T'.$row, $record['birth_weight']);
        $sheet->setCellValue('U'.$row, $record['birth_length']);
        $sheet->setCellValue('V'.$row, $record['place_of_delivery']);
        
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
    $pdf->SetCreator('Barangay Midwifery System');
    $pdf->SetAuthor('Administrator');
    $pdf->SetTitle('Midwifery Data Export');
    
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
        ['width' => $pageWidth * 0.10, 'title' => 'Name'],
        ['width' => $pageWidth * 0.03, 'title' => 'Age'],
        ['width' => $pageWidth * 0.12, 'title' => 'Address'],
        ['width' => $pageWidth * 0.05, 'title' => 'LMP'],
        ['width' => $pageWidth * 0.05, 'title' => 'EDC']
    ];
    
    // Add prenatal visit columns
    $prenatalVisitWidth = $pageWidth * 0.36; // 36% of page width for prenatal visits
    for ($i = 1; $i <= 12; $i++) {
        $cols[] = ['width' => $prenatalVisitWidth / 12, 'title' => $i];
    }
    
    // Add remaining columns
    $cols = array_merge($cols, [
        ['width' => $pageWidth * 0.07, 'title' => 'Date of Birth'],
        ['width' => $pageWidth * 0.03, 'title' => 'Sex'],
        ['width' => $pageWidth * 0.05, 'title' => 'Birth Weight'],
        ['width' => $pageWidth * 0.05, 'title' => 'Birth Length'],
        ['width' => $pageWidth * 0.09, 'title' => 'Place of Delivery']
    ]);
    
    // Draw nested header
    $x = 10;
    $y = 10;
    $headerHeight = 20;
    $subHeaderHeight = 10;
    
    // Main headers
    $pdf->SetFillColor(226, 239, 218);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(128, 128, 128);
    $pdf->SetLineWidth(0.3);
    $pdf->SetFont($fontFamily, 'B', 9);
    
    $pdf->SetXY($x, $y);
    $pdf->Cell($cols[0]['width'] + $cols[1]['width'] + $cols[2]['width'], $headerHeight, 'Patient Information', 1, 0, 'C', 1);
    $x += $cols[0]['width'] + $cols[1]['width'] + $cols[2]['width'];
    
    $pdf->SetXY($x, $y);
    $pdf->Cell($cols[3]['width'] + $cols[4]['width'], $headerHeight, 'Pregnancy Info', 1, 0, 'C', 1);
    $x += $cols[3]['width'] + $cols[4]['width'];
    
    $pdf->SetXY($x, $y);
    $pdf->Cell($prenatalVisitWidth, $headerHeight / 2, 'Prenatal Visits', 1, 0, 'C', 1);
    $pdf->SetXY($x, $y + $headerHeight / 2);
    $pdf->Cell($prenatalVisitWidth / 4, $headerHeight / 2, '1st Trimester', 1, 0, 'C', 1);
    $pdf->Cell($prenatalVisitWidth / 4, $headerHeight / 2, '2nd Trimester', 1, 0, 'C', 1);
    $pdf->Cell($prenatalVisitWidth / 2, $headerHeight / 2, '3rd Trimester', 1, 0, 'C', 1);
    $x += $prenatalVisitWidth;
    
    $pdf->SetXY($x, $y);
    $pdf->Cell($pageWidth - $x + 10, $headerHeight, 'Delivery Information', 1, 0, 'C', 1);
    
    // Sub headers
    $y += $headerHeight;
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
            $prenatalVisitWidth = $pageWidth * 0.36;
        
            // Main headers
            $pdf->SetFillColor(226, 239, 218);
            $pdf->SetTextColor(0);
            $pdf->SetDrawColor(128, 128, 128);
            $pdf->SetLineWidth(0.3);
            $pdf->SetFont($fontFamily, 'B', 9);
        
            $pdf->SetXY($x, $y);
            $pdf->Cell($cols[0]['width'] + $cols[1]['width'] + $cols[2]['width'], $headerHeight, 'Patient Information', 1, 0, 'C', 1);
            $x += $cols[0]['width'] + $cols[1]['width'] + $cols[2]['width'];
        
            $pdf->SetXY($x, $y);
            $pdf->Cell($cols[3]['width'] + $cols[4]['width'], $headerHeight, 'Pregnancy Info', 1, 0, 'C', 1);
            $x += $cols[3]['width'] + $cols[4]['width'];
        
            $pdf->SetXY($x, $y);
            $pdf->Cell($prenatalVisitWidth, $headerHeight / 2, 'Prenatal Visits', 1, 0, 'C', 1);
            $pdf->SetXY($x, $y + $headerHeight / 2);
            $pdf->Cell($prenatalVisitWidth / 4, $headerHeight / 2, '1st Trimester', 1, 0, 'C', 1);
            $pdf->Cell($prenatalVisitWidth / 4, $headerHeight / 2, '2nd Trimester', 1, 0, 'C', 1);
            $pdf->Cell($prenatalVisitWidth / 2, $headerHeight / 2, '3rd Trimester', 1, 0, 'C', 1);
            $x += $prenatalVisitWidth;
        
            $pdf->SetXY($x, $y);
            $pdf->Cell($pageWidth - $x + 10, $headerHeight, 'Delivery Information', 1, 0, 'C', 1);
        
            // Sub headers
            $y += $headerHeight;
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
        $pdf->Cell($cols[2]['width'], 10, $record['address'], 1, 0, 'L', 1);
        $x += $cols[2]['width'];
        
        $pdf->SetX($x);
        $pdf->Cell($cols[3]['width'], 10, $record['lmp'], 1, 0, 'C', 1);
        $x += $cols[3]['width'];
        
        $pdf->SetX($x);
        $pdf->Cell($cols[4]['width'], 10, $record['edc'], 1, 0, 'C', 1);
        $x += $cols[4]['width'];
        
        // Prenatal visits
        for ($i = 0; $i < 12; $i++) {
            $pdf->SetX($x);
            $pdf->Cell($cols[5 + $i]['width'], 10, $record['prenatal_visits'][$i] ?? '', 1, 0, 'C', 1);
            $x += $cols[5 + $i]['width'];
        }
        
        // Remaining data
        $pdf->SetX($x);
        $pdf->Cell($cols[17]['width'], 10, $record['date_of_birth'], 1, 0, 'C', 1);
        $x += $cols[17]['width'];
        
        $pdf->SetX($x);
        $pdf->Cell($cols[18]['width'], 10, $record['sex'], 1, 0, 'C', 1);
        $x += $cols[18]['width'];
        
        $pdf->SetX($x);
        $pdf->Cell($cols[19]['width'], 10, $record['birth_weight'], 1, 0, 'C', 1);
        $x += $cols[19]['width'];
        
        $pdf->SetX($x);
        $pdf->Cell($cols[20]['width'], 10, $record['birth_length'], 1, 0, 'C', 1);
        $x += $cols[20]['width'];
        
        $pdf->SetX($x);
        $pdf->Cell($cols[21]['width'], 10, $record['place_of_delivery'], 1, 0, 'L', 1);
        
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
    <title>Advanced Export Options</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-dark: #45a049;
            --secondary-color: #2196F3;
            --accent-color: #FF9800;
            --error-color: #f44336;
            --success-color: #4CAF50;
            --background-color: #f8f9fa;
            --surface-color: #ffffff;
            --text-color: #333333;
            --border-color: #e0e0e0;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .back-button {
            padding: 0.5rem 1rem;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #1976D2;
        }

        .export-panel {
            background-color: var(--surface-color);
            border-radius: 8px;
            box-shadow: 0 2px 4px var(--shadow-color);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .option-group {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .option-group h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
            font-size: 1.1rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .preview-container {
            background-color: var(--surface-color);
            border-radius: 8px;
            box-shadow: 0 2px 4px var(--shadow-color);
            padding: 2rem;
            overflow-x: auto;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .preview-table th,
        .preview-table td {
            border: 1px solid var(--border-color);
            padding: 0.75rem;
            text-align: left;
        }

        .preview-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .preview-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .button-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .button-primary:hover {
            background-color: var(--primary-dark);
        }

        .button-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .button-secondary:hover {
            background-color: #1976D2;
        }

        .button-accent {
            background-color: var(--accent-color);
            color: white;
        }

        .button-accent:hover {
            background-color: #F57C00;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .options-grid {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Advanced Export Options</h1>
            <a href="barangay_midwifery.php" class="back-button">Back to Midwifery Form</a>
        </div>

        <form method="post" id="exportForm">
            <div class="export-panel">
                <div class="options-grid">
                    <div class="option-group">
                        <h3>Basic Settings</h3>
                        <div class="form-group">
                            <label for="filename">Filename:</label>
                            <input type="text" id="filename" name="filename" value="barangay_midwifery_data" required>
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
                        <h3>Spreadsheet Options</h3>
                        <div class="form-group">
                            <label for="columnWidth">Base Column Width:</label>
                            <input type="number" id="columnWidth" name="columnWidth" value="15" min="5" max="50">
                        </div>
                        <div class="form-group">
                            <label for="fontSize">Font Size:</label>
                            <input type="number" id="fontSize" name="fontSize" value="11" min="8" max="16">
                        </div>
                    </div>

                    <div class="option-group" id="csvOptions" style="display: none;">
                        <h3>CSV Options</h3>
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
                    </div>

                    <div class="option-group" id="pdfOptions" style="display: none;">
                        <h3>PDF Options</h3>
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
                                <option value="helvetica">Helvetica</option>
                                <option value="times">Times New Roman</option>
                                <option value="courier">Courier</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fontSize">Font Size:</label>
                            <input type="number" id="fontSize" name="fontSize" value="8" min="6" max="14">
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" name="export" class="button button-primary">
                        Export File
                    </button>
                </div>
            </div>
        </form>

        <div class="preview-container">
            <h2>Preview</h2>
            <div id="previewTable"></div>
        </div>
    </div>

    <script>
        function updateOptions() {
            const format = document.getElementById('format').value;
            const spreadsheetOptions = document.getElementById('spreadsheetOptions');
            const csvOptions = document.getElementById('csvOptions');
            const pdfOptions = document.getElementById('pdfOptions');
            
            spreadsheetOptions.style.display = format === 'xlsx' ? 'block' : 'none';
            csvOptions.style.display = format === 'csv' ? 'block' : 'none';
            pdfOptions.style.display = format === 'pdf' ? 'block' : 'none';
            
            updatePreview();
        }

        function updatePreview() {
            const previewData = <?php echo json_encode(array_slice($data, 0, 5)); ?>;
            const previewTable = document.getElementById('previewTable');
            
            let table = '<table class="preview-table">';
            
            // Headers
            table += `
                <tr>
                    <th rowspan="3">Name</th>
                    <th rowspan="3">Age</th>
                    <th rowspan="3">Address</th>
                    <th rowspan="3">LMP</th>
                    <th rowspan="3">EDC</th>
                    <th colspan="12">Prenatal Visits</th>
                    <th rowspan="3">Date of Birth</th>
                    <th rowspan="3">Sex</th>
                    <th rowspan="3">Birth Weight</th>
                    <th rowspan="3">Birth Length</th>
                    <th rowspan="3">Place of Delivery</th>
                </tr>
                <tr>
                    <th colspan="3">1st Trimester</th>
                    <th colspan="3">2nd Trimester</th>
                    <th colspan="6">3rd Trimester</th>
                </tr>
                <tr>
                    ${Array.from({length: 12}, (_, i) => `<th>${i + 1}</th>`).join('')}
                </tr>
            `;
            
            // Data rows
            previewData.forEach(row => {
                table += `
                    <tr>
                        <td>${row.name}</td>
                        <td>${row.age}</td>
                        <td>${row.address}</td>
                        <td>${row.lmp}</td>
                        <td>${row.edc}</td>
                        ${row.prenatal_visits.map(visit => `<td>${visit || ''}</td>`).join('')}
                        <td>${row.date_of_birth}</td>
                        <td>${row.sex}</td>
                        <td>${row.birth_weight}</td>
                        <td>${row.birth_length}</td>
                        <td>${row.place_of_delivery}</td>
                    </tr>
                `;
            });
            
            table += '</table>';
            previewTable.innerHTML = table;
        }

        // Initial preview and options update
        updateOptions();

        // Add event listeners to form inputs for automatic preview update
        document.querySelectorAll('#exportForm input, #exportForm select').forEach(element => {
            element.addEventListener('change', updatePreview);
        });
    </script>
</body>
</html>