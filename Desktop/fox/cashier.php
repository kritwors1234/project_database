<?php
require __DIR__ . '/includes/bootstrap.php';
require_login();

$st = db()->prepare(
    'SELECT p.id, p.name, p.sku, p.selling_price, p.quantity, c.name AS category_name, u.symbol AS unit_symbol
       FROM dashboard_product p
       JOIN dashboard_category c ON c.id = p.category_id
       LEFT JOIN dashboard_unit u ON u.id = p.unit_id
      WHERE p.quantity > 0 AND p.is_deleted = 0 AND p.user_id = ?
      ORDER BY p.name'
);
$st->execute([uid()]);
$products = $st->fetchAll();

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

page_header('Cashier', 'cashier', 'Cashier');
?>
<div class="row">
  <div class="col-lg-8 mb-4">
    <section class="fox-card">
      <h2 class="tab-head">Search Product</h2>
      <div class="fox-card-body">
        <div class="input-group mb-3 search-box">
          <input type="text" id="searchInput" class="form-control" placeholder="Search . . ." autocomplete="off"
                 aria-label="Search product by name, SKU or category">
          <div class="input-group-append"><span class="input-group-text">+</span></div>
        </div>

        <div id="searchResults" class="row"></div>

        <h3 class="sub-head">All Products</h3>
        <div class="row" id="allProducts">
<?php if (!$products): ?>
          <div class="col-12"><p class="empty-note">No products in stock.</p></div>
<?php endif; ?>
<?php foreach ($products as $p): ?>
          <div class="col-6 col-md-4 col-xl-3 mb-3">
            <div class="product-card h-100">
              <div class="pc-name"><?= e($p['name']) ?></div>
              <div class="pc-meta">SKU: <?= e($p['sku']) ?><br>Category: <?= e($p['category_name']) ?></div>
              <div class="pc-price"><?= e(baht($p['selling_price'], 0)) ?></div>
              <div class="pc-stock">Stock: <?= (int)$p['quantity'] ?><?= $p['unit_symbol'] !== null ? ' ' . e($p['unit_symbol']) : '' ?></div>
              <div class="input-group input-group-sm">
                <input type="number" class="form-control qty-input" value="1" min="1" max="<?= (int)$p['quantity'] ?>"
                       aria-label="Quantity">
                <div class="input-group-append">
                  <button type="button" class="btn btn-fox btn-green btn-add" data-id="<?= (int)$p['id'] ?>" aria-label="Add to cart">+</button>
                </div>
              </div>
            </div>
          </div>
<?php endforeach; ?>
        </div>
      </div>
    </section>
  </div>

  <div class="col-lg-4 mb-4">
    <section class="fox-card cart-card">
      <h2 class="tab-head">Cart <span class="cart-count" id="cartCount">0</span></h2>
      <div class="cart-time">Time: <span id="clock">--/--/---- --:--:--</span></div>
      <div class="fox-card-body">
        <div class="cart-scroll">
          <table class="table table-sm cart-table mb-0">
            <thead><tr><th>PRODUCT</th><th>QTY</th><th>PRICE</th><th></th></tr></thead>
            <tbody id="cartItems"></tbody>
          </table>
        </div>
        <div class="cart-total"><span>Total</span><strong id="cartTotal">฿0</strong></div>

        <form method="post" action="<?= e(url('checkout.php')) ?>" id="checkoutForm">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-fox btn-green btn-block btn-lg mt-3" id="payBtn" disabled>Cash Payment</button>
        </form>
        <button type="button" class="btn btn-fox btn-red btn-block mt-2" id="clearBtn" disabled>Clear All</button>
      </div>
    </section>
  </div>
</div>

<script>
window.POS = {
  api:  <?= json_encode(url('api.php'), $jsonFlags) ?>,
  csrf: <?= json_encode(csrf_token(), $jsonFlags) ?>,
  cart: <?= json_encode(cart_payload(), $jsonFlags) ?>
};
</script>
<script src="<?= e(url('assets/cashier.js')) ?>?v=1"></script>
<?php page_footer(); ?>
