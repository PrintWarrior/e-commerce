<?php
require_once '../includes/functions.php';
require_once 'seller_header.php';

$seller_id = $seller['seller_id'];
$product_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Get product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
$stmt->execute([$product_id, $seller_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
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
    
    $image_name = $product['image'];
    
    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "../product_images/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $new_image_name = time() . '_' . basename($_FILES['product_image']['name']);
        $target_file = $target_dir . $new_image_name;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $check = getimagesize($_FILES['product_image']['tmp_name']);
        
        if ($check === false) {
            $errors[] = "File is not an image.";
        } elseif (!in_array($image_file_type, $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } elseif ($_FILES['product_image']['size'] > 5000000) {
            $errors[] = "File is too large. Max 5MB allowed.";
        } elseif (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
            // Delete old image if not default
            if ($product['image'] != 'default.jpg' && file_exists($target_dir . $product['image'])) {
                unlink($target_dir . $product['image']);
            }
            $image_name = $new_image_name;
        } else {
            $errors[] = "Failed to upload image.";
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ? AND seller_id = ?");
        if ($stmt->execute([$category_id, $name, $description, $price, $stock, $image_name, $product_id, $seller_id])) {
            logSystemEvent(
                'seller_product_updated',
                'products',
                (int) $product_id,
                "Seller {$seller['business_name']} updated product '{$name}'."
            );
            $success = "Product updated successfully!";
            // Refresh product data
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
            $stmt->execute([$product_id, $seller_id]);
            $product = $stmt->fetch();
        } else {
            $error = "Failed to update product.";
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>

<div class="edit-product">
    <div class="page-header">
        <h1>Edit Product</h1>
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
            <input type="text" id="name" name="name" required value="<?= htmlspecialchars($product['name']) ?>">
        </div>
        
        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">Select a category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="price">Price (₱) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required value="<?= $product['price'] ?>">
            </div>
            
            <div class="form-group">
                <label for="stock">Stock Quantity *</label>
                <input type="number" id="stock" name="stock" min="0" required value="<?= $product['stock'] ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5"><?= htmlspecialchars($product['description']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Current Image</label>
            <div class="current-image">
                <img src="../product_images/<?= htmlspecialchars($product['image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     style="max-width: 200px; max-height: 200px;">
            </div>
        </div>
        
        <div class="form-group">
            <label for="product_image">Change Image (optional)</label>
            <input type="file" id="product_image" name="product_image" accept="image/*">
            <p class="info-note">Supported formats: JPG, JPEG, PNG, GIF. Max size: 5MB</p>
        </div>
        
        <button type="submit" name="update_product" class="btn-primary">Update Product</button>
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
