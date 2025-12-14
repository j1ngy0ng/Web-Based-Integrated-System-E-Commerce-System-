<?php
include '../_base.php';
auth();

require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$_title = 'Batch Category Upload';

if (is_post()) {

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        temp('err', 'Please upload a valid file');
        redirect();
    }

$file = $_FILES['file']['tmp_name'];
$ext  = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);


    $rows = [];

    if (in_array(strtolower($ext), ['xlsx', 'xls'])) {
        // Excel file
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

    } elseif (strtolower($ext) === 'csv') {
        // CSV file
        if (($handle = fopen($file, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
    } else {
        temp('err', 'Unsupported file type');
        redirect();
    }

    // -------------------------------
    // Insert rows into DB
    $stm = $_db->prepare('INSERT INTO ProductCategory (categoryName, description) VALUES (?, ?)');
    $count = 0;

    $_db->beginTransaction();

    foreach ($rows as $index => $row) {
        if ($index === 0) continue; // skip header

        $name = trim($row[0] ?? '');
        $desc = trim($row[1] ?? '');

        if ($name === '' || strlen($name) > 100) continue;
        if (strlen($desc) > 500) continue;

        $stm->execute([$name, $desc]);
        $count++;
    }

    $_db->commit();

    temp('info', "$count categories inserted successfully");
    redirect('category.php');
}

include '../navbar.php'; 
?>

<div class="actions">
    <a href="../templates/category_template.xlsx"
       class="btn btn-secondary"
       download>
        Download Excel Template
    </a>
</div>

<p style="margin: 10px 0; font-size: 0.9rem; color: #555;">
    Please use the provided Excel template.
    Do not rename or remove the header row.
</p>


<form method="post" enctype="multipart/form-data" class="form">
    <label>Select Excel or CSV File</label>
    <input type="file" name="file" accept=".xlsx,.xls,.csv" required>

    <section>
        <button>Upload</button>
        <a href="category.php" class="btn">Cancel</a>
    </section>
</form>


<?php include '../footer.php'; ?>
