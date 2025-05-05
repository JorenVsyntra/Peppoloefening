<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice UBL Processor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--dark-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            margin-bottom: 2rem;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: var(--secondary-color);
            color: white;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        .btn-success {
            background-color: #2ecc71;
            border-color: #2ecc71;
        }
        
        .btn-success:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        
        .btn-danger {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table thead {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            padding: 2rem;
            border: 2px dashed var(--primary-color);
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .file-upload-wrapper:hover {
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        
        .upload-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .file-status {
            margin-top: 1rem;
            font-style: italic;
            color: #7f8c8d;
        }
        
        .export-container {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 1rem;
        }
        
        .export-btn {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem 0;
            border-top: 1px solid #ddd;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-file-invoice"></i> UBL Invoice Processor</h1>
            <p class="lead">Upload, process, and export UBL XML invoice files</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-upload"></i> Upload Invoices</h3>
                    </div>
                    <div class="card-body">
                        <form action="upload.php" method="post" enctype="multipart/form-data" id="uploadForm">
                            <div class="file-upload-wrapper">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <h4>Drag & Drop Files Here</h4>
                                <p>or click to browse for UBL XML files</p>
                                <input type="file" name="ubl_files[]" multiple accept=".xml" class="file-upload-input" id="fileInput">
                                <div class="file-status" id="fileStatus">No files selected</div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-upload"></i> Process Invoices
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (isset($_SESSION['invoices']) && !empty($_SESSION['invoices'])): ?>
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-table"></i> Invoice Summary</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice Number</th>
                                        <th>Issue Date</th>
                                        <th>Customer Name</th>
                                        <th>Amount excl. Tax</th>
                                        <th>Tax</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalExclTax = 0;
                                    $totalTax = 0;
                                    $totalAmount = 0;
                                    
                                    foreach ($_SESSION['invoices'] as $invoice): 
                                        $totalExclTax += (float)$invoice['exclTax'];
                                        $totalTax += (float)$invoice['tax'];
                                        $totalAmount += (float)$invoice['total'];
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($invoice['number']) ?></td>
                                            <td><?= htmlspecialchars($invoice['date']) ?></td>
                                            <td><?= htmlspecialchars($invoice['customer']) ?></td>
                                            <td>€<?= htmlspecialchars($invoice['exclTax']) ?></td>
                                            <td>€<?= htmlspecialchars($invoice['tax']) ?></td>
                                            <td>€<?= htmlspecialchars($invoice['total']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <tr class="table-primary fw-bold">
                                        <td colspan="3" class="text-end">Totals:</td>
                                        <td>€<?= number_format($totalExclTax, 2) ?></td>
                                        <td>€<?= number_format($totalTax, 2) ?></td>
                                        <td>€<?= number_format($totalAmount, 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="export-container">
                            <a href="export.php?format=csv" class="btn btn-success export-btn">
                                <i class="fas fa-file-csv"></i> Export as CSV
                            </a>
                            <a href="export.php?format=pdf" class="btn btn-danger export-btn">
                                <i class="fas fa-file-pdf"></i> Export as PDF
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>UBL Invoice Processor &copy; <?= date('Y') ?></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const fileCount = e.target.files.length;
            let fileStatus = document.getElementById('fileStatus');
            
            if (fileCount > 0) {
                fileStatus.textContent = fileCount + (fileCount === 1 ? ' file selected' : ' files selected');
                fileStatus.style.color = '#2ecc71';
            } else {
                fileStatus.textContent = 'No files selected';
                fileStatus.style.color = '#7f8c8d';
            }
        });
        
        // Make the entire upload area clickable
        document.querySelector('.file-upload-wrapper').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });
        
        <?php if (isset($_SESSION['invoices'])): ?>
        const invoiceData = <?php echo json_encode($_SESSION['invoices']); ?>;
        sessionStorage.setItem('invoices', JSON.stringify(invoiceData));
        <?php endif; ?>
    </script>
</body>
</html>