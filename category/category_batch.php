<?php
include '../_base.php';
auth("Admin","Superadmin");

require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$_title = 'Batch Category Upload';

// --- File processing code (keep as is) ---
if (is_post()) {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        temp('err', 'Please upload a valid file');
        redirect();
    }

    $file = $_FILES['file']['tmp_name'];
    $ext  = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    $rows = [];

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

<style>
/* Container for form */
.form-container {
    max-width: 500px;
    margin: 50px auto;
    background: #fff;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    text-align: center;
}

.form-container h2 {
    margin-bottom: 20px;
    font-size: 1.8rem;
    color: #333;
}

.form-container p {
    font-size: 0.95rem;
    color: #555;
    margin-bottom: 20px;
}

.form-container input[type="file"] {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    margin-bottom: 20px;
}

.form-container .btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    margin: 5px;
    font-size: 1rem;
}

.btn-upload { background: #007BFF; color: white; }
.btn-cancel { background: #6c757d; color: white; }
.btn-template { background: #17a2b8; color: white; }
</style>

<div class="form-container">
    <h2>Batch Category Upload</h2>
    
    <a href="../templates/category_template.xlsx" class="btn btn-template" download>
        Download Excel Template
    </a>
    
    <p>Please use the provided Excel template.<br>Do not rename or remove the header row.</p>
    
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="file" accept=".xlsx,.xls,.csv" required>
        <div>
            <button type="submit" class="btn btn-upload">Upload</button>
            <a href="category.php" class="btn btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<?php include '../footer.php'; ?>
