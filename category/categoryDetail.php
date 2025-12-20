<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth('Admin');

// --------------------------------------------------------------------
// Get categoryID from URL (edit mode) or null (add mode)
$categoryID = req('categoryID'); // matches your category.php edit links

// Default empty category (for Add mode)
$category = new stdClass();
$category->categoryName = '';
$category->description = '';

// --------------------------------------------------------------------
// If edit mode, fetch category from DB
if ($categoryID) {
    $stm = $_db->prepare('SELECT * FROM ProductCategory WHERE categoryID = ?');
    $stm->execute([$categoryID]);
    $cat = $stm->fetch(); // fetch as object

    if (!$cat) {
        temp('err', 'Category not found');
        redirect('category.php');
    }

    $category = $cat; // object
}

// --------------------------------------------------------------------
// Handle POST (both Add and Edit)
if (is_post()) {
    $name = req('name');
    $description = req('description');

    // Validation
    if ($name == '') {
        $_err['name'] = 'Category name is required';
    } else if (strlen($name) > 100) {
        $_err['name'] = 'Maximum 100 characters';
    }

    if (strlen($description) > 500) {
        $_err['description'] = 'Maximum 500 characters';
    }

    // DB operation if no errors
    if (!$_err) {
        if ($categoryID) {
            // Edit mode
            $stm = $_db->prepare('UPDATE ProductCategory SET categoryName = ?, description = ? WHERE categoryID = ?');
            $stm->execute([$name, $description, $categoryID]);
            temp('info', 'Category updated');
        } else {
            // Add mode
            $stm = $_db->prepare('INSERT INTO ProductCategory (categoryName, description) VALUES (?, ?)');
            $stm->execute([$name, $description]);
            temp('info', 'Category added');
        }

        redirect('category.php');
    }
}

// --------------------------------------------------------------------
// Prepare $GLOBALS for html_text / html_textarea helpers
$GLOBALS['name'] = $_POST['name'] ?? $category->categoryName;
$GLOBALS['description'] = $_POST['description'] ?? $category->description;

// --------------------------------------------------------------------
$_title = $categoryID ? 'Edit Category' : 'Add Category';
include '../navbar.php';
?>

<style>
form.form {
    max-width: 450px;
    margin: 50px auto;
    background: #fff;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    margin-top: 20px;
}

form input[type="text"],
form textarea {
    width: 95%;
    padding: 10px 14px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
}

form .err {
    color: #e74c3c;
    font-size: 0.85rem;
    margin-top: 4px;
}

form section {
    margin-top: 30px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

form button {
    padding: 10px 20px;
    border: none;
    background-color: #007BFF;
    color: white;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
}

form button[type="reset"] {
    background-color: #6c757d;
}
</style>

<form method="post" class="form">

    <label for="name">Category Name</label>
    <?= html_text('name', 'maxlength="100"') ?>
    <?= err('name') ?>

    <label for="description">Description</label>
    <?= html_textarea('description', 'maxlength="500"') ?>
    <?= err('description') ?>

    <section>
        <button><?= $categoryID ? 'Update' : 'Add' ?></button>
        <button type="reset">Reset</button>
    </section>
</form>

<?php include '../footer.php'; ?>
