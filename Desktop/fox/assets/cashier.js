/* FoxInventory - หน้า Cashier (ไม่ต้องใช้ jQuery) */
(function () {
  'use strict';
  var POS = window.POS;
  if (!POS) { return; }

  function $(sel, root) { return (root || document).querySelector(sel); }

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function money(n) {
    return '฿' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  /* ---------- แจ้งเตือนมุมขวาบน ---------- */
  function toast(message, type) {
    var el = document.createElement('div');
    el.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger') + ' fox-toast';
    el.setAttribute('role', 'alert');
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 3500);
  }

  /* ---------- เรียก API ---------- */
  function post(action, data) {
    var fd = new FormData();
    fd.append('csrf', POS.csrf);
    fd.append('action', action);
    Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(POS.api, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.cart) { renderCart(res.cart); }
        if (res && res.status === 'error') { toast(res.message || 'เกิดข้อผิดพลาด', 'error'); }
        return res;
      })
      .catch(function () { toast('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้', 'error'); });
  }

  /* ---------- ตะกร้า ---------- */
  function renderCart(cart) {
    $('#cartCount').textContent = cart.count;
    $('#cartTotal').textContent = money(cart.total);
    var body = $('#cartItems');

    if (!cart.items.length) {
      body.innerHTML = '<tr><td colspan="4" class="text-center empty-note py-3">Cart is empty</td></tr>';
    } else {
      body.innerHTML = cart.items.map(function (it) {
        return '<tr>' +
          '<td><small>' + esc(it.name) + '</small></td>' +
          '<td><input type="number" class="form-control form-control-sm cart-qty" min="1" value="' + Number(it.quantity) +
            '" data-id="' + Number(it.id) + '" aria-label="Quantity of ' + esc(it.name) + '"></td>' +
          '<td><small>' + money(it.total) + '</small></td>' +
          '<td><button type="button" class="btn btn-fox btn-xs btn-white cart-remove" data-id="' + Number(it.id) +
            '" aria-label="Remove ' + esc(it.name) + '">&times;</button></td>' +
          '</tr>';
      }).join('');
    }
    var empty = cart.items.length === 0;
    $('#payBtn').disabled = empty;
    $('#clearBtn').disabled = empty;
  }

  /* ---------- การ์ดสินค้า (ผลค้นหา) ---------- */
  function productCard(p) {
    return '<div class="col-6 col-md-4 col-xl-3 mb-3"><div class="product-card product-card-hit h-100">' +
      '<div class="pc-name">' + esc(p.name) + '</div>' +
      '<div class="pc-meta">SKU: ' + esc(p.sku) + '<br>Category: ' + esc(p.category) + '</div>' +
      '<div class="pc-price">' + money(p.price) + '</div>' +
      '<div class="pc-stock">Stock: ' + Number(p.quantity) + (p.unit ? ' ' + esc(p.unit) : '') + '</div>' +
      '<div class="input-group input-group-sm">' +
        '<input type="number" class="form-control qty-input" value="1" min="1" max="' + Number(p.quantity) + '" aria-label="Quantity">' +
        '<div class="input-group-append"><button type="button" class="btn btn-fox btn-green btn-add" data-id="' + Number(p.id) +
        '" aria-label="Add to cart">+</button></div>' +
      '</div></div></div>';
  }

  /* ---------- ค้นหา ---------- */
  var searchTimer = null;
  var searchSeq = 0;
  $('#searchInput').addEventListener('input', function () {
    var q = this.value.trim();
    var box = $('#searchResults');
    clearTimeout(searchTimer);
    if (q === '') { box.innerHTML = ''; return; }
    searchTimer = setTimeout(function () {
      var seq = ++searchSeq;
      fetch(POS.api + '?action=search&q=' + encodeURIComponent(q), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (seq !== searchSeq) { return; }   // ผลเก่ากว่า ไม่ต้องแสดง
          if (!data.products || !data.products.length) {
            box.innerHTML = '<div class="col-12"><p class="empty-note">Product not found</p></div>';
            return;
          }
          box.innerHTML = data.products.map(productCard).join('');
        })
        .catch(function () { box.innerHTML = ''; });
    }, 200);
  });

  /* ---------- เหตุการณ์คลิก/แก้ไข (delegation) ---------- */
  document.addEventListener('click', function (ev) {
    var add = ev.target.closest('.btn-add');
    if (add) {
      var qtyEl = add.closest('.input-group').querySelector('.qty-input');
      var qty = parseInt(qtyEl.value, 10);
      if (!qty || qty < 1) { qty = 1; }
      post('add', { product_id: add.getAttribute('data-id'), quantity: qty }).then(function (res) {
        if (res && res.status === 'success') {
          $('#searchInput').value = '';
          $('#searchResults').innerHTML = '';
          qtyEl.value = 1;
        }
      });
      return;
    }
    var rm = ev.target.closest('.cart-remove');
    if (rm) {
      post('remove', { product_id: rm.getAttribute('data-id') });
    }
  });

  document.addEventListener('change', function (ev) {
    var inp = ev.target.closest('.cart-qty');
    if (!inp) { return; }
    var qty = parseInt(inp.value, 10);
    if (!qty || qty < 1) { qty = 1; }
    post('update', { product_id: inp.getAttribute('data-id'), quantity: qty });
  });

  $('#clearBtn').addEventListener('click', function () {
    if (confirm('Confirm clearing all items?')) { post('clear', {}); }
  });

  $('#checkoutForm').addEventListener('submit', function () {
    $('#payBtn').disabled = true;   // กันกดซ้ำ
  });

  /* ---------- นาฬิกา ---------- */
  function pad(n) { return String(n).padStart(2, '0'); }
  function tick() {
    var d = new Date();
    var el = $('#clock');
    if (el) {
      el.textContent = pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' +
                       pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }
  }
  tick();
  setInterval(tick, 1000);

  renderCart(POS.cart);
})();
