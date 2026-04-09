<?php
require_once '../includes/functions.php';
require_once 'seller_header.php';

$seller_id = $seller['seller_id'];
$error = '';
$success = '';

// Get categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = "Product name is required.";
    if ($price <= 0) $errors[] = "Valid price is required.";
    if ($stock < 0) $errors[] = "Stock cannot be negative.";
    
    // Handle image upload
    $image_name = 'default.jpg';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "../product_images/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image_name = time() . '_' . basename($_FILES['product_image']['name']);
        $target_file = $target_dir . $image_name;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $check = getimagesize($_FILES['product_image']['tmp_name']);
        
        if ($check === false) {
            $errors[] = "File is not an image.";
        } elseif (!in_array($image_file_type, $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } elseif ($_FILES['product_image']['size'] > 5000000) {
            $errors[] = "File is too large. Max 5MB allowed.";
        } elseif (!move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            $errors[] = "Failed to upload image.";
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO products (seller_id, category_id, name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$seller_id, $category_id, $name, $description, $price, $stock, $image_name])) {
            $newProductId = (int) $pdo->lastInsertId();
            logSystemEvent(
                'seller_product_created',
                'products',
                $newProductId,
                "Seller {$seller['business_name']} added product '{$name}'."
            );
            $success = "Product added successfully!";
            // Clear form
            $_POST = [];
        } else {
            $error = "Failed to add product.";
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<div class="add-product">
    <div class="page-header">
        <h1>Add New Product</h1>
        <a href="products.php" class="btn-secondary">← Back to Products</a>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" class="product-form">
        <div class="form-group">
            <label for="name">Product Name *</label>
            <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        
        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">Select a category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="price">Price (₱) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="stock">Stock Quantity *</label>
                <input type="number" id="stock" name="stock" min="0" required value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="product_image">Product Image</label>
            <input type="file" id="product_image" name="product_image" accept="image/*">
            <p class="info-note">Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB</p>
        </div>
        
        <button type="submit" name="add_product" class="btn-primary">Add Product</button>
    </form>
</div>

<footer class="seller-footer">
            <div class="footer-inner">
                <p class="footer-copy">Copyright &copy; 2025 All Rights Reserved by <span>Beauty Mart.</span></p>
                <div class="footer-socials">
                    <a href="#" title="Facebook">f</a>
                    <a href="#" title="Twitter">t</a>
                    <a href="#" title="Website">🌐</a>
                    <a href="#" title="LinkedIn">in</a>
                </div>
            </div>
        </footer>
