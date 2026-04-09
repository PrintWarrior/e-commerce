<?php
require_once '../includes/functions.php';
if (!isLoggedIn() || !isVerified()) redirect('../login.php');

$customer_id = getCustomerId($_SESSION['user_id']);
if (!$customer_id) {
    $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
    $stmt->execute([$_SESSION['user_id']]);
    $customer_id = $pdo->lastInsertId();
}

// ── Handle remove via GET (keep existing logic) ───────────────
if (isset($_GET['remove'])) {
    $stmt = $pdo->prepare("DELETE FROM carts WHERE id = ? AND customer_id = ?");
    $stmt->execute([(int)$_GET['remove'], $customer_id]);
    header("Location: cart.php"); exit;
}

// ── Handle remove selected via POST ──────────────────────────
if (isset($_POST['remove_selected']) && !empty($_POST['selected'])) {
    $ids = array_map('intval', $_POST['selected']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge($ids, [$customer_id]);
    $pdo->prepare("DELETE FROM carts WHERE id IN ($placeholders) AND customer_id = ?")
        ->execute($params);
    header("Location: cart.php"); exit;
}

// ── Handle quantity update via POST ──────────────────────────
if (isset($_POST['update_qty'])) {
    $stmt = $pdo->prepare("UPDATE carts SET quantity = ? WHERE id = ? AND customer_id = ?");
    $stmt->execute([(int)$_POST['quantity'], (int)$_POST['cart_id'], $customer_id]);
    header("Location: cart.php"); exit;
}

// ── Fetch cart items ──────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity,
           p.id AS product_id, p.name, p.price, p.image, p.stock,
           s.id AS seller_id,
           COALESCE(s.business_name, CONCAT('Seller #', s.id)) AS seller_name
    FROM carts c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN sellers s ON p.seller_id = s.id
    WHERE c.customer_id = ?
    ORDER BY s.id, p.name
");
$stmt->execute([$customer_id]);
$cart_items = $stmt->fetchAll();

// Group by seller
$grouped = [];
foreach ($cart_items as $item) {
    $grouped[$item['seller_id']]['seller_name'] = $item['seller_name'];
    $grouped[$item['seller_id']]['items'][]      = $item;
}

$grand_total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart_items));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | Beauty Mart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/customer_cart.css">
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
                        <?php
                        $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $u = $stmt->fetch();
                        if (!empty($u['profile_pic']) && file_exists("../uploads/profile_images/" . $u['profile_pic'])):
                        ?>
                            <img src="../uploads/profile_images/<?= htmlspecialchars($u['profile_pic']) ?>" alt="">
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

            <div class="search-wrap">
                <input type="text" placeholder="Search...">
                <button type="button">Search</button>
            </div>

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
                <a href="cart.php" title="Cart" class="active-icon">
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
                <h1>My Cart</h1>
                <a href="dashboard.php" class="btn-back">‹ Dashboard</a>
            </div>

            <?php if (empty($cart_items)): ?>
                <!-- Empty state -->
                <div class="empty-cart">
                    <div class="empty-icon">🛒</div>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything yet.</p>
                    <a href="dashboard.php" class="btn-shop">Continue Shopping</a>
                </div>
            <?php else: ?>

                <!-- Main cart form (wraps everything for bulk actions) -->
                <form method="post" id="cart-form">

                    <!-- Seller groups -->
                    <?php foreach ($grouped as $sid => $group): ?>
                    <div class="seller-group">
                        <div class="seller-group-header">
                            <span class="shop-icon">🏪</span>
                            <?= htmlspecialchars($group['seller_name']) ?>
                        </div>

                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>
                                        <!-- Group select-all checkbox -->
                                        <input type="checkbox" class="row-check group-check"
                                               data-group="<?= $sid ?>"
                                               title="Select all from this seller">
                                    </th>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Price (₱)</th>
                                    <th>Qty</th>
                                    <th>Subtotal (₱)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($group['items'] as $item): ?>
                                <tr>
                                    <!-- Select checkbox -->
                                    <td>
                                        <input type="checkbox"
                                               name="selected[]"
                                               value="<?= $item['cart_id'] ?>"
                                               class="row-check item-check"
                                               data-group="<?= $sid ?>"
                                               data-price="<?= $item['price'] ?>"
                                               data-qty="<?= $item['quantity'] ?>">
                                    </td>

                                    <!-- Image -->
                                    <td>
                                        <?php if (!empty($item['image']) && file_exists("../product_images/" . $item['image'])): ?>
                                            <img class="prod-thumb"
                                                 src="../product_images/<?= htmlspecialchars($item['image']) ?>"
                                                 alt="<?= htmlspecialchars($item['name']) ?>">
                                        <?php else: ?>
                                            <div class="prod-thumb-placeholder">🛍️</div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Product name -->
                                    <td>
                                        <div class="prod-name-cell"><?= htmlspecialchars($item['name']) ?></div>
                                    </td>

                                    <!-- Price -->
                                    <td class="price-cell">₱<?= number_format($item['price'], 2) ?></td>

                                    <!-- Qty stepper -->
                                    <td>
                                        <div class="qty-stepper">
                                            <button type="button" class="qty-btn qty-minus" data-id="<?= $item['cart_id'] ?>">−</button>
                                            <input type="number"
                                                   class="qty-input"
                                                   id="qty-<?= $item['cart_id'] ?>"
                                                   value="<?= $item['quantity'] ?>"
                                                   min="1"
                                                   max="<?= $item['stock'] ?>"
                                                   data-cart-id="<?= $item['cart_id'] ?>"
                                                   data-price="<?= $item['price'] ?>">
                                            <button type="button" class="qty-btn qty-plus" data-id="<?= $item['cart_id'] ?>" data-max="<?= $item['stock'] ?>">+</button>
                                        </div>
                                        <?php if ($item['quantity'] > $item['stock']): ?>
                                            <div class="stock-warn">Only <?= $item['stock'] ?> in stock!</div>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Subtotal -->
                                    <td class="subtotal-cell" id="sub-<?= $item['cart_id'] ?>">
                                        ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                    </td>

                                    <!-- Remove -->
                                    <td>
                                        <a href="cart.php?remove=<?= $item['cart_id'] ?>"
                                           class="btn-remove"
                                           onclick="return confirm('Remove this item?')">
                                            🗑 Remove
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endforeach; ?>

                    <!-- Bottom bar -->
                    <div class="cart-bottom">
                        <div class="cart-bottom-top">
                            <button type="button" class="btn-select-all" id="select-all-btn">Select All</button>
                        </div>
                        <div class="cart-bottom-row">
                            <div class="cart-summary-info">
                                <div class="summary-line">
                                    Selected Items: <strong id="sel-count">0</strong>
                                </div>
                                <div class="summary-line">
                                    Total price: <span class="total-val" id="sel-total">₱0.00</span>
                                </div>
                            </div>
                            <div class="cart-actions">
                                <button type="submit" name="remove_selected" class="btn-remove-sel"
                                        onclick="return confirm('Remove all selected items?')">
                                    Remove Selected Items
                                </button>
                                <a href="checkout.php" class="btn-checkout">Proceed to Checkout</a>
                            </div>
                        </div>
                    </div>

                </form><!-- /cart-form -->

            <?php endif; ?>

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

    <script>
    (function () {

        /* ── Qty stepper ────────────────────────────────────── */
        document.querySelectorAll('.qty-minus').forEach(btn => {
            btn.addEventListener('click', () => {
                const id    = btn.dataset.id;
                const input = document.getElementById('qty-' + id);
                const val   = Math.max(1, parseInt(input.value) - 1);
                input.value = val;
                updateSubtotal(input);
                debounceSubmitQty(id, val);
            });
        });

        document.querySelectorAll('.qty-plus').forEach(btn => {
            btn.addEventListener('click', () => {
                const id    = btn.dataset.id;
                const max   = parseInt(btn.dataset.max) || 9999;
                const input = document.getElementById('qty-' + id);
                const val   = Math.min(max, parseInt(input.value) + 1);
                input.value = val;
                updateSubtotal(input);
                debounceSubmitQty(id, val);
            });
        });

        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', () => {
                updateSubtotal(input);
                debounceSubmitQty(input.dataset.cartId, input.value);
            });
        });

        function updateSubtotal(input) {
            const price = parseFloat(input.dataset.price);
            const qty   = parseInt(input.value) || 1;
            const sub   = document.getElementById('sub-' + input.dataset.cartId);
            if (sub) sub.textContent = '₱' + (price * qty).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
            updateSummary();
        }

        const timers = {};
        function debounceSubmitQty(id, qty) {
            clearTimeout(timers[id]);
            timers[id] = setTimeout(() => {
                const f = document.createElement('form');
                f.method = 'post';
                f.action = 'cart.php';
                f.innerHTML = `<input name="update_qty" value="1"><input name="cart_id" value="${id}"><input name="quantity" value="${qty}">`;
                document.body.appendChild(f);
                f.submit();
            }, 800);
        }

        /* ── Checkbox logic ─────────────────────────────────── */
        const selectAllBtn = document.getElementById('select-all-btn');
        let allSelected = false;

        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', () => {
                allSelected = !allSelected;
                document.querySelectorAll('.item-check').forEach(c => c.checked = allSelected);
                document.querySelectorAll('.group-check').forEach(c => c.checked = allSelected);
                selectAllBtn.textContent = allSelected ? 'Deselect All' : 'Select All';
                updateSummary();
            });
        }

        // Group checkbox toggles all items in that seller group
        document.querySelectorAll('.group-check').forEach(gc => {
            gc.addEventListener('change', () => {
                const g = gc.dataset.group;
                document.querySelectorAll(`.item-check[data-group="${g}"]`).forEach(c => c.checked = gc.checked);
                updateSummary();
            });
        });

        document.querySelectorAll('.item-check').forEach(c => {
            c.addEventListener('change', updateSummary);
        });

        function updateSummary() {
            const checked = document.querySelectorAll('.item-check:checked');
            let total = 0;
            checked.forEach(c => {
                const id    = c.value;
                const input = document.getElementById('qty-' + id);
                const price = parseFloat(c.dataset.price);
                const qty   = input ? parseInt(input.value) : parseInt(c.dataset.qty);
                total += price * qty;
            });
            document.getElementById('sel-count').textContent = checked.length;
            document.getElementById('sel-total').textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
        }

        updateSummary();
    })();
    </script>

</body>
</html>