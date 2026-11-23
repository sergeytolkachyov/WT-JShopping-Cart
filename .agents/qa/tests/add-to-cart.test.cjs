const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.resolve(__dirname, '../../../plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart/media/js/wtjshopping-add-to-cart.js'), 'utf8');
const origin = 'https://shop.test';
const buttonAttribute = 'wt-jshopping-add-to-cart-btn';
const messageType = 'wt-jshop-module-cart:update-products-count';
const tick = () => new Promise(resolve => setImmediate(resolve));

function fixture(selectors = {}) {
  const requests = [];
  const messages = [];
  const notices = [];
  const handlers = {};
  const fields = {};
  const controls = new Set();
  const options = {
    wt_jshop_add_to_cart: { url: '/en/shop/cart/add?Itemid=42', ...selectors },
    wt_jshop_module_cart_product_qunatities: { 0: 1, 4: 3 },
    wt_jshop_module_cart: {},
  };
  class BrowserFormData extends FormData {
    constructor(form) {
      super();
      if (form) for (const [key, value] of form.entries) this.append(key, value);
    }
  }
  const context = {
    FormData: BrowserFormData,
    URL,
    window: { location: { origin }, postMessage: (message, target) => messages.push({ message, target }) },
    document: {
      baseURI: origin + '/en/catalog',
      addEventListener: (name, fn) => { handlers[name] = fn; },
      getElementById: id => fields[id],
      querySelectorAll: () => Array.from(controls),
      querySelector(selector) {
        if (selector === '[') throw new DOMException('Invalid CSS selector', 'SyntaxError');
        return null;
      },
      createElement: () => ({
        get innerHTML() {
          return this.textContent.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        },
      }),
    },
    Joomla: {
      getOptions: (key, fallback) => options[key] || fallback,
      loadOptions: values => Object.assign(options, values),
      request: request => requests.push(request),
      renderMessages: message => notices.push(message),
      Text: {
        _: (key, fallback) => key === 'JERROR_LAYOUT_ERROR_HAS_OCCURRED_WHILE_PROCESSING_YOUR_REQUEST'
          ? 'Request failed'
          : fallback,
      },
    },
  };
  vm.runInNewContext(source, context);

  const dispatch = (type, properties) => {
    const event = {
      defaultPrevented: false,
      button: 0,
      preventDefault() { this.defaultPrevented = true; },
      ...properties,
    };
    controls.add(event.submitter || event.target);
    handlers[type](event);
    return event;
  };
  return { requests, messages, notices, fields, options, dispatch, controls, handlers, window: context.window };
}

function control({ tagName = 'BUTTON', marker = '', attributes = {}, selectors = [], ...properties } = {}) {
  const values = { ...attributes };
  if (marker !== null) values[buttonAttribute] = marker;
  return {
    tagName,
    type: 'button',
    getAttribute: name => values[name] === undefined ? null : values[name],
    setAttribute: (name, value) => { values[name] = value; },
    removeAttribute: name => { delete values[name]; },
    hasAttribute: name => Object.hasOwn(values, name),
    matches: selector => selectors.includes(selector) || (selector === '[' + buttonAttribute + ']' && marker !== null),
    closest(selector) {
      if (tagName === 'A' && selector.includes('a.button_buy[href]')) return this;
      return selector.split(', ').some(value => this.matches(value)) ? this : null;
    },
    ...properties,
  };
}

const listButton = (id = '9', marker = '') => control({
  marker, dataset: { productId: id, productCategoryId: '2' },
});
const qtyField = value => ({ value, matches: selector => selector === '[wt-jshopping-add-to-cart-qty]' });
const cart = { count_product: 2.5, price_product: 55, products: { 0: { price: 22, quantity: 2.5 } } };

test('legacy SEF purchase preserves routing, updates refresh quantities and posts the server cart', async () => {
  const f = fixture();
  const target = control({
    tagName: 'A', marker: null, href: origin + '/en/cart/add?product_id=9&category_id=2&Itemid=42',
  });
  assert.equal(f.dispatch('click', { target }).defaultPrevented, true);
  await tick();
  const request = f.requests[0];
  assert.equal(request.method, 'GET');
  assert.equal(request.url, target.href + '&ajax=1');
  assert.equal(request.data, null);
  request.onSuccess(JSON.stringify(cart));
  await tick();
  assert.equal(target.hasAttribute('aria-busy'), false);
  assert.deepEqual(f.options.wt_jshop_module_cart_product_qunatities, { 0: 2.5 });
  assert.deepEqual(JSON.parse(JSON.stringify(f.messages)), [{ message: { messageType, cart }, target: origin }]);
});

test('legacy query-string purchase is intercepted while modified or unrelated links navigate normally', async () => {
  const f = fixture();
  const buy = control({
    tagName: 'A', marker: null, href: origin + '/index.php?option=com_jshopping&controller=cart&task=add&product_id=9',
  });
  const detail = control({ tagName: 'A', marker: null, href: buy.href.replace('task=add', 'task=view') });
  assert.equal(f.dispatch('click', { target: buy, ctrlKey: true }).defaultPrevented, false);
  assert.equal(f.dispatch('click', { target: detail }).defaultPrevented, false);
  assert.equal(f.dispatch('click', { target: buy }).defaultPrevented, true);
  await tick();
  assert.equal(f.requests.length, 1);
  assert.equal(f.requests[0].url, buy.href + '&ajax=1');
  f.requests[0].onSuccess(JSON.stringify(cart));
});

test('marked list buttons use an optional linked quantity and default to one', async () => {
  const f = fixture();
  f.fields.local = qtyField('1,25');
  assert.equal(f.dispatch('click', { target: listButton('9', 'local') }).defaultPrevented, true);
  assert.equal(f.dispatch('click', { target: listButton('11') }).defaultPrevented, true);
  await tick();
  assert.equal(f.requests[0].method, 'POST');
  assert.equal(f.requests[0].url, origin + '/en/shop/cart/add?Itemid=42&ajax=1');
  assert.deepEqual(Object.fromEntries(f.requests[0].data), {
    product_id: '9', category_id: '2', quantity: '1,25', to: 'cart', ajax: '1',
  });
  f.requests[0].onSuccess(JSON.stringify(cart));
  await tick();
  assert.equal(f.requests[1].data.get('quantity'), '1');
  f.requests[1].onSuccess(JSON.stringify(cart));
});

test('product submit includes the stock quantity, attributes, and other form fields', async () => {
  const f = fixture();
  const form = {
    action: origin + '/en/cart/add',
    querySelector: () => null,
    entries: [
      ['product_id', '9'], ['quantity', '2.75'], ['to', 'wishlist'],
      ['jshop_attr_id[1]', '17'], ['freeattribut[4]', 'custom'], ['token', '1'],
    ],
  };
  const buy = control({ name: 'submit_action', value: 'buy' });
  const wishlist = control({ marker: null });
  assert.equal(f.dispatch('submit', { target: form, submitter: wishlist }).defaultPrevented, false);
  assert.equal(f.dispatch('submit', { target: form, submitter: buy, defaultPrevented: true }).defaultPrevented, true);
  assert.equal(f.dispatch('submit', { target: form, submitter: buy }).defaultPrevented, true);
  await tick();
  assert.equal(f.requests.length, 1);
  assert.equal(f.requests[0].method, 'POST');
  assert.equal(f.requests[0].url, form.action + '?ajax=1');
  assert.deepEqual(Object.fromEntries(f.requests[0].data), {
    product_id: '9', quantity: '2.75', to: 'cart', 'jshop_attr_id[1]': '17',
    'freeattribut[4]': 'custom', token: '1', submit_action: 'buy', ajax: '1',
  });
  f.requests[0].onSuccess(JSON.stringify(cart));
});

test('marked form quantity overrides the standard field within the same form', async () => {
  const f = fixture();
  const form = {
    action: origin + '/en/cart/add',
    querySelector: () => qtyField('0.5'),
    entries: [['product_id', '9'], ['quantity', '1']],
  };
  f.dispatch('submit', { target: form, submitter: control() });
  await tick();
  assert.equal(f.requests[0].data.get('quantity'), '0.5');
  f.requests[0].onSuccess(JSON.stringify(cart));
});

test('repeat clicks are collapsed and the next request can run after a server error', async () => {
  const f = fixture();
  const first = listButton();
  const second = listButton('11');
  f.dispatch('click', { target: first });
  f.dispatch('click', { target: first });
  f.dispatch('click', { target: second });
  await tick();
  assert.equal(f.requests.length, 1);
  assert.equal(first.getAttribute('aria-busy'), 'true');
  f.requests[0].onSuccess(JSON.stringify([{ level: 'error', message: 'Check <stock>' }]));
  await tick();
  assert.equal(first.hasAttribute('aria-busy'), false);
  assert.equal(f.messages.length, 0);
  assert.equal(f.notices[0].error[0], 'Check &lt;stock&gt;');
  assert.equal(f.requests.length, 2);
  f.requests[1].onError({ status: 500 });
  await tick();
  assert.equal(f.notices[1].error[0], 'Request failed');
  assert.equal(second.hasAttribute('aria-busy'), false);
  f.dispatch('click', { target: first });
  await tick();
  assert.equal(f.requests.length, 3);
  f.requests[2].onSuccess(JSON.stringify(cart));
});

test('an invalid cart row leaves existing quantities intact', async () => {
  const f = fixture();
  f.dispatch('click', { target: listButton() });
  await tick();
  f.requests[0].onSuccess(JSON.stringify({ ...cart, products: { 0: null } }));
  await tick();
  assert.deepEqual(f.options.wt_jshop_module_cart_product_qunatities, { 0: 1, 4: 3 });
  assert.equal(f.messages.length, 0);
  assert.equal(f.notices[0].error[0], 'Request failed');
});

test('list selectors use the chosen quantity from the clicked product only', async () => {
  const f = fixture({ list_button_selector: '[site-buy]', list_quantity_selector: '[site-qty]' });
  const catalogue = {
    matches: () => false,
    querySelectorAll: () => [first, second],
    querySelector: () => qtyField('2.5'),
  };
  function productButton(id, value) {
    return control({
      marker: null,
      selectors: ['[site-buy]'],
      dataset: { productId: id, productCategoryId: '2' },
      parentElement: {
        parentElement: catalogue,
        matches: () => false,
        querySelectorAll: () => [],
        querySelector: selector => selector === '[site-qty]' && value ? qtyField(value) : null,
      },
    });
  }
  const first = productButton('9', '2.5');
  const second = productButton('11');
  assert.equal(f.dispatch('click', { target: first }).defaultPrevented, true);
  assert.equal(f.dispatch('click', { target: second }).defaultPrevented, true);
  await tick();
  assert.equal(f.requests[0].data.get('quantity'), '2.5');
  f.requests[0].onSuccess(JSON.stringify(cart));
  await tick();
  assert.equal(f.requests[1].data.get('quantity'), '1');
  f.requests[1].onSuccess(JSON.stringify(cart));
});

test('product selectors handle an unmarked submitter and preserve the wishlist destination', async () => {
  const f = fixture({ product_button_selector: '[site-order]', product_quantity_selector: '[site-qty]' });
  const form = {
    action: origin + '/en/cart/add',
    querySelector: selector => selector === '[site-qty]' ? qtyField('1,75') : null,
    entries: [['product_id', '9'], ['quantity', '3'], ['to', 'wishlist']],
  };
  const submitter = control({ marker: null, selectors: ['[site-order]'] });
  assert.equal(f.dispatch('submit', { target: form, submitter }).defaultPrevented, false);
  form.entries[2][1] = 'cart';
  assert.equal(f.dispatch('submit', { target: form, submitter }).defaultPrevented, true);
  await tick();
  assert.equal(f.requests.length, 1);
  assert.equal(f.requests[0].data.get('quantity'), '1,75');
  f.requests[0].onSuccess(JSON.stringify(cart));
});

test('invalid configured CSS selectors fall back to the WT attributes', async () => {
  const f = fixture({ list_button_selector: '[', list_quantity_selector: '[' });
  f.fields.local = qtyField('2.25');
  assert.equal(f.dispatch('click', { target: listButton('9', 'local') }).defaultPrevented, true);
  await tick();
  assert.equal(f.requests[0].data.get('quantity'), '2.25');
  f.requests[0].onSuccess(JSON.stringify(cart));
});

test('the plugin updates the marked purchase button from the server quantities', async () => {
  const f = fixture({
    added_button_text: 'In cart',
    added_button_class: 'btn-success in-cart',
    quantity_precision: 2,
  });
  const label = {};
  const classes = [];
  const target = control({
    dataset: { productId: '9', productCategoryId: '2' },
    querySelector: selector => selector === '[wt-jshopping-add-to-cart-label]' ? label : null,
    classList: { add: (...tokens) => classes.push(...tokens) },
  });
  assert.equal(f.dispatch('click', { target }).defaultPrevented, true);
  await tick();
  f.requests[0].onSuccess(JSON.stringify({
    count_product: 4.3,
    price_product: 45,
    products: [
      { product_id: 9, quantity: 0.1 },
      { product_id: 9, quantity: 0.2 },
      { product_id: 10, quantity: 4 },
    ],
  }));
  await tick();
  assert.equal(label.textContent, 'In cart 0.3');
  assert.ok(classes.includes('btn-success') && classes.includes('in-cart'));
  assert.equal(f.messages.length, 1);
});

test('cart events synchronize every button for a product and restore initially added labels at zero', () => {
  const f = fixture({
    added_button_text: 'In cart', added_button_class: 'btn-success', quantity_precision: 2,
    button_text: 'Buy', product_button_text: 'Add to cart',
  });
  function purchaseButton(productId, type = 'button', normalText = null) {
    const label = { textContent: 'In cart 1' };
    const classes = new Set(['btn', 'btn-primary', 'btn-success']);
    const button = control({
      type, dataset: { productId },
      attributes: normalText ? { 'data-wt-jshopping-add-to-cart-text': normalText } : {},
      querySelector: () => label,
      classList: {
        add: (...tokens) => tokens.forEach(token => classes.add(token)),
        remove: (...tokens) => tokens.forEach(token => classes.delete(token)),
      },
    });
    f.controls.add(button);
    return { button, label, classes };
  }
  const list = purchaseButton('9');
  const product = purchaseButton('9', 'submit');
  const custom = purchaseButton('9', 'button', 'Order');
  const other = purchaseButton('10');
  const update = products => f.handlers['wt-jshop-module-cart:updated']({ detail: { cart: { products } } });
  update({ 0: { product_id: 9, quantity: 0.1 }, 3: { product_id: 9, quantity: 0.2 } });
  for (const item of [list, product, custom]) assert.equal(item.label.textContent, 'In cart 0.3');
  assert.equal(other.label.textContent, 'Buy');
  update({});
  assert.equal(list.label.textContent, 'Buy');
  assert.equal(product.label.textContent, 'Add to cart');
  assert.equal(custom.label.textContent, 'Order');
  for (const item of [list, product, custom]) {
    assert.equal(item.classes.has('btn-success'), false);
    assert.equal(item.classes.has('btn-primary'), true);
  }
  assert.equal(Object.keys(f.options.wt_jshop_module_cart.buttons).length, 4);
});

test('the shared cart queue includes response publication before the next mutation', async () => {
  const f = fixture();
  let queue = Promise.resolve();
  const seen = [];
  f.window.wtJshopModuleCart = {
    enqueueMutation(task) {
      queue = queue.then(task);
      return queue;
    },
    applyCart(value) { seen.push(value.count_product); },
  };
  f.dispatch('click', { target: listButton('9') });
  f.dispatch('click', { target: listButton('11') });
  await tick();
  assert.equal(f.requests.length, 1);
  f.requests[0].onSuccess(JSON.stringify(cart));
  await tick();
  assert.deepEqual(seen, [2.5]);
  assert.equal(f.messages.length, 1);
  assert.equal(f.messages[0].message.wtJshopCartApplied, true);
  assert.equal(f.requests.length, 2);
  f.requests[1].onSuccess(JSON.stringify(cart));
  await queue;
  assert.deepEqual(seen, [2.5, 2.5]);
});
