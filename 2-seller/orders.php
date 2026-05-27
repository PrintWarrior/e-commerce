<?php
require_once '../includes/functions.php';
require_once 'seller_header.php';

$seller_id = $seller['seller_id'];
ensureOrderItemFulfillmentColumns();
$success = '';
$error   = '';

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

    if (!in_array($new_status, $allowed_statuses, true)) {
        $error = "Invalid order status.";
    } else {
        // Fetch this seller's current status for the order.
        $cur_stmt = $pdo->prepare("
            SELECT DISTINCT oi.status FROM orders o
            JOIN order_items oi ON oi.order_id = o.id
            JOIN products p ON p.id = oi.product_id
            WHERE o.id = ? AND p.seller_id = ?
        ");
        $cur_stmt->execute([$order_id, $seller_id]);
        $seller_statuses = $cur_stmt->fetchAll(PDO::FETCH_COLUMN);
        $current_status = count($seller_statuses) === 1 ? $seller_statuses[0] : false;

        $allowed_transitions = [
            'pending'    => ['processing', 'cancelled'],
            'processing' => ['shipped'],
            'shipped'    => ['completed'],
        ];

        if ($current_status === false) {
            $error = "Order not found or has mixed item statuses for this seller.";
        } elseif (isset($allowed_transitions[$current_status]) && !in_array($new_status, $allowed_transitions[$current_status], true)) {
            $error = "Cannot change status from " . ucfirst($current_status) . " to " . ucfirst($new_status) . ".";
        } else {
        $pdo->beginTransaction();

        try {
            applyOrderStockForSellerStatusTransition($order_id, $seller_id, (string) $current_status, $new_status);

            $stmt = $pdo->prepare("
                UPDATE order_items oi
                JOIN products p ON p.id = oi.product_id
                SET oi.status = ?
                WHERE oi.order_id = ? AND p.seller_id = ?
            ");
            $stmt->execute([$new_status, $order_id, $seller_id]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('Order not found or not assigned to this seller.');
            }

            syncOrderStatusFromItems($order_id);

            if ($new_status === 'completed') {
                $getOrder = $pdo->prepare("
                    SELECT p.seller_id, SUM(oi.quantity * oi.price) AS seller_total
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p ON oi.product_id = p.id
                    WHERE o.id = ? AND p.seller_id = ?
                    GROUP BY p.seller_id
                ");
                $getOrder->execute([$order_id, $seller_id]);
                $orderData = $getOrder->fetch();

                if ($orderData) {
                    $check = $pdo->prepare("SELECT COUNT(*) FROM seller_earnings WHERE seller_id = ? AND order_id = ?");
                    $check->execute([$seller_id, $order_id]);

                    if ((int)$check->fetchColumn() === 0) {
                        $insert = $pdo->prepare("
                            INSERT INTO seller_earnings (seller_id, order_id, amount, status)
                            VALUES (?, ?, ?, 'paid')
                        ");
                        $insert->execute([$orderData['seller_id'], $order_id, $orderData['seller_total']]);
                    }
                }
            }

            $pdo->commit();
            $success = "Order #$order_id status updated to " . ucfirst($new_status) . ".";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
        } // end allowed_transitions check
    } // end allowed_statuses check
}

$status_filter = $_GET['status'] ?? 'all';
$search        = $_GET['search'] ?? '';
$orders_per_page = 10;
$current_page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $orders_per_page;

 $query_base  = " FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            JOIN customers c ON o.customer_id = c.id
            JOIN users u ON c.user_id = u.id
            LEFT JOIN addresses a ON o.shipping_address_id = a.id
            LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
            WHERE p.seller_id = ?";
$params = [$seller_id];

if ($status_filter !== 'all') { $query_base .= " AND oi.status = ?"; $params[] = $status_filter; }
if ($search) {
    $query_base  .= " AND (o.id LIKE ? OR u.firstname LIKE ? OR u.lastname LIKE ?)";
    $sp      = "%$search%";
    $params  = array_merge($params, [$sp, $sp, $sp]);
}

$count_stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id)" . $query_base);
$count_stmt->execute($params);
$filtered_total = (int) $count_stmt->fetchColumn();
$total_pages = max(1, (int) ceil($filtered_total / $orders_per_page));

if ($current_page > $total_pages) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $orders_per_page;
}

 $query  = "SELECT o.*, u.firstname, u.lastname, u.email,
                   a.barangay, a.municipality, a.province, a.zip_code, a.address_details,
                   pm.name AS payment_method_name,
                   MIN(oi.status) AS seller_status,
                   SUM(oi.quantity * oi.price) AS seller_total
            " . $query_base . "
            GROUP BY o.id
            ORDER BY
                FIELD(MIN(oi.status), 'pending', 'processing', 'shipped', 'completed', 'cancelled'),
                o.created_at DESC
            LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($query);
$order_params = [...$params, $orders_per_page, $offset];
$param_index = 1;
foreach ($order_params as $value) {
    $stmt->bindValue($param_index++, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$orders = $stmt->fetchAll();

foreach ($orders as &$order) {
    $order['status'] = $order['seller_status'] ?? $order['status'];
    $s = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? AND p.seller_id = ?");
    $s->execute([$order['id'], $seller_id]);
    $order['items'] = $s->fetchAll();
}
unset($order);

// Count per status for tab badges
$all_count_stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_items oi ON o.id=oi.order_id JOIN products p ON oi.product_id=p.id WHERE p.seller_id=?");
$all_count_stmt->execute([$seller_id]);
$counts = ['all' => (int) $all_count_stmt->fetchColumn()];
foreach (['pending','processing','shipped','completed','cancelled'] as $st) {
    $s = $pdo->prepare("SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_items oi ON o.id=oi.order_id JOIN products p ON oi.product_id=p.id WHERE p.seller_id=? AND oi.status=?");
    $s->execute([$seller_id, $st]);
    $counts[$st] = (int)$s->fetchColumn();
}

$pagination_params = array_filter([
    'status' => $status_filter !== 'all' ? $status_filter : '',
    'search' => $search,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Beauty Mart Seller</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/seller_header.css">
    <link rel="stylesheet" href="../css/seller_orders.css">
    <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>

<div class="seller-wrapper">

    <?php /* sidebar injected by seller_header.php */ ?>

    <div class="main-content">

        <!-- Top bar -->
        <div class="topbar">
            <div class="topbar-left">
                <h1>Orders Management</h1>
                <p>Manage and track all your customer orders</p>
            </div>
            <div class="topbar-right">
                <span class="topbar-date"><?= date('F j, Y') ?></span>
            </div>
        </div>

        <!-- Page content -->
        <div class="page-content">

            <?php if ($success): ?>
                <div class="alert alert-success"> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">  <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Filters bar -->
            <div class="filters-bar">
                <div class="status-filters">
                    <?php
                    $tabs = [
                        'all'        => 'All',
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'shipped'    => 'Shipped',
                        'completed'  => 'Completed',
                        'cancelled'  => 'Cancelled',
                    ];
                    // order matches: Pending > Processing > Shipped > Completed > Cancelled
                    foreach ($tabs as $val => $label):
                        $cnt = $counts[$val] ?? 0;
                    ?>
                    <a href="?status=<?= $val ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                       class="filter-btn <?= $status_filter === $val ? 'active' : '' ?>">
                        <?= $label ?>
                        <?php if ($cnt > 0): ?>
                            <span class="count"><?= $cnt ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="filters-actions">
                    <form method="get" class="search-form">
                        <?php if ($status_filter !== 'all'): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                        <?php endif; ?>
                        <input type="text" name="search"
                               placeholder="Search by order # or customer"
                               value="<?= htmlspecialchars($search) ?>">
                        <button type="submit">Search</button>
                    </form>
                    <button type="button" id="generate-report-btn" class="btn-generate-report">
                        📄 Generate Report
                    </button>
                </div>
            </div>

            <!-- Orders list -->
            <div class="orders-list">
                <?php if (empty($orders)): ?>
                    <div class="empty-orders">
                        <div class="empty-icon"> </div>
                        <h3>No orders found</h3>
                        <p>
                            <?= $search
                                ? "No results for \"" . htmlspecialchars($search) . "\". Try a different search."
                                : ($status_filter !== 'all'
                                    ? "No " . ucfirst($status_filter) . " orders at the moment."
                                    : "You haven't received any orders yet.") ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <div class="order-card">

                        <!-- Header -->
                        <div class="order-header">
                            <div class="order-meta">
                                <h3>Order #<?= $order['id'] ?></h3>
                                <p>Placed on <?= date('F j, Y · g:i A', strtotime($order['created_at'])) ?></p>
                                <p>Customer: <span class="customer-name"><?= htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) ?></span></p>
                                <p><?= htmlspecialchars($order['email']) ?></p>
                            </div>
                            <div class="order-header-right">
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                                <span class="order-total-chip">
                                    <?= number_format((float) $order['seller_total'], 2) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Items table -->
                        <div class="order-items">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td><?= number_format($item['price'], 2) ?></td>
                                        <td><?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="total-label">Store Total</td>
                                        <td class="total-amount"><?= number_format((float) $order['seller_total'], 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Actions -->
                        <div class="order-actions">
                            <?php if (!in_array($order['status'], ['cancelled', 'completed'])): ?>
                                <form method="post" class="status-form">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <?php
                                    // Define which statuses are selectable from each current status
                                    $allowed_next = [
                                        'pending'    => ['processing', 'cancelled'],
                                        'processing' => ['shipped'],
                                        'shipped'    => ['completed'],
                                    ];
                                    $next_options = $allowed_next[$order['status']] ?? [];
                                    $all_options  = [
                                        'pending'    => 'Pending',
                                        'processing' => 'Processing',
                                        'shipped'    => 'Shipped',
                                        'completed'  => 'Completed',
                                        'cancelled'  => 'Cancelled',
                                    ];
                                    ?>
                                    <select name="status" required>
                                        
                                        <?php foreach ($all_options as $val => $label):
                                            $is_current  = $order['status'] === $val;
                                            $is_allowed  = in_array($val, $next_options, true);
                                            $disabled    = (!$is_current && !$is_allowed) ? 'disabled' : '';
                                        ?>
                                        <option value="<?= $val ?>"
                                                <?= $is_current ? 'selected' : '' ?>
                                                <?= $disabled ?>>
                                            <?= $label ?><?= $disabled ? ' —' : '' ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="update_status" class="btn-update">Update</button>
                                </form>
                            <?php else: ?>
                                <span class="action-note">
                                    This order is <?= $order['status'] === 'completed' ? '“ completed' : ' cancelled' ?> and cannot be modified.
                                </span>
                            <?php endif; ?>
                        </div>

                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a class="page-link" href="?<?= htmlspecialchars(http_build_query([...$pagination_params, 'page' => $current_page - 1])) ?>">‹</a>
                    <?php else: ?>
                        <span class="page-link disabled">‹</span>
                    <?php endif; ?>

                    <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                        <a class="page-link <?= $page === $current_page ? 'active' : '' ?>" href="?<?= htmlspecialchars(http_build_query([...$pagination_params, 'page' => $page])) ?>"><?= $page ?></a>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a class="page-link" href="?<?= htmlspecialchars(http_build_query([...$pagination_params, 'page' => $current_page + 1])) ?>">›</a>
                    <?php else: ?>
                        <span class="page-link disabled">›</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div><!-- /page-content -->

        <!-- Footer -->
        <footer class="seller-footer">
            <div class="footer-inner">
                <p class="footer-copy">Copyright &copy; 2025 All Rights Reserved by <span>Beauty Mart.</span></p>
                <div class="footer-socials">
                    <a href="#" title="Facebook">f</a>
                    <a href="#" title="Twitter">t</a>
                    <a href="#" title="Website"> </a>
                    <a href="#" title="LinkedIn">in</a>
                </div>
            </div>
        </footer>

     </div><!-- /main-content -->
 </div><!-- /seller-wrapper -->

 <!-- Report Modal -->
 <div id="report-modal" class="report-modal-overlay" style="display:none;">
     <div class="report-modal">
         <div class="report-modal-header">
             <div>
                 <h2>Order Report</h2>
                 <p class="report-subtitle">
                     Filter: <?= ucfirst($status_filter) ?> orders
                     <?php if ($search): ?>
                         | Search: "<?= htmlspecialchars($search) ?>"
                     <?php endif; ?>
                     | (<?= $filtered_total ?> order<?= $filtered_total === 1 ? '' : 's' ?> total)
                 </p>
             </div>
             <button type="button" class="report-close" id="report-close">&times;</button>
         </div>
         <div class="report-modal-body">
             <!-- Report Table -->
             <table class="report-table">
                 <thead>
                     <tr>
                         <th>Order #</th>
                         <th>Date</th>
                         <th>Customer</th>
                         <th>Shipping</th>
                         <th>Payment</th>
                         <th>Products</th>
                         <th>Total</th>
                         <th>Status</th>
                     </tr>
                 </thead>
                 <tbody>
                     <?php foreach ($orders as $order): ?>
                     <tr>
                         <td class="report-order-id">#<?= $order['id'] ?></td>
                         <td class="report-date"><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                         <td class="report-customer">
                             <?= htmlspecialchars($order['firstname'] . ' ' . $order['lastname']) ?>
                             <br><small><?= htmlspecialchars($order['email']) ?></small>
                         </td>
                         <td class="report-shipping">
                             <?php
                             $addr = trim(($order['barangay'] ?? '') . ' ' . ($order['municipality'] ?? '') . ' ' . ($order['province'] ?? ''));
                             echo $addr ? htmlspecialchars($addr) : '—';
                             ?>
                         </td>
                         <td class="report-payment">
                             <?= htmlspecialchars($order['payment_method_name'] ?? 'COD') ?>
                         </td>
                         <td class="report-items">
                             <?php foreach ($order['items'] as $item): ?>
                                 <div>• <?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></div>
                             <?php endforeach; ?>
                         </td>
                         <td class="report-total">₱<?= number_format($order['total_amount'], 2) ?></td>
                         <td>
                             <span class="status-badge status-<?= $order['status'] ?>">
                                 <?= ucfirst($order['status']) ?>
                             </span>
                         </td>
                     </tr>
                     <?php endforeach; ?>
                 </tbody>
             </table>

             <?php if (empty($orders)): ?>
                 <p class="report-empty">No orders match this filter.</p>
             <?php endif; ?>
         </div>
         <div class="report-modal-footer">
             <button type="button" class="btn-print-report" id="btn-print-report">Print Report</button>
             <button type="button" class="btn-close-modal" id="btn-close-report">Close</button>
         </div>
     </div>
 </div>

 <!-- Report Modal Styles -->
 <style>
 .report-modal-overlay {
     position: fixed; inset: 0;
     background: rgba(0,0,0,.6);
     z-index: 10000;
     display: flex; align-items: flex-start; justify-content: center;
     overflow-y: auto;
     padding: 40px 20px;
 }
 .report-modal {
     background: #fff;
     width: 100%; max-width: 900px;
     border-radius: 14px;
     box-shadow: 0 12px 60px rgba(0,0,0,.35);
     overflow: hidden;
     margin: 40px 0;
 }
 .report-modal-header {
     background: linear-gradient(135deg, #fce8ee, #f5c6d4);
     padding: 18px 24px;
     display: flex; justify-content: space-between; align-items: flex-start;
     border-bottom: 2px solid var(--pink-mid);
 }
 .report-modal-header h2 {
     margin: 0;
     font-family: 'Playfair Display', serif;
     font-size: 24px; font-weight: 700;
     color: var(--pink-accent);
 }
 .report-subtitle {
     margin: 4px 0 0;
     font-size: 13px;
     color: var(--text-muted);
 }
 .report-close {
     background: none; border: none;
     font-size: 32px; line-height: 1;
     color: var(--pink-dark);
     cursor: pointer; width: 36px; height: 36px;
     display: flex; align-items: center; justify-content: center;
     border-radius: 50%; transition: background .15s;
 }
 .report-close:hover { background: rgba(200,80,110,.1); }

 .report-modal-body {
     padding: 20px 24px 0;
     max-height: 70vh;
     overflow-y: auto;
 }
 .report-table {
     width: 100%;
     border-collapse: collapse;
     font-size: 14px;
 }
 .report-table th {
     text-align: left;
     font-size: 11px;
     font-weight: 800;
     color: var(--text-muted);
     text-transform: uppercase;
     letter-spacing: .5px;
     padding: 10px 12px;
     background: var(--pink-pale);
     border-bottom: 2px solid var(--pink-mid);
     white-space: nowrap;
 }
 .report-table td {
     padding: 14px 12px;
     border-bottom: 1px solid #f5eef0;
     vertical-align: top;
     font-size: 13.5px;
 }
 .report-table tbody tr:hover { background: #fdf5f7; }
 .report-order-id { font-weight: 800; color: var(--pink-accent); }
 .report-date { color: var(--text-muted); font-size: 13px; white-space: nowrap; }
 .report-customer { font-weight: 700; color: var(--text-dark); }
 .report-customer small { font-weight: 400; color: var(--text-muted); font-size: 12px; }
 .report-shipping { font-size: 13px; color: var(--text-mid); white-space: nowrap; }
 .report-payment { font-size: 13px; color: var(--text-mid); }
 .report-items { font-size: 13px; color: var(--text-mid); }
 .report-items div { line-height: 1.5; }
 .report-total { font-weight: 800; color: var(--text-dark); }

 .report-empty {
     text-align: center; padding: 40px 20px;
     color: var(--text-muted); font-style: italic;
 }

 .report-modal-footer {
     padding: 16px 24px;
     background: var(--pink-soft);
     border-top: 1.5px solid var(--pink-mid);
     display: flex; justify-content: flex-end; gap: 12px;
 }
 .btn-print-report {
     height: 40px; padding: 0 22px;
     background: var(--pink-accent); color: #fff;
     border: none; border-radius: 20px;
     font-size: 14px; font-weight: 800;
     font-family: 'Nunito', sans-serif;
     cursor: pointer;
     transition: background .2s;
 }
 .btn-print-report:hover { background: var(--pink-dark); }
 .btn-close-modal {
     height: 40px; padding: 0 22px;
     background: #fff; color: var(--text-mid);
     border: 1.5px solid var(--pink-mid);
     border-radius: 20px;
     font-size: 14px; font-weight: 800;
     font-family: 'Nunito', sans-serif;
     cursor: pointer;
     transition: background .2s, color .2s;
 }
 .btn-close-modal:hover { background: var(--pink-pale); color: var(--pink-dark); }

 @media print {
     body * { visibility: hidden; }
     #report-modal, #report-modal * { visibility: visible; }
     #report-modal {
         position: fixed; top: 0; left: 0; right: 0; bottom: 0;
         background: #fff; z-index: 999999;
         display: block !important;
         padding: 0;
         margin: 0;
         overflow: visible;
     }
     .report-modal { margin: 0; box-shadow: none; border-radius: 0; max-width: 100%; }
     .report-modal-header, .report-modal-footer, .report-close { display: none !important; }
     .report-modal-body { max-height: none; overflow: visible; padding: 0; }
     .report-table { font-size: 11px; }
     .report-table th { background: #fce8ee !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; padding: 6px 8px; }
     .report-table td { padding: 6px 8px; }
     .report-shipping, .report-payment { white-space: nowrap; }
     .report-items div { line-height: 1.4; }
     @page { margin: 10mm; size: landscape; }
 }
 </style>

 <script>
 (function() {
     const modal = document.getElementById('report-modal');
     const openBtn = document.getElementById('generate-report-btn');
     const closeBtn = document.getElementById('report-close');
     const closeBtn2 = document.getElementById('btn-close-report');
     const printBtn = document.getElementById('btn-print-report');

     openBtn?.addEventListener('click', () => {
         modal.style.display = 'flex';
         document.body.style.overflow = 'hidden';
     });

     const closeModal = () => {
         modal.style.display = 'none';
         document.body.style.overflow = '';
     };

     closeBtn?.addEventListener('click', closeModal);
     closeBtn2?.addEventListener('click', closeModal);
     modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

     printBtn?.addEventListener('click', () => {
         modal.style.display = 'block';
         document.body.style.overflow = 'hidden';
         window.print();
         setTimeout(() => { modal.style.display = 'none'; document.body.style.overflow = ''; }, 100);
     });
 })();
 </script>

 </body>
 </html>
