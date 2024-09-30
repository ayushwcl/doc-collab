<?php
include 'includes/db.php'; // Include this to connect to the database
include 'includes/functions.php'; 
include 'includes/header.php'; 

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$uploadDir = 'uploads/'; // Directory where merged files are stored

// Handle PDF upload and merging logic
if (isset($_POST['merge_pdf'])) {
    // PDF merging logic remains the same
    if (isset($_FILES['pdfs']) && !empty($_FILES['pdfs']['name'][0])) {
        $pdfFiles = $_FILES['pdfs'];
        $totalFiles = count($pdfFiles['name']);

        if ($totalFiles > 1) {
            require_once('vendor/autoload.php'); // Load FPDI and FPDF

            // Create the uploads directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true); // Create directory with appropriate permissions
            }

            // Create a new FPDI object
            $pdf = new \setasign\Fpdi\Fpdi();

            // Loop through each uploaded PDF and add them to the new document
            for ($i = 0; $i < $totalFiles; $i++) {
                $filePath = $pdfFiles['tmp_name'][$i];

                if (file_exists($filePath)) {
                    $pageCount = $pdf->setSourceFile($filePath);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $tplIdx = $pdf->importPage($pageNo);
                        $pdf->AddPage();
                        $pdf->useTemplate($tplIdx);
                    }
                } else {
                    echo "<div class='alert alert-danger'>File " . $pdfFiles['name'][$i] . " does not exist or cannot be read.</div>";
                    break;
                }
            }

            // Output the merged PDF to a file in the uploads directory
            $mergedFilePath = $uploadDir . 'merged_document_' . time() . '.pdf';
            $pdf->Output($mergedFilePath, 'F');

            // Save the merged PDF file to the database
            $stmt = $pdo->prepare("INSERT INTO merged_files (username, file_path) VALUES (:username, :file_path)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':file_path', $mergedFilePath);
            $stmt->execute();

            echo "<div class='alert alert-success'>PDFs merged successfully. <a href='$mergedFilePath' target='_blank'>Download Merged PDF</a></div>";
        } else {
            echo "<div class='alert alert-warning'>Please select at least two PDF files to merge.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>No PDF files were selected. Please try again.</div>";
    }
}


// Handle DOCX upload and merging logic
if (isset($_POST['merge_docx'])) {
    if (isset($_FILES['docx']) && !empty($_FILES['docx']['name'][0])) {
        $docxFiles = $_FILES['docx'];
        $totalFiles = count($docxFiles['name']);

        if ($totalFiles > 1) {
            require_once('vendor/autoload.php'); // Load PHPWord

            $phpWord = new \PhpOffice\PhpWord\PhpWord();

            // Create the uploads directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true); // Create directory with appropriate permissions
            }

            // Loop through each uploaded DOCX and add them to the new document
            for ($i = 0; $i < $totalFiles; $i++) {
                $filePath = $docxFiles['tmp_name'][$i];

                if (file_exists($filePath)) {
                    $content = \PhpOffice\PhpWord\IOFactory::load($filePath);
                    foreach ($content->getSections() as $section) {
                        // Create a new section for each section in the document
                        $newSection = $phpWord->addSection();
                        foreach ($section->getElements() as $element) {
                            // Check the type of the element and handle accordingly
                            if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                                // Loop through each text run and add text to the new section
                                foreach ($element->getElements() as $textElement) {
                                    if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                                        // Add the text along with its styles
                                        $newSection->addText($textElement->getText(), $textElement->getFontStyle(), $textElement->getParagraphStyle());
                                    } elseif ($textElement instanceof \PhpOffice\PhpWord\Element\TextBreak) {
                                        $newSection->addTextBreak();
                                    }
                                }
                            } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextBreak) {
                                $newSection->addTextBreak();
                            } elseif ($element instanceof \PhpOffice\PhpWord\Element\Image) {
                                // Handle image elements if necessary
                                $newSection->addImage($element->getSource(), $element->getStyle());
                            }
                            // Add more conditions here for other types of elements as needed
                        }
                    }
                } else {
                    echo "<div class='alert alert-danger'>File " . htmlspecialchars($docxFiles['name'][$i]) . " does not exist or cannot be read.</div>";
                    break;
                }
            }

            // Output the merged DOCX to a file in the uploads directory
            $mergedDocxPath = $uploadDir . 'merged_document_' . time() . '.docx';
            $phpWord->save($mergedDocxPath, 'Word2007');

            // Save the merged DOCX file to the database
            $stmt = $pdo->prepare("INSERT INTO merged_files (username, file_path) VALUES (:username, :file_path)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':file_path', $mergedDocxPath);
            $stmt->execute();

            echo "<div class='alert alert-success'>DOCXs merged successfully. <a href='$mergedDocxPath' target='_blank'>Download Merged DOCX</a></div>";
        } else {
            echo "<div class='alert alert-warning'>Please select at least two DOCX files to merge.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>No DOCX files were selected. Please try again.</div>";
    }
}




// Handle CSV upload and merging logic
if (isset($_POST['merge_csv'])) {
    if (isset($_FILES['csvs']) && !empty($_FILES['csvs']['name'][0])) {
        $csvFiles = $_FILES['csvs'];
        $totalFiles = count($csvFiles['name']);

        if ($totalFiles > 1) {
            $mergedCsvPath = $uploadDir . 'merged_document_' . time() . '.csv';
            $outputFile = fopen($mergedCsvPath, 'w');

            // Loop through each uploaded CSV and add them to the new document
            for ($i = 0; $i < $totalFiles; $i++) {
                $filePath = $csvFiles['tmp_name'][$i];

                if (file_exists($filePath)) {
                    if (($handle = fopen($filePath, 'r')) !== FALSE) {
                        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                            fputcsv($outputFile, $data);
                        }
                        fclose($handle);
                    }
                } else {
                    echo "<div class='alert alert-danger'>File " . $csvFiles['name'][$i] . " does not exist or cannot be read.</div>";
                    break;
                }
            }
            fclose($outputFile);

            // Save the merged CSV file to the database
            $stmt = $pdo->prepare("INSERT INTO merged_files (username, file_path) VALUES (:username, :file_path)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':file_path', $mergedCsvPath);
            $stmt->execute();

            echo "<div class='alert alert-success'>CSV files merged successfully. <a href='$mergedCsvPath' target='_blank'>Download Merged CSV</a></div>";
        } else {
            echo "<div class='alert alert-warning'>Please select at least two CSV files to merge.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>No CSV files were selected. Please try again.</div>";
    }
}

// Handle TXT upload and merging logic
if (isset($_POST['merge_txt'])) {
    if (isset($_FILES['txts']) && !empty($_FILES['txts']['name'][0])) {
        $txtFiles = $_FILES['txts'];
        $totalFiles = count($txtFiles['name']);

        if ($totalFiles > 1) {
            $mergedTxtPath = $uploadDir . 'merged_document_' . time() . '.txt';
            $outputFile = fopen($mergedTxtPath, 'w');

            // Loop through each uploaded TXT and add them to the new document
            for ($i = 0; $i < $totalFiles; $i++) {
                $filePath = $txtFiles['tmp_name'][$i];

                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                    fwrite($outputFile, $content . PHP_EOL);
                } else {
                    echo "<div class='alert alert-danger'>File " . $txtFiles['name'][$i] . " does not exist or cannot be read.</div>";
                    break;
                }
            }
            fclose($outputFile);

            // Save the merged TXT file to the database
            $stmt = $pdo->prepare("INSERT INTO merged_files (username, file_path) VALUES (:username, :file_path)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':file_path', $mergedTxtPath);
            $stmt->execute();

            echo "<div class='alert alert-success'>TXT files merged successfully. <a href='$mergedTxtPath' target='_blank'>Download Merged TXT</a></div>";
        } else {
            echo "<div class='alert alert-warning'>Please select at least two TXT files to merge.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>No TXT files were selected. Please try again.</div>";
    }
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $fileToDelete = $uploadDir . basename($_GET['delete']);
    if (file_exists($fileToDelete)) {
        unlink($fileToDelete);
        // Delete file record from the database
        $stmt = $pdo->prepare("DELETE FROM merged_files WHERE file_path = :file_path AND username = :username");
        $stmt->bindParam(':file_path', $fileToDelete);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        echo "<div class='alert alert-success'>File deleted successfully.</div>";
    } else {
        echo "<div class='alert alert-danger'>File not found.</div>";
    }
}

// List all merged files for the current user
$stmt = $pdo->prepare("SELECT * FROM merged_files WHERE username = :username");
$stmt->bindParam(':username', $username);
$stmt->execute();
$mergedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <h2 class="my-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>Here's your dashboard where you can manage your account and projects.</p>

    <!-- Form to upload and merge PDFs -->
    <form action="dashboard.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="pdfs">Select PDF files to merge:</label>
            <input type="file" name="pdfs[]" id="pdfs" class="form-control" multiple accept="application/pdf" required>
        </div>
        <button type="submit" name="merge_pdf" class="btn btn-primary mt-2">Merge PDFs</button>
    </form>

    <!-- Form to upload and merge DOCX -->
    <form action="dashboard.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="docx">Select DOCX files to merge:</label>
            <input type="file" name="docx[]" id="docx" class="form-control" multiple accept=".docx" required>
        </div>
        <button type="submit" name="merge_docx" class="btn btn-primary mt-2">Merge DOCXs</button>
    </form>

    <!-- Form to upload and merge CSV -->
    <form action="dashboard.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="csvs">Select CSV files to merge:</label>
            <input type="file" name="csvs[]" id="csvs" class="form-control" multiple accept=".csv" required>
        </div>
        <button type="submit" name="merge_csv" class="btn btn-primary mt-2">Merge CSVs</button>
    </form>

    <!-- Form to upload and merge TXT -->
    <form action="dashboard.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="txts">Select TXT files to merge:</label>
            <input type="file" name="txts[]" id="txts" class="form-control" multiple accept=".txt" required>
        </div>
        <button type="submit" name="merge_txt" class="btn btn-primary mt-2">Merge TXTs</button>
    </form>

    <hr>

    <h3>Merged Files</h3>
    <?php if (!empty($mergedFiles)): ?>
        <ul class="list-group">
            <?php foreach ($mergedFiles as $file): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?php echo htmlspecialchars(basename($file['file_path'])); ?>
                    <span>
                        <a href="<?php echo $file['file_path']; ?>" class="btn btn-sm btn-success" download>Download</a>
                        <a href="dashboard.php?delete=<?php echo urlencode(basename($file['file_path'])); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this file?');">Delete</a>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No merged files found.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
