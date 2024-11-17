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
    $query = "SELECT * FROM purok_selection WHERE item_status = 'active'";
    $results = $db->query($query);
    $data = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }
} catch (Exception $e) {
    die("Data fetch failed: " . $e->getMessage());
}

// Get available puroks
$availablePuroks = [];
foreach ($data as $row) {
    if (!in_array($row['purok'], $availablePuroks)) {
        $availablePuroks[] = $row['purok'];
    }
}
sort($availablePuroks);

$db->close();

// Handle export requests
if (isset($_POST['export'])) {
    $format = $_POST['format'];
    $filename = $_POST['filename'];
    $selectedPurok = isset($_POST['purok']) && $_POST['purok'] !== '' ? (int)$_POST['purok'] : null;
    
    // Filter data based on selected purok
    if ($selectedPurok !== null) {
        $data = array_filter($data, function($row) use ($selectedPurok) {
            return $row['purok'] == $selectedPurok;
        });
    }
    
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
        'B' => $baseWidth * 1.2,  // Birthday
        'C' => $baseWidth * 0.8,  // Age
        'D' => $baseWidth * 1.2,  // Gender
        'E' => $baseWidth * 1.5,  // Civil Status
        'F' => $baseWidth * 2,    // Occupation
    ];
    
    // Health Condition columns (G-L)
    for ($i = 'G'; $i <= 'L'; $i++) {
        $columnWidths[$i] = $baseWidth * 0.8;
    }
    
    // Health and Sanitation columns (M-O)
    for ($i = 'M'; $i <= 'O'; $i++) {
        $columnWidths[$i] = $baseWidth * 0.8;
    }
    
    // Zero Waste Management columns (P-Q)
    for ($i = 'P'; $i <= 'Q'; $i++) {
        $columnWidths[$i] = $baseWidth * 0.8;
    }
    
    // Purok column
    $columnWidths['R'] = $baseWidth * 0.8;
    
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
    
    // Set main headers
    $sheet->mergeCells('G1:L1');
    $sheet->setCellValue('G1', 'HEALTH CONDITION');
    
    $sheet->mergeCells('M1:O1');
    $sheet->setCellValue('M1', 'HEALTH AND SANITATION');
    
    $sheet->mergeCells('P1:Q1');
    $sheet->setCellValue('P1', 'ZERO WASTE MANAGEMENT');
    
    // Set sub-headers
    $headers = [
        'A2' => 'Name',
        'B2' => 'Birthday',
        'C2' => 'Age',
        'D2' => 'Gender',
        'E2' => 'Civil Status',
        'F2' => 'Occupation',
        'G2' => 'SC',
        'H2' => 'PWD',
        'I2' => 'Hypertension',
        'J2' => 'Diabetes',
        'K2' => 'F. Planning',
        'L2' => 'T. Pregnancy',
        'M2' => 'POSO',
        'N2' => 'NAWASA',
        'O2' => 'MINERAL',
        'P2' => 'Segregation',
        'Q2' => 'Composition',
        'R2' => 'Purok'
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    
    // Apply styles
    $sheet->getStyle('A1:R2')->applyFromArray($headerStyle);
    
    // Add data starting from row 3
    $row = 3;
    foreach ($data as $record) {
        $sheet->setCellValue('A'.$row, $record['name']);
        $sheet->setCellValue('B'.$row, $record['birthday']);
        $sheet->setCellValue('C'.$row, $record['age']);
        $sheet->setCellValue('D'.$row, $record['gender']);
        $sheet->setCellValue('E'.$row, $record['civil_status']);
        $sheet->setCellValue('F'.$row, $record['occupation']);
        $sheet->setCellValue('G'.$row, $record['sc'] ? 'Yes' : 'No');
        $sheet->setCellValue('H'.$row, $record['pwd'] ? 'Yes' : 'No');
        $sheet->setCellValue('I'.$row, $record['hypertension'] ? 'Yes' : 'No');
        $sheet->setCellValue('J'.$row, $record['diabetes'] ? 'Yes' : 'No');
        $sheet->setCellValue('K'.$row, $record['f_planning'] ? 'Yes' : 'No');
        $sheet->setCellValue('L'.$row, $record['t_pregnancy'] ? 'Yes' : 'No');
        $sheet->setCellValue('M'.$row, $record['poso'] ? 'Yes' : 'No');
        $sheet->setCellValue('N'.$row, $record['nawasa'] ? 'Yes' : 'No');
        $sheet->setCellValue('O'.$row, $record['mineral'] ? 'Yes' : 'No');
        $sheet->setCellValue('P'.$row, $record['segregation'] ? 'Yes' : 'No');
        $sheet->setCellValue('Q'.$row, $record['composition'] ? 'Yes' : 'No');
        $sheet->setCellValue('R'.$row, $record['purok']);
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
    
    ob_end_clean();
    $writer->save('php://output');
}

function exportPDF($data, $options) {
    if (!class_exists('TCPDF')) {
        if (!file_exists(__DIR__ . '/tcpdf/tcpdf.php')) {
            die("TCPDF library not found. Please make sure it's installed in the 'tcpdf' directory.");
        }
        require_once('tcpdf.php');
    }
    
    $pdf = new TCPDF('L', 'mm', array($options['customPageWidth'] ?? 594, $options['customPageHeight'] ?? 420), true, 'UTF-8');
    
    $pdf->SetCreator('Purok Selection System');
    $pdf->SetAuthor('Administrator');
    $pdf->SetTitle('Purok Selection Data Export');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(TRUE, 10);
    
    $pdf->AddPage();
    
    $fontFamily = isset($options['fontFamily']) ? $options['fontFamily'] : 'helvetica';
    $pdf->SetFont($fontFamily, '', 8);
    
    $pageWidth = $pdf->getPageWidth() - 20;
    
    // Calculate section widths
    $basicInfoWidth = $pageWidth * 0.35;
    $healthConditionWidth = $pageWidth * 0.30;
    $healthSanitationWidth = $pageWidth * 0.15;
    $wasteManagementWidth = $pageWidth * 0.15;
    $purokWidth = $pageWidth * 0.05;
    
    // Draw main headers
    $headerColor = $options['pdfHeaderColor'] ?? '#E2EFDA';
    list($r, $g, $b) = sscanf($headerColor, "#%02x%02x%02x");
    $pdf->SetFillColor($r, $g, $b);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(128, 128, 128);
    $pdf->SetLineWidth(0.3);
    $pdf->SetFont($fontFamily, 'B', 9);
    
    $x = 10;
    $y = 10;
    
    // Basic Info section
    $pdf->SetXY($x, $y);
    $pdf->Cell($basicInfoWidth, 10, 'Basic Information', 1, 0, 'C', 1);
    $x += $basicInfoWidth;
    
    // Health Condition section
    $pdf->SetXY($x, $y);
    $pdf->Cell($healthConditionWidth, 10, 'HEALTH CONDITION', 1, 0, 'C', 1);
    $x += $healthConditionWidth;
    
    // Health and Sanitation section
    $pdf->SetXY($x, $y);
    $pdf->Cell($healthSanitationWidth, 10, 'HEALTH AND SANITATION', 1, 0, 'C', 1);
    $x += $healthSanitationWidth;
    
    // Waste Management section
    $pdf->SetXY($x, $y);
    $pdf->Cell($wasteManagementWidth, 10, 'ZERO WASTE MANAGEMENT', 1, 0, 'C', 1);
    $x += $wasteManagementWidth;
    
    // Purok section
    $pdf->SetXY($x, $y);
    $pdf->Cell($purokWidth, 10, 'Purok', 1, 0, 'C', 1);
    
    // Draw sub-headers
    $y += 10;
    $x = 10;
    
    // Basic Info sub-headers
    $basicInfoCols = [
        ['width' => $basicInfoWidth * 0.3, 'title' => 'Name'],
        ['width' => $basicInfoWidth * 0.15, 'title' => 'Birthday'],
        ['width' => $basicInfoWidth * 0.1, 'title' => 'Age'],
        ['width' => $basicInfoWidth * 0.15, 'title' => 'Gender'],
        ['width' => $basicInfoWidth * 0.15, 'title' => 'Civil Status'],
        ['width' => $basicInfoWidth * 0.15, 'title' => 'Occupation'],
    ];
    
    foreach ($basicInfoCols as $col) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($col['width'], 10, $col['title'], 1, 0, 'C', 1);
        $x += $col['width'];
    }
    
    // Health Condition sub-headers
    $healthConditionCols = [
        ['width' => $healthConditionWidth / 6, 'title' => 'SC'],
        ['width' => $healthConditionWidth / 6, 'title' => 'PWD'],
        ['width' => $healthConditionWidth / 6, 'title' => 'Hypertension'],
        ['width' => $healthConditionWidth / 6, 'title' => 'Diabetes'],
        ['width' => $healthConditionWidth / 6, 'title' => 'F. Planning'],
        ['width' => $healthConditionWidth / 6, 'title' => 'T. Pregnancy'],
    ];
    
    foreach ($healthConditionCols as $col) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($col['width'], 10, $col['title'], 1, 0, 'C', 1);
        $x += $col['width'];
    }
    
    // Health and Sanitation sub-headers
    $healthSanitationCols = [
        ['width' => $healthSanitationWidth / 3, 'title' => 'POSO'],
        ['width' => $healthSanitationWidth / 3, 'title' => 'NAWASA'],
        ['width' => $healthSanitationWidth / 3, 'title' => 'MINERAL'],
    ];
    
    foreach ($healthSanitationCols as $col) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($col['width'], 10, $col['title'], 1, 0, 'C', 1);
        $x += $col['width'];
    }
    
    // Waste Management sub-headers
    $wasteManagementCols = [
        ['width' => $wasteManagementWidth / 2, 'title' => 'Segregation'],
        ['width' => $wasteManagementWidth / 2, 'title' => 'Composition'],
    ];
    
    foreach ($wasteManagementCols as $col) {
        $pdf->SetXY($x, $y);
        $pdf->Cell($col['width'], 10, $col['title'], 1, 0, 'C', 1);
        $x += $col['width'];
    }
    
    // Purok sub-header
    $pdf->SetXY($x, $y);
    $pdf->Cell($purokWidth, 10, 'No.', 1, 0, 'C', 1);
    
    // Draw data
    $y += 10;
    $pdf->SetFont($fontFamily, '', 8);
    $pdf->SetFillColor(255, 255, 255);
    
    foreach ($data as $record) {
        if ($y > $pdf->getPageHeight() - 20) {
            $pdf->AddPage();
            $y = 10;
            
            // Redraw headers on new page
            $x = 10;
            $pdf->SetFillColor($r, $g, $b);
            $pdf->SetFont($fontFamily, 'B', 9);
            
            // Redraw main headers
            $pdf->SetXY($x, $y);
            $pdf->Cell($basicInfoWidth, 10, 'Basic Information', 1, 0, 'C', 1);
            $x += $basicInfoWidth;
            
            $pdf->SetXY($x, $y);
            $pdf->Cell($healthConditionWidth, 10, 'HEALTH CONDITION', 1, 0, 'C', 1);
            $x += $healthConditionWidth;
            
            $pdf->SetXY($x, $y);
            $pdf->Cell($healthSanitationWidth, 10, 'HEALTH AND SANITATION', 1, 0, 'C', 1);
            $x += $healthSanitationWidth;
            
            $pdf->SetXY($x, $y);
            $pdf->Cell($wasteManagementWidth, 10, 'ZERO WASTE MANAGEMENT', 1, 0, 'C', 1);
            $x += $wasteManagementWidth;
            
            $pdf->SetXY($x, $y);
            $pdf->Cell($purokWidth, 10, 'Purok', 1, 0, 'C', 1);
            
            // Redraw sub-headers
            $y += 10;
            $x = 10;
            
            foreach ($basicInfoCols as $col) {
                $pdf->SetXY($x, $y);
                $pdf->Cell($col['width'], 10, $col['title'], 1, 0, 'C', 1);
                $x += $col['width'];
            }
            
            foreach ($healthConditionCols as $col) {
                $pdf->SetXY($x, $y);
                $pdf->Cell($col['width'], 10, $col['title'], 1, 0, 'C', 1);
                $x += $col['width'];
            }
            
            foreach ($healthSanitationCols as $col) {
                $pdf->SetXY($x, $y);
                $pdf->Cell($col['width'], 10, $col['title'], 1, 0, 'C', 1);
                $x += $col['width'];
            }
            
            foreach ($wasteManagementCols as $col) {
                $pdf->SetXY($x, $y);
                $pdf->Cell($col['width'], 10, $col['title'], 1, 0, 'C', 1);
                $x += $col['width'];
            }
            
            $pdf->SetXY($x, $y);
            $pdf->Cell($purokWidth, 10, 'No.', 1, 0, 'C', 1);
            
            $y += 10;
            $pdf->SetFont($fontFamily, '', 8);
            $pdf->SetFillColor(255, 255, 255);
        }
        
        $x = 10;
        
        // Basic Info
        foreach ($basicInfoCols as $i => $col) {
            $pdf->SetXY($x, $y);
            $value = '';
            switch ($i) {
                case 0: $value = $record['name']; break;
                case 1: $value = $record['birthday']; break;
                case 2: $value = $record['age']; break;
                case 3: $value = $record['gender']; break;
                case 4: $value = $record['civil_status']; break;
                case 5: $value = $record['occupation']; break;
            }
            $pdf->Cell($col['width'], 10, $value, 1, 0, 'L', 1);
            $x += $col['width'];
        }
        
        // Health Condition
        foreach ($healthConditionCols as $i => $col) {
            $pdf->SetXY($x, $y);
            $value = '';
            switch ($i) {
                case 0: $value = $record['sc'] ? 'Yes' : 'No'; break;
                case 1: $value = $record['pwd'] ? 'Yes' : 'No'; break;
                case 2: $value = $record['hypertension'] ? 'Yes' : 'No'; break;
                case 3: $value = $record['diabetes'] ? 'Yes' : 'No'; break;
                case 4: $value = $record['f_planning'] ? 'Yes' : 'No'; break;
                case 5: $value = $record['t_pregnancy'] ? 'Yes' : 'No'; break;
            }
            $pdf->Cell($col['width'], 10, $value, 1, 0, 'C', 1);
            $x += $col['width'];
        }
        
        // Health and Sanitation
        foreach ($healthSanitationCols as $i => $col) {
            $pdf->SetXY($x, $y);
            $value = '';
            switch ($i) {
                case 0: $value = $record['poso'] ? 'Yes' : 'No'; break;
                case 1: $value = $record['nawasa'] ? 'Yes' : 'No'; break;
                case 2: $value = $record['mineral'] ? 'Yes' : 'No'; break;
            }
            $pdf->Cell($col['width'], 10, $value, 1, 0, 'C', 1);
            $x += $col['width'];
        }
        
        // Waste Management
        foreach ($wasteManagementCols as $i => $col) {
            $pdf->SetXY($x, $y);
            $value = '';
            switch ($i) {
                case 0: $value = $record['segregation'] ? 'Yes' : 'No'; break;
                case 1: $value = $record['composition'] ? 'Yes' : 'No'; break;
            }
            $pdf->Cell($col['width'], 10, $value, 1, 0, 'C', 1);
            $x += $col['width'];
        }
        
        // Purok
        $pdf->SetXY($x, $y);
        $pdf->Cell($purokWidth, 10, $record['purok'], 1, 0, 'C', 1);
        
        $y += 10;
    }
    
    ob_end_clean();
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
    <title>Purok Selection Form - Export Options</title>
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
        <h1>Purok Selection Form - Export Options</h1>
        
        <form method="post" id="exportForm">
            <div class="export-options">
                <div class="option-group">
                    <h2><i class="fas fa-cog"></i> Basic Settings</h2>
                    <div class="form-group">
                        <label for="filename">Filename:</label>
                        <input type="text" id="filename" name="filename" value="purok_selection_data" required>
                    </div>
                    <div class="form-group">
                        <label for="format">Export Format:</label>
                        <select id="format" name="format" required onchange="updateOptions()">
                            <option value="xlsx">Excel (XLSX)</option>
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="purok">Select Purok:</label>
                        <select id="purok" name="purok" onchange="updatePreview()">
                            <option value="">All Puroks</option>
                            <?php foreach ($availablePuroks as $purok): ?>
                                <option value="<?php echo $purok; ?>">Purok <?php echo $purok; ?></option>
                            <?php endforeach; ?>
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
                <a href="purok_selection.php" class="button" style="margin-right: 10px;">
                    <i class="fas fa-arrow-left"></i> Back to Purok Selection Form
                </a>
                <button type="submit" name="export" class="button">
                    <i class="fas fa-file-export"></i> Export Data
                </button>
            </div>
        </form>

        <div class="preview-container">
            <div class="preview-header">
                <i class="fas fa-eye"></i>
                <h2>Preview (First 10 Rows of All Puroks)</h2>
            </div>
            <div class="table-wrapper">
                <div class="table-scroll-container">
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th colspan="6">Basic Information</th>
                                <th colspan="6">HEALTH CONDITION</th>
                                <th colspan="3">HEALTH AND SANITATION</th>
                                <th colspan="2">ZERO WASTE MANAGEMENT</th>
                                <th>Purok</th>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <th>Birthday</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Civil Status</th>
                                <th>Occupation</th>
                                <th>SC</th>
                                <th>PWD</th>
                                <th>Hypertension</th>
                                <th>Diabetes</th>
                                <th>F. Planning</th>
                                <th>T. Pregnancy</th>
                                <th>POSO</th>
                                <th>NAWASA</th>
                                <th>MINERAL</th>
                                <th>Segregation</th>
                                <th>Composition</th>
                                <th>No.</th>
                            </tr>
                        </thead>
                        <tbody id="previewBody">
                            <?php
                            // Get the first 10 rows for initial preview
                            $previewData = array_slice($data, 0, 10);
                            foreach ($previewData as $row):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['birthday']); ?></td>
                                <td><?php echo htmlspecialchars($row['age']); ?></td>
                                <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                <td><?php echo htmlspecialchars($row['civil_status']); ?></td>
                                <td><?php echo htmlspecialchars($row['occupation']); ?></td>
                                <td><?php echo $row['sc'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $row['pwd'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $row['hypertension'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $row['diabetes'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $row['f_planning'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $row['t_pregnancy'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $row['poso'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $row['nawasa'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $row['mineral'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $row['segregation'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo $row['composition'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo htmlspecialchars($row['purok']); ?></td>
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

        function updatePreview() {
            const selectedPurok = document.getElementById('purok').value;
            const previewBody = document.getElementById('previewBody');
            const allRows = <?php echo json_encode($data); ?>;
            
            let filteredRows = selectedPurok ? allRows.filter(row => row.purok == selectedPurok) : allRows;
            filteredRows = filteredRows.slice(0, 10); // Limit to 10 rows for preview
            
            let html = '';
            for (const row of filteredRows) {
                html += `
                    <tr>
                        <td>${escapeHtml(row.name)}</td>
                        <td>${escapeHtml(row.birthday)}</td>
                        <td>${escapeHtml(row.age)}</td>
                        <td>${escapeHtml(row.gender)}</td>
                        <td>${escapeHtml(row.civil_status)}</td>
                        <td>${escapeHtml(row.occupation)}</td>
                        <td>${row.sc ? 'Yes' : 'No'}</td>
                        <td>${row.pwd ? 'Yes' : 'No'}</td>
                        <td>${row.hypertension ? 'Yes' : 'No'}</td>
                        <td>${row.diabetes ? 'Yes' : 'No'}</td>
                        <td>${row.f_planning ? 'Yes' : 'No'}</td>
                        <td>${row.t_pregnancy ? 'Yes' : 'No'}</td>
                        <td>${row.poso ? 'Yes' : 'No'}</td>
                        <td>${row.nawasa ? 'Yes' : 'No'}</td>
                        <td>${row.mineral ? 'Yes' : 'No'}</td>
                        <td>${row.segregation ? 'Yes' : 'No'}</td>
                        <td>${row.composition ? 'Yes' : 'No'}</td>
                        <td>${escapeHtml(row.purok)}</td>
                    </tr>
                `;
            }
            previewBody.innerHTML = html;
        }

        function escapeHtml(unsafe) {
            return unsafe
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
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

            // Initial call to populate the preview
            updatePreview();
        });

        // Initial call to set the correct options visibility
        updateOptions();
    </script>
</body>
</html>