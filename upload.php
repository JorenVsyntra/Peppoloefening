<?php
// upload.php
session_start();

// Create uploads directory if it doesn't exist
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$response = [
    'success' => false,
    'message' => '',
    'processed' => 0,
    'failed' => 0
];

if (isset($_FILES['ubl_files'])) {
    $uploadedFiles = $_FILES['ubl_files'];
    $invoices = [];
    $processedCount = 0;
    $failedCount = 0;

    for ($i = 0; $i < count($uploadedFiles['name']); $i++) {
        if ($uploadedFiles['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $uploadedFiles['tmp_name'][$i];
            $fileName = basename($uploadedFiles['name'][$i]);
            
            // Sanitize filename
            $fileName = preg_replace("/[^a-zA-Z0-9._-]/", "", $fileName);
            $destination = $uploadDir . $fileName;

            if (move_uploaded_file($tmpName, $destination)) {
                try {
                    // Make sure the file is a valid XML file
                    $xml = @simplexml_load_file($destination);
                    
                    if ($xml === false) {
                        $failedCount++;
                        continue;
                    }
                    
                    // Register namespaces
                    $namespaces = $xml->getNamespaces(true);
                    
                    // Check if required namespaces exist
                    if (!isset($namespaces['cbc']) || !isset($namespaces['cac'])) {
                        $failedCount++;
                        continue;
                    }
                    
                    $cbc = $xml->children($namespaces['cbc']);
                    $cac = $xml->children($namespaces['cac']);

                    // Basic info
                    $invoiceNumber = (string) $cbc->ID;
                    $issueDate = (string) $cbc->IssueDate;

                    // Format the date if it's in YYYY-MM-DD format
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $issueDate)) {
                        $date = new DateTime($issueDate);
                        $issueDate = $date->format('d-m-Y');
                    }

                    // Customer name
                    $xmlCustomerName = $xml->xpath('//cac:AccountingCustomerParty/cac:Party/cac:PartyName/cbc:Name');
                    $customerName = isset($xmlCustomerName[0]) ? (string)$xmlCustomerName[0] : 'Unknown';

                    // Total excluding tax
                    $xmlExlTax = $xml->xpath('//cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount');
                    $totalExcludingTax = isset($xmlExlTax[0]) ? (float)$xmlExlTax[0] : 0;

                    // Total
                    $xmlTotalAmount = $xml->xpath('//cac:LegalMonetaryTotal/cbc:PayableAmount');
                    $totalAmount = isset($xmlTotalAmount[0]) ? (float)$xmlTotalAmount[0] : 0;

                    // Tax amount
                    $taxAmount = $totalAmount - $totalExcludingTax;

                    // Currency
                    $xmlCurrency = $xml->xpath('//cbc:DocumentCurrencyCode');
                    $currency = isset($xmlCurrency[0]) ? (string)$xmlCurrency[0] : 'EUR';

                    // Supplier information
                    $xmlSupplierName = $xml->xpath('//cac:AccountingSupplierParty/cac:Party/cac:PartyName/cbc:Name');
                    $supplierName = isset($xmlSupplierName[0]) ? (string)$xmlSupplierName[0] : 'Unknown';

                    $invoices[] = [
                        'number' => (string) $invoiceNumber,
                        'date' => (string) $issueDate,
                        'customer' => (string) $customerName,
                        'supplier' => (string) $supplierName,
                        'exclTax' => number_format($totalExcludingTax, 2),
                        'tax' => number_format($taxAmount, 2),
                        'total' => number_format($totalAmount, 2),
                        'currency' => (string) $currency
                    ];

                    $processedCount++;
                } catch (Exception $e) {
                    $failedCount++;
                    // You could log the error here if needed
                }
            } else {
                $failedCount++;
            }
        } else {
            $failedCount++;
        }
    }

    // Store the invoices in the session
    $_SESSION['invoices'] = $invoices;

    if ($processedCount > 0) {
        $response['success'] = true;
        $response['message'] = "Successfully processed $processedCount files.";
        if ($failedCount > 0) {
            $response['message'] .= " Failed to process $failedCount files.";
        }
    } else {
        $response['message'] = "Failed to process any files. Please check your XML format.";
    }

    $response['processed'] = $processedCount;
    $response['failed'] = $failedCount;

    // Redirect back to the index page with success parameters
    header('Location: index.php?' . ($processedCount > 0 ? 'success=1' : 'error=1') . 
           '&processed=' . $processedCount . 
           '&failed=' . $failedCount);
    exit;
} else {
    // Handle the case where no files were uploaded
    header('Location: index.php?error=1&message=' . urlencode('No files were uploaded.'));
    exit;
}