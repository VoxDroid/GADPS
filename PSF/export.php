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
function fetchData($selectedPurok = null) {
    global $db;
    $query = "SELECT * FROM purok_selection WHERE item_status = 'active'";
    if ($selectedPurok !== null) {
        $query .= " AND purok = :purok";
    }
    $stmt = $db->prepare($query);
    if ($selectedPurok !== null) {
        $stmt->bindValue(':purok', $selectedPurok, SQLITE3_INTEGER);
    }
    $results = $stmt->execute();
    $data = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }
    return $data;
}

$data = fetchData();

// Get available puroks
$availablePuroks = [];
foreach ($data as $row) {
    if (!in_array($row['purok'], $availablePuroks)) {
        $availablePuroks[] = $row['purok'];
    }
}
sort($availablePuroks);

// Handle AJAX request for preview update
if (isset($_GET['action']) && $_GET['action'] === 'update_preview') {
    $selectedPurok = isset($_GET['purok']) && $_GET['purok'] !== '' ? (int)$_GET['purok'] : null;
    $filteredData = fetchData($selectedPurok);
    $previewData = array_slice($filteredData, 0, 10);
    
    $html = '';
    foreach ($previewData as $row) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['birthday']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['age']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['gender']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['civil_status']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['occupation']) . '</td>';
        $html .= '<td>' . ($row['sc'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td>' . ($row['pwd'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td>' . ($row['hypertension'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td>' . ($row['diabetes'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td>' . ($row['f_planning'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td>' . ($row['t_pregnancy'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td>' . ($row['poso'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td>' . ($row['nawasa'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td>' . ($row['mineral'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td>' . ($row['segregation'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td>' . ($row['composition'] ? 'Yes' : 'No') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['purok']) . '</td>';
        $html .= '</tr>';
    }
    
    echo $html;
    exit;
}

// Handle export requests
if (isset($_POST['export'])) {
    $format = $_POST['format'];
    $filename = $_POST['filename'];
    $selectedPurok = isset($_POST['purok']) && $_POST['purok'] !== '' ? (int)$_POST['purok'] : null;
    
    $data = fetchData($selectedPurok);
    
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

$db->close();

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
    <?php include '../assets/html/disable-caching.html'; ?>
    <title>Purok Selection Form - Export Options</title>
    <?php include '../assets/html/icon.html'; ?>
    <?php include '../assets/html/styling.html'; ?>
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

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .preview-table th,
        .preview-table td {
            padding: 0.75rem;
            text-align: left;
            border: 1px solid var(--border-color);
        }

        .preview-table th {
            background-color: var(--accent-color);
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .preview-table tr:nth-child(even) {
            background-color: var(--background-color);
        }

        .preview-table tr:hover {
            background-color: var(--hover-color);
        }

        .horizontal-scroll {
            overflow-x: auto;
            overflow-y: hidden;
            height: 12px;
        }

        .horizontal-scroll-content {
            height: 1px;
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
    <?php include '../assets/html/header.html'; ?>

    <div class="container fade-in">
        <h1>Purok Selection Form - Export Options</h1>
        
        <form method="post" id="exportForm">
            <div class="export-options">
                <div class="option-group">
                    <h2><i class="fas fa-file-export"></i> Export Settings</h2>
                    <div class="form-group">
                        <label for="format">Export Format:</label>
                        <select id="format" name="format" onchange="updateOptions()">
                            <option value="xlsx">Excel (XLSX)</option>
                            <option value="csv">CSV</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filename">File Name:</label>
                        <input type="text" id="filename" name="filename" value="purok_selection_export" required>
                    </div>
                    <div class="form-group">
                        <label for="purok">Select Purok:</label>
                        <select id="purok" name="purok">
                            <option value="">All Puroks</option>
                            <?php foreach ($availablePuroks as $purok): ?>
                                <option value="<?php echo htmlspecialchars($purok); ?>"><?php echo htmlspecialchars($purok); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div id="spreadsheetOptions" class="option-group">
                    <h2><i class="fas fa-table"></i> Spreadsheet Options</h2>
                    <div class="form-group">
                        <label for="columnWidth">Column Width:</label>
                        <input type="number" id="columnWidth" name="columnWidth" value="15" min="5" max="50">
                    </div>
                    <div class="form-group">
                        <label for="headerColor">Header Color:</label>
                        <input type="color" id="headerColor" name="headerColor" value="#E2EFDA">
                    </div>
                </div>
                
                <div id="csvOptions" class="option-group" style="display: none;">
                    <h2><i class="fas fa-file-csv"></i> CSV Options</h2>
                    <div class="form-group">
                        <label for="delimiter">Delimiter:</label>
                        <select id="delimiter" name="delimiter">
                            <option value=",">Comma (,)</option>
                            <option value=";">Semicolon (;)</option>
                            <option value="\t">Tab (\t)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="enclosure">Enclosure:</label>
                        <select id="enclosure" name="enclosure">
                            <option value='"'>Double Quote (")</option>
                            <option value="'">Single Quote (')</option>
                        </select>
                    </div>
                </div>
                
                <div id="pdfOptions" class="option-group" style="display: none;">
                    <h2><i class="fas fa-file-pdf"></i> PDF Options</h2>
                    <div class="form-group">
                        <label for="customPageWidth">Page Width (mm):</label>
                        <input type="number" id="customPageWidth" name="customPageWidth" value="1188" min="100" max="1500">
                    </div>
                    <div class="form-group">
                        <label for="customPageHeight">Page Height (mm):</label>
                        <input type="number" id="customPageHeight" name="customPageHeight" value="420" min="100" max="1000">
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
                <h2>Preview (First 10 Rows)</h2>
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
                            <!-- Preview data will be dynamically inserted here -->
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
            
            previewBody.innerHTML = '<tr><td colspan="18" style="text-align: center;">Loading...</td></tr>';
            
            fetch(`export.php?action=update_preview&purok=${selectedPurok}`)
                .then(response => response.text())
                .then(html => {
                    previewBody.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    previewBody.innerHTML = '<tr><td colspan="18" style="text-align: center;">Error loading preview</td></tr>';
                });
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

            updatePreview();

            document.getElementById('purok').addEventListener('change', updatePreview);
        });

        updateOptions();
    </script>
</body>
</html>