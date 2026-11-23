const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.resolve(__dirname, '../../../mod_wtjshoppingcart/media/js/wtjshoppingcart.js'), 'utf8');
function fixture(priceFormat, currency = 'RUB', missingRow = false) {
  const field = () => ({
    innerHTML: '',
    get textContent() { return this.innerHTML; },
    set textContent(value) { this.innerHTML = value; },
  });
  const row = { quantity: field(), price: field(), total: field() };
  row.link = {
    setAttribute(name, value) { this[name] = value; },
    removeAttribute(name) { delete this[name]; },
  };
  row.getAttribute = () => '0';
  row.setAttribute = () => {};
  row.querySelectorAll = () => [];
  row.querySelector = selector => ({
    '[data-wt-jshop-module-cart-product-link]': row.link,
    '[data-wt-jshop-module-cart-product-quantity]': row.quantity,
    '[data-wt-jshop-module-cart-product-price]': row.price,
    '[data-wt-jshop-module-cart-product-price-total]': row.total,
  }[selector]);
  const total = field();
  const digit = field();
  const module = {
    getAttribute: () => '1',
    addEventListener() {},
    querySelector: () => null,
    querySelectorAll: () => missingRow ? [] : [row],
  };
  const listeners = {};
  const requests = [];
  const messages = [];
  const updates = [];
  const options = {
    wt_jshop_module_cart_module_options: { currency_code: currency, ...(priceFormat ? { price_format: priceFormat } : {}) },
    'system.paths': { rootFull: 'https://shop.test/' },
  };
  const context = {
    console, FormData, URL,
    CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
    document: {
      addEventListener() {},
      dispatchEvent(event) { updates.push(event.detail.cart); },
      createElement: field,
      getElementById: () => null,
      querySelector(selector) {
        if (selector === '[data-wt-jshop-module-cart-product-number="0"]') return missingRow ? null : row;
        if (selector === '[data-wt-jshop-module-cart-price-total]') return total;
        return null;
      },
      querySelectorAll: selector => ({
        '[data-wt-jshop-module-cart]': [module],
        '[data-wt-jshop-module-cart-price-total]': [total],
        '.wt-jshopping-cart-module-icon .digit': [digit],
      }[selector] || []),
    },
    location: { origin: 'https://shop.test' },
    addEventListener: (type, fn) => { listeners[type] = fn; },
    Joomla: {
      getOptions: (key, fallback) => options[key] || fallback,
      loadOptions: values => Object.assign(options, values),
      request: request => { requests.push(request); },
      Text: { _: key => key },
      renderMessages: message => messages.push(message),
    },
  };
  context.window = context;
  vm.createContext(context);
  vm.runInContext(source, context);
  return { api: context.wtJshopModuleCart, row, total, digit, listeners, requests, options, messages, updates };
}
const cart = (price = 19.99, quantity = 2) => ({ products: { 0: { price, quantity } }, count_product: quantity, price_product: price * quantity });

test('decimal prices are multiplied without dropping cents', () => {
  const f = fixture();
  f.api.wtJshopModuleCartUpdateCartProducts(cart());
  assert.equal(f.row.total.innerHTML, '39.98 RUB');
  assert.equal(f.row.price.innerHTML, '19.99 RUB');
});
test('initialization preserves the server-rendered discount and requests no total', () => {
  const f = fixture();
  f.total.innerHTML = 'PHP 30.00 RUB';
  f.options.wt_jshop_module_cart = { cart: cart() };
  f.api.init();
  assert.equal(f.total.innerHTML, 'PHP 30.00 RUB');
  assert.equal(f.requests.length, 0);
});
test('only the newest cart view supplies the total, including a full discount', async () => {
  const f = fixture();
  f.total.innerHTML = '30.00 RUB';
  const first = { ...cart(), summ: 0 };
  const second = { ...cart(20, 3), summ: 0 };
  f.api.applyCart(first);
  f.api.applyCart(second);
  assert.equal(f.requests.length, 2);
  assert.ok(f.requests.every(r => r.method === 'GET' && r.url.endsWith('task=view&ajax=2')));
  assert.equal(f.total.innerHTML, '30.00 RUB');
  f.requests[1].onSuccess(JSON.stringify({ ...second, summ: 0 }));
  await new Promise(setImmediate);
  f.requests[0].onSuccess(JSON.stringify({ ...first, summ: 35 }));
  await new Promise(setImmediate);
  assert.equal(f.total.innerHTML, '0.00 RUB');
  assert.equal(f.options.wt_jshop_module_cart.cart.summ, 0);
  assert.deepEqual(f.updates.map(cart => cart.summ), [0]);
});
test('an unavailable total preserves the displayed sum and the confirmed quantities', async () => {
  const f = fixture();
  f.total.innerHTML = '30.00 RUB';
  for (const summ of [null, '', ' ', 'invalid', false]) {
    f.api.applyCart({ ...cart(20, 3), summ: 0 });
    f.requests.at(-1).onSuccess(JSON.stringify({ ...cart(20, 3), summ }));
    await new Promise(setImmediate);
  }
  f.api.applyCart({ ...cart(20, 3), summ: 0 });
  f.requests.at(-1).onError({ status: 503 });
  await new Promise(setImmediate);
  assert.equal(f.total.innerHTML, '30.00 RUB');
  assert.equal(f.options.wt_jshop_module_cart.cart.summ, undefined);
  assert.equal(f.options.wt_jshop_module_cart_product_qunatities[0], 3);
  assert.equal(f.messages.length, 6);
  f.api.applyCart(cart(20, 4));
  f.requests.at(-1).onSuccess(JSON.stringify({ ...cart(20, 4), summ: '72.50' }));
  await new Promise(setImmediate);
  assert.equal(f.total.innerHTML, '72.50 RUB');
});

test('cart updates retain the matching menu route and resolve new links from the current cart view', async () => {
  const f = fixture();
  const item = (id, category, href) => ({
    products: [{ product_id: id, category_id: category, price: 20, quantity: 1, href }],
    count_product: 1, price_product: 20,
  });
  const initial = item(6, 1, '/shop/cdek');
  f.options.wt_jshop_module_cart = { cart: initial };
  f.options.wt_jshop_module_cart_module_options.cart_urls = { view: '/store/ru/cart?Itemid=14' };
  f.api.init();
  assert.equal(f.row.link.href, 'https://shop.test/shop/cdek');
  f.api.applyCart(item(6, 1));
  assert.equal(f.row.link.href, 'https://shop.test/shop/cdek');
  f.api.applyCart(item(6, 3));
  assert.equal(f.row.link.href, undefined);
  assert.equal(f.requests[1].url, 'https://shop.test/store/ru/cart?Itemid=14&ajax=2');
  const nonSef = '/store/index.php?option=com_jshopping&amp;category_id=3&amp;product_id=6&amp;Itemid=27';
  f.requests[1].onSuccess(JSON.stringify({ ...item(6, 3, nonSef), summ: 20 }));
  f.requests[0].onSuccess(JSON.stringify({ ...initial, summ: 20 }));
  await new Promise(setImmediate);
  const expected = 'https://shop.test/store/index.php?option=com_jshopping&category_id=3&product_id=6&Itemid=27';
  assert.equal(f.row.link.href, expected);
  assert.ok(Array.isArray(f.options.wt_jshop_module_cart.cart.products));
  f.api.applyCart(item(6, 3));
  f.requests[2].onError({ status: 503 });
  await new Promise(setImmediate);
  assert.equal(f.row.link.href, expected);
  f.api.applyCart(item(9, 3));
  assert.equal(f.row.link.href, undefined);
});

test('quantity and delete use the cart routes supplied by JoomShopping', async () => {
  const f = fixture();
  f.options.wt_jshop_module_cart_product_qunatities = { 0: 1 };
  f.options.wt_jshop_module_cart_module_options.cart_urls = {
    refresh: '/store/en/basket/refresh',
    delete: '/store/index.php?option=com_jshopping&task=delete&Itemid=51',
  };
  f.api.wtJshopModuleCartUpdateProductQty(0, 1, 'up');
  await new Promise(setImmediate);
  assert.equal(f.requests[0].url, 'https://shop.test/store/en/basket/refresh?ajax=1');
  f.requests[0].onError({ status: 503 });
  f.api.wtJshopModuleCartDeleteProduct(0, 1);
  await new Promise(setImmediate);
  assert.equal(f.requests[1].url, 'https://shop.test/store/index.php?option=com_jshopping&task=delete&Itemid=51&ajax=1&number_id=0');
});
test('fractional quantities and half-cent rounding match the configured precision', () => {
  const f = fixture();
  f.api.wtJshopModuleCartUpdateCartProducts(cart(1.01, 1.5));
  assert.equal(f.row.total.innerHTML, '1.52 RUB');
  f.api.wtJshopModuleCartUpdateCartProducts(cart(1.005, 1));
  assert.equal(f.row.price.innerHTML, '1.01 RUB');
});
test('JoomShopping separators and currency position are preserved', () => {
  const f = fixture({ decimal_count: 2, decimal_symbol: ',', thousand_separator: ' ', currency_format: 'Symb 00' }, '₽');
  f.api.wtJshopModuleCartUpdateCartProducts(cart(1234.5, 2));
  assert.equal(f.row.price.innerHTML, '₽ 1 234,50');
  assert.equal(f.row.total.innerHTML, '₽ 2 469,00');
});
test('zero and negative precision use the existing shop configuration', () => {
  const zero = fixture({ decimal_count: 0 });
  zero.api.wtJshopModuleCartUpdateCartProducts(cart(19.99, 1));
  assert.equal(zero.row.price.innerHTML, '20 RUB');
  const negative = fixture({ decimal_count: -2 });
  negative.api.wtJshopModuleCartUpdateCartProducts(cart(1234, 1));
  assert.equal(negative.row.price.innerHTML, '1200 RUB');
});
test('same-origin messages retain fractional counts without a cart ReferenceError', () => {
  const f = fixture();
  assert.doesNotThrow(() => f.listeners.message({ origin: 'https://shop.test', data: { messageType: 'wt-jshop-module-cart:update-products-count', cart: cart(10, 2.5) } }));
  assert.equal(f.digit.innerHTML, 2.5);
  assert.doesNotThrow(() => f.listeners.message({ origin: 'https://shop.test', data: null }));
  f.listeners.message({ origin: 'https://other.test', data: { messageType: 'wt-jshop-module-cart:update-products-count', cart: cart(10, 5) } });
  assert.equal(f.digit.innerHTML, 2.5);
});
test('missing rows do not change the rendering scheme or create new requests', () => {
  const f = fixture(undefined, 'RUB', true);
  assert.doesNotThrow(() => f.api.wtJshopModuleCartUpdateCartProducts(cart()));
  assert.equal(f.requests.length, 0);
});
