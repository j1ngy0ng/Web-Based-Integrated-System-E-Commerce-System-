<?php
include '../_base.php';
auth();

require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$_title = 'Batch Category Update';

if (is_post()) {

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        temp('err', 'Please upload a valid file');
        redirect();
    }

    $file = $_FILES['file']['tmp_name'];
    $ext  = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $rows = [];

    // Load Excel or CSV
    if (in_array(strtolower($ext), ['xlsx', 'xls'])) {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
    } elseif (strtolower($ext) === 'csv') {
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
    // Update rows in DB
    $stm = $_db->prepare('UPDATE ProductCategory SET categoryName = ?, description = ? WHERE categoryID = ?');
    $count = 0;
    $_db->beginTransaction();

    foreach ($rows as $index => $row) {
        if ($index === 0) continue; // skip header

        $id   = intval($row[0] ?? 0);
        $name = trim($row[1] ?? '');
        $desc = trim($row[2] ?? '');

        // Skip invalid rows
        if ($id <= 0 || $name === '' || strlen($name) > 100 || strlen($desc) > 500) continue;

        $stm->execute([$name, $desc, $id]);
        $count++;
    }

    $_db->commit();
    temp('info', "$count categories updated successfully");
    redirect('category.php');
}

include '../navbar.php';
?>

<div class="actions">
    <a href="../templates/category_update_template.xlsx"
       class="btn btn-secondary"
       download>
        Download Update Template
    </a>
</div>

<p style="margin: 10px 0; font-size: 0.9rem; color: #555;">
    Please use the provided template for batch updating.
    Do not rename or remove the header row and category IDs.
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
