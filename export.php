<?php
// export.php

session_start();
$invoices = $_SESSION['invoices'] ?? [];
$format = $_GET['format'] ?? 'csv';

if (!isset($invoices) || empty($invoices)) {
    die('No invoices available for export.');
}

if (isset($format) && $format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="invoices.csv"');

    $output = fopen('php://output', 'w');

    // Write the CSV header row
    fputcsv($output, ['Invoice Number', 'Issue Date', 'Customer Name', 'Amount excl. Tax', 'Tax Amount', 'Total Amount']);

    // Write each invoice row
    foreach ($invoices as $invoice) {
        fputcsv($output, [
            $invoice['number'],
            $invoice['date'],
            $invoice['customer'],
            $invoice['exclTax'],
            $invoice['tax'],
            $invoice['total']
        ]);
    }

    fclose($output);
    exit;
}

if ($format === 'pdf') {
    // Using DomPDF instead of FPDF
    require_once __DIR__ . '/vendor/autoload.php'; // Composer autoload restored, as vendor directory and autoload.php exist

    // Create new PDF instance
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->setPaper('A4', 'portrait');
    
    // Generate HTML content for PDF
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                color: #333;
            }
            h1 {
                color: #2c3e50;
                text-align: center;
                padding-bottom: 10px;
                border-bottom: 2px solid #3498db;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th {
                background-color: #3498db;
                color: white;
                font-weight: bold;
                padding: 8px;
                text-align: left;
                border: 1px solid #ddd;
            }
            td {
                padding: 8px;
                border: 1px solid #ddd;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            .total-row {
                font-weight: bold;
                background-color: #eaf2f8;
            }
            .footer {
                margin-top: 30px;
                text-align: center;
                color: #7f8c8d;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <h1>Invoice Summary</h1>
        <table>
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Issue Date</th>
                    <th>Customer</th>
                    <th>Amount excl. Tax</th>
                    <th>Tax</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>';
    
    $totalExclTax = 0;
    $totalTax = 0;
    $totalAmount = 0;
    
    foreach ($invoices as $invoice) {
        $html .= '<tr>
            <td>' . htmlspecialchars($invoice['number']) . '</td>
            <td>' . htmlspecialchars($invoice['date']) . '</td>
            <td>' . htmlspecialchars($invoice['customer']) . '</td>
            <td>€' . htmlspecialchars($invoice['exclTax']) . '</td>
            <td>€' . htmlspecialchars($invoice['tax']) . '</td>
            <td>€' . htmlspecialchars($invoice['total']) . '</td>
        </tr>';
        
        $totalExclTax += (float)$invoice['exclTax'];
        $totalTax += (float)$invoice['tax'];
        $totalAmount += (float)$invoice['total'];
    }
    
    $html .= '<tr class="total-row">
            <td colspan="3" style="text-align: right;">Totals:</td>
            <td>€' . number_format($totalExclTax, 2) . '</td>
            <td>€' . number_format($totalTax, 2) . '</td>
            <td>€' . number_format($totalAmount, 2) . '</td>
        </tr>
        </tbody>
        </table>
        <div class="footer">
            <p>Generated on ' . date('Y-m-d H:i:s') . '</p>
        </div>
    </body>
    </html>';
    
    // Load HTML content
    $dompdf->loadHtml($html);
    
    // Render the PDF
    $dompdf->render();
    
    // Output the PDF (attachment)
    $dompdf->stream("invoices.pdf", ["Attachment" => true]);
    exit;
}

exit('Unsupported export format.');