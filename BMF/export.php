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
    $query = "SELECT * FROM barangay_midwifery WHERE item_status = 'active'";
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
    <title>Barangay Midwifery Form - Export Options</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6A5ACD;
            --secondary-color: #9370DB;
            --accent-color: #E6E6FA;
            --text-color: #333;
            --shadow-color: rgba(106, 90, 205, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #E6E6FA 0%, #9370DB 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background-color: white;
            padding: 0.75rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo {
            width: 40px;
            height: 40px;
            background-color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo i {
            color: white;
            font-size: 1.2rem;
        }

        .site-title {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 500;
            line-height: 50px;
            margin: 0;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary-color);
        }

        .container {
            flex: 1;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
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
        }

        .option-group h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.2rem;
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
        select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--primary-color);
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px var(--shadow-color);
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
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .button:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .preview-container {
            margin-top: 2rem;
            overflow-x: auto;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .preview-table th,
        .preview-table td {
            border: 1px solid var(--primary-color);
            padding: 0.75rem;
            text-align: left;
        }

        .preview-table th {
            background-color: var(--accent-color);
            color: var(--primary-color);
            font-weight: 600;
        }

        .preview-table tr:nth-child(even) {
            background-color: #f8f8f8;
        }

        .footer {
            background-color: white;
            padding: 1rem;
            margin-top: auto;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 1.2rem;
        }

        .social-links a:hover {
            color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .export-options {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-venus-mars"></i>
                </div>
                <h1 class="site-title">Gender and Development Profiling System</h1>
            </div>
            <nav class="nav-links">
                <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                <a href="#"><i class="fas fa-info-circle"></i> About</a>
                <a href="#"><i class="fas fa-envelope"></i> Contact</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1>Barangay Midwifery Form - Export Options</h1>
        
        <form method="post" id="exportForm">
            <div class="export-options">
                <div class="option-group">
                    <h2>Basic Settings</h2>
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
                    <h2>Spreadsheet Options</h2>
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
                    <h2>CSV Options</h2>
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
                    <h2>PDF Options</h2>
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
                </div>
            </div>

            <div style="text-align: center;">
                <a href="barangay_midwifery.php" class="button" style="margin-right: 10px;">
                    <i class="fas fa-arrow-left"></i> Back to Midwifery Form
                </a>
                <button type="submit" name="export" class="button">
                    <i class="fas fa-file-export"></i> Export Data
                </button>
            </div>
        </form>

        <div class="preview-container">
            <h2>Preview (First 10 Rows)</h2>
            <table class="preview-table">
                <thead>
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
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <th><?php echo $i; ?></th>
                        <?php endfor; ?>
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
                        <td><?php echo htmlspecialchars($row['address']); ?></td>
                        <td><?php echo htmlspecialchars($row['lmp']); ?></td>
                        <td><?php echo htmlspecialchars($row['edc']); ?></td>
                        <?php for ($i = 0; $i < 12; $i++): ?>
                            <td><?php echo htmlspecialchars($row['prenatal_visits'][$i] ?? ''); ?></td>
                        <?php endfor; ?>
                        <td><?php echo htmlspecialchars($row['date_of_birth']); ?></td>
                        <td><?php echo htmlspecialchars($row['sex']); ?></td>
                        <td><?php echo htmlspecialchars($row['birth_weight']); ?></td>
                        <td><?php echo htmlspecialchars($row['birth_length']); ?></td>
                        <td><?php echo htmlspecialchars($row['place_of_delivery']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
            </div>
            <p>&copy; 2024 Gender and Development Profiling System. All rights reserved.</p>
        </div>
    </footer>

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

        // Initial call to set the correct options visibility
        updateOptions();
    </script>
</body>
</html>