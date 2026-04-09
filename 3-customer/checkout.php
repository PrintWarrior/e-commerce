<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) redirect('../login.php');

$user_id = $_SESSION['user_id'];

$customer_id = getCustomerId($user_id);
if (!$customer_id) {
    $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    $customer_id = $pdo->lastInsertId();
}

// Get user details
$stmt = $pdo->prepare("
    SELECT u.*, c.id as customer_id, c.phone, c.barangay, c.municipality, c.province, c.zip_code, c.address_details
    FROM users u 
    LEFT JOIN customers c ON u.id = c.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get cart items with product details
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.product_id, c.quantity,
           p.name, p.price, p.image, p.stock
    FROM carts c
    JOIN products p ON c.product_id = p.id
    WHERE c.customer_id = ?
");
$stmt->execute([$customer_id]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) redirect('cart.php');

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart_items));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? 'cod';

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount, status, payment_method) VALUES (?, ?, 'pending', ?)");
        $stmt->execute([$customer_id, $total, $payment_method]);
        $order_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart_items as $item) {
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            // Decrement stock
            $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?")
                ->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
        }

        $pdo->prepare("DELETE FROM carts WHERE customer_id = ?")->execute([$customer_id]);
        logSystemEvent(
            'customer_checkout',
            'orders',
            (int) $order_id,
            "Customer {$_SESSION['username']} placed order #{$order_id} with " . count($cart_items) . " item(s), total PHP " . number_format((float) $total, 2) . "."
        );
        $pdo->commit();

        $_SESSION['checkout_success'] = "Order #$order_id placed successfully!";
        header("Location: orders.php"); exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Checkout failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer_checkout.css">
    <link rel="icon" href="../images/logo.png" type="image/png">
</head>
<body>

    <!-- Top strip -->
    <div class="top-strip"></div>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="inner">
            <div class="left">
                <a href="../about.php">About Us</a>
                <span class="sep">|</span>
                <a href="../contact.php">Contact Us</a>
            </div>
            <div class="right">
                <a href="profile.php">
                    <div class="avatar-sm">
                        <?php if (!empty($user['profile_pic']) && file_exists("../uploads/profile_images/" . $user['profile_pic'])): ?>
                            <img src="../uploads/profile_images/<?= htmlspecialchars($user['profile_pic']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                </a>
                Welcome, <?= htmlspecialchars($_SESSION['username']) ?>
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="inner">
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">
                    <img src="../images/logo.png" alt="Logo"
                         onerror="this.style.display='none';this.parentElement.textContent='🌸'">
                </div>
                <div class="logo-text"><span>Beauty</span><span>Mart</span></div>
            </a>
            <div class="nav-spacer"></div>
            <div class="nav-icons">
                <a href="dashboard.php" title="Home">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/>
                        <polyline points="9 21 9 12 15 12 15 21"/>
                    </svg>
                </a>
                <a href="orders.php" title="Orders">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <path d="M8 21h8M12 17v4"/>
                    </svg>
                </a>
                <a href="wishlist.php" title="Wishlist">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </a>
                <a href="cart.php" title="Cart">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- Page -->
    <div class="page">
        <div class="page-inner">

            <!-- Title row -->
            <div class="page-title-row">
                <h1>Checkout</h1>
                <a href="dashboard.php" class="btn-back">‹ Dashboard</a>
            </div>

            <?php if ($error): ?>
                <div class="alert-error">⚠ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="payment_method" id="payment_method_input" value="cod">

                <div class="checkout-grid">

                    <!-- ── Left: Address / Contact / Payment ── -->
                    <div class="left-col">

                        <!-- Address panel -->
                        <div class="info-panel">
                            <div class="panel-header">
                                <h2>Address</h2>
                                <a href="profile.php" class="btn-edit">Edit</a>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Full Name:</span>
                                <span class="info-val"><?= htmlspecialchars(trim($user['firstname'] . ' ' . $user['lastname'])) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Barangay:</span>
                                <span class="info-val"><?= htmlspecialchars($user['barangay'] ?? '') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Municipality:</span>
                                <span class="info-val"><?= htmlspecialchars($user['municipality'] ?? '') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Province:</span>
                                <span class="info-val"><?= htmlspecialchars($user['province'] ?? '') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Zip Code:</span>
                                <span class="info-val"><?= htmlspecialchars($user['zip_code'] ?? '') ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Address Details:</span>
                                <span class="info-val"><?= htmlspecialchars($user['address_details'] ?? '') ?></span>
                            </div>
                        </div>

                        <!-- Contact info panel -->
                        <div class="info-panel">
                            <div class="panel-header">
                                <h2>Contact Info</h2>
                                <a href="profile.php" class="btn-edit">Edit</a>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-val"><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone:</span>
                                <span class="info-val"><?= htmlspecialchars($user['phone'] ?? '') ?></span>
                            </div>
                        </div>

                        <!-- Payment method panel -->
                        <div class="info-panel">
                            <div class="panel-header">
                                <h2>Payment Method</h2>
                            </div>
                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" name="payment_choice" value="cod" checked
                                           onchange="document.getElementById('payment_method_input').value=this.value">
                                    Cash on Delivery
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_choice" value="pickup"
                                           onchange="document.getElementById('payment_method_input').value=this.value">
                                    Pickup
                                </label>
                            </div>
                        </div>

                    </div>

                    <!-- ── Right: Order summary ────────────── -->
                    <div class="right-col">
                        <div class="order-panel">

                            <!-- Place Order button -->
                            <button type="submit" class="place-order-btn">Place Order</button>

                            <!-- Policy -->
                            <div class="policy-text">
                                By placing your order, you agree to our
                                <a href="#">Privacy Policy</a> and
                                <a href="#">Conditions of Use</a>
                            </div>

                            <!-- Order Summary heading -->
                            <div class="order-summary-title">Order Summary</div>

                            <!-- Items -->
                            <div class="order-items">
                                <?php foreach ($cart_items as $item): ?>
                                <div class="order-item">
                                    <div class="item-thumb">
                                        <?php if (!empty($item['image']) && file_exists("../product_images/" . $item['image'])): ?>
                                            <img src="../product_images/<?= htmlspecialchars($item['image']) ?>"
                                                 alt="<?= htmlspecialchars($item['name']) ?>">
                                        <?php else: ?>
                                            🛍️
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-details">
                                        <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>
                                        <div class="item-meta">
                                            Qty: <?= $item['quantity'] ?><br>
                                            Price: ₱<?= number_format($item['price'], 2) ?>
                                        </div>
                                    </div>
                                    <div class="item-price">
                                        ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Total -->
                            <div class="order-total-row">
                                <span class="order-total-label">Order Total</span>
                                <span class="order-total-val">₱<?= number_format($total, 2) ?></span>
                            </div>

                        </div>
                    </div>

                </div>
            </form>

        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="inner">
            <p class="copy">Copyright &copy; 2025 All Rights Reserved by <span>Beauty Mart.</span></p>
            <div class="socials">
                <a href="#" title="Facebook">f</a>
                <a href="#" title="Twitter">t</a>
                <a href="#" title="Website">🌐</a>
                <a href="#" title="LinkedIn">in</a>
            </div>
        </div>
    </footer>

</body>
</html>
