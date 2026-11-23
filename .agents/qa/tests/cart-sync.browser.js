async () => {
    const results = [];
    const tick = () => new Promise(resolve => setTimeout(resolve, 0));
    const check = (condition, message) => { if (!condition) throw new Error(message); };
    const rows = id => document.querySelectorAll('#module-' + id + ' [data-wt-jshop-module-cart-product-number]');
    const labels = () => Array.from(document.querySelectorAll('[wt-jshopping-add-to-cart-label]'), node => node.textContent);
    const quantities = () => Joomla.getOptions('wt_jshop_module_cart_product_qunatities');
    const first = { product_id: 9, category_id: 2, product_name: 'Variant one', price: 10.25, quantity: 1, thumb_image: 'first.png' };
    const second = {
        product_id: 9, category_id: 2, product_name: '<Variant two>', price: 12.5, quantity: 2, thumb_image: 'second.png',
        attributes_value: [{ attr: 'Colour', value: 'Blue' }], free_attributes_value: [{ attr: 'Text', value: '<engraving>' }]
    };
    const cart = products => ({ products, count_product: Object.values(products).reduce((sum, p) => sum + p.quantity, 0),
        price_product: Object.values(products).reduce((sum, p) => sum + p.quantity * p.price, 0) });
    const nextRequest = async () => {
        await tick();
        check(requests.length === 1, 'Expected exactly one active request, got ' + requests.length);
        return requests.shift();
    };
    const confirmTotal = async (products, summ) => {
        await tick();
        const views = requests.filter(request => request.url.includes('task=view&ajax=2'));
        check(views.length === 1 && views[0].method === 'GET', 'One shared cart view after a successful change');
        const view = requests.splice(requests.indexOf(views[0]), 1)[0];
        const routed = Object.fromEntries(Object.entries(products).map(([key, product]) =>
            [key, { ...product, href: '/shop/variant-product' }]));
        const result = cart(routed);
        result.summ = summ === undefined ? result.price_product : summ;
        view.onSuccess(JSON.stringify(result));
        await tick();
    };
    const respond = async (request, products, summ) => {
        request.onSuccess(JSON.stringify({ ...cart(products), summ: 0 }));
        await confirmTotal(products, summ);
    };
    const taskButton = (id, key, task, direction = '') => document.querySelector('#module-' + id
        + ' [data-wt-jshop-module-cart-product-number="' + key + '"] [data-product-task="' + task + '"]'
        + (direction ? '[data-quantity="' + direction + '"]' : ''));

    check(requests.length === 0 && rows(1).length === 1 && rows(2).length === 1, 'Initial module state');
    check(labels().every(text => text === 'In cart 1'), 'Initial button state');
    document.getElementById('product-buy').click();
    let request = await nextRequest();
    check(request.method === 'POST' && request.data.get('jshop_attr_id[1]') === '17', 'Product form attributes');
    await respond(request, { 0: first, 4: second }, 30.25);
    check(rows(1).length === 2 && rows(2).length === 2, 'New attributed row in both modules');
    check(labels().every(text => text === 'In cart 3'), 'All list and product button quantities');
    check(Array.from(document.querySelectorAll('[data-wt-jshop-module-cart-price-total]')).every(e => e.textContent === '30.25 RUB'), 'Both modules display the discounted view total');
    check(document.querySelector('#module-1 [data-wt-jshop-module-cart-product-number="4"] [data-wt-jshop-module-cart-product-name]').textContent === '<Variant two>', 'Escaped product name');
    check(document.querySelector('#module-1 [data-wt-jshop-module-cart-product-number="4"] [data-wt-jshop-module-cart-product-attributes]').textContent === 'Colour: Blue, Text: <engraving>', 'Variant and free attributes');
    check(!document.querySelector('#module-2 [data-wt-jshop-module-cart-product-image]'), 'Independent image setting');
    check(!document.querySelector('#module-2 [data-wt-jshop-module-cart-product-attributes]'), 'Independent attribute setting');
    check([1, 2].every(id => document.querySelector('#module-' + id + ' [data-wt-jshop-module-cart-product-number="4"] a').pathname === '/shop/variant-product'), 'Both new rows use the server product route');
    results.push({ scenario: 'Add a second variant through the product form; both modules and all buttons agree', passed: true });

    const up = taskButton(2, 4, 'quantity', 'up');
    up.innerHTML = '<span>+</span>';
    up.firstElementChild.click();
    request = await nextRequest();
    check(request.data.get('quantity[4]') === '3' && quantities()[4] === 2, 'Delegation and confirmed cache');
    await respond(request, { 0: first, 4: Object.assign({}, second, { quantity: 3 }) }, 0);
    check(Array.from(document.querySelectorAll('[data-wt-jshop-module-cart-price-total]')).every(e => e.textContent === '0.00 RUB'), 'A full discount keeps a zero total with products present');
    check(labels().every(text => text === 'In cart 4'), 'Refresh updates every button');
    results.push({ scenario: 'Nested click target in a newly inserted row updates the confirmed quantity', passed: true });

    taskButton(2, 4, 'delete').click();
    request = await nextRequest();
    check(request.url.includes('number_id=4'), 'Position key for deletion');
    await respond(request, { 0: first });
    check(rows(1).length === 1 && rows(2).length === 1 && !Object.hasOwn(quantities(), 4), 'Delete in every module and cache');
    check(labels().every(text => text === 'In cart 1'), 'Remaining variant aggregate');
    taskButton(1, 0, 'delete').click();
    request = await nextRequest();
    await respond(request, []);
    check(rows(1).length === 0 && rows(2).length === 0 && Object.keys(quantities()).length === 0, 'Last variant deleted');
    check(labels().join('|') === 'Buy|Buy|Add to cart', 'Normal labels restored after initially added labels');
    check(!document.querySelector('[wt-jshopping-add-to-cart-label]').parentElement.classList.contains('btn-success'), 'Added class removed');
    check(Array.from(document.querySelectorAll('[data-wt-jshop-module-cart-empty]')).every(e => !e.hidden), 'Empty messages');
    check(Array.from(document.querySelectorAll('[data-wt-jshop-module-cart-products]')).every(e => e.hidden), 'Empty lists');
    results.push({ scenario: 'Delete one and then the last variant; restore normal labels, classes and empty state', passed: true });

    document.getElementById('custom-buy').click();
    document.getElementById('custom-buy').click();
    request = await nextRequest();
    check(request.data.get('quantity') === '1.5', 'Plugin quantity selector');
    await respond(request, { 5: Object.assign({}, second, { quantity: 1.5 }) });
    check(rows(1).length === 1 && rows(2).length === 1 && labels().every(text => text === 'In cart 1.5'), 'First add into empty modules');
    check(requests.length === 0, 'Repeat add click collapsed');
    results.push({ scenario: 'Add into empty modules using the shared plugin selectors and a fractional quantity', passed: true });

    taskButton(1, 5, 'quantity', 'up').click();
    taskButton(2, 5, 'quantity', 'up').click();
    request = await nextRequest();
    check(request.data.get('quantity[5]') === '2.5', 'First queued increment');
    await respond(request, { 5: Object.assign({}, second, { quantity: 2.5 }) });
    request = await nextRequest();
    check(request.data.get('quantity[5]') === '3.5', 'Next increment uses the server result');
    request.onError({ status: 500 });
    await tick();
    check(quantities()[5] === 2.5 && notices.length === 1 && requests.length === 0, 'A rejected change preserves the cart without requesting a total');
    taskButton(2, 5, 'quantity', 'up').click();
    request = await nextRequest();
    check(request.data.get('quantity[5]') === '3.5', 'Queue resumes after error');
    await respond(request, { 5: Object.assign({}, second, { quantity: 3.5 }) });
    results.push({ scenario: 'Serialize rapid increments and recover after a failed refresh without a stale cache', passed: true });

    document.getElementById('list-buy').click();
    taskButton(2, 5, 'delete').click();
    request = await nextRequest();
    check(request.url.includes('task=add'), 'Add starts first');
    await respond(request, { 5: Object.assign({}, second, { quantity: 4.5 }) });
    request = await nextRequest();
    check(request.url.includes('task=delete'), 'Delete waits for add publication');
    await respond(request, []);
    check(labels().join('|') === 'Buy|Buy|Add to cart', 'Shared mutation queue final state');
    window.dispatchEvent(new MessageEvent('message', { origin: location.origin, source: window,
        data: { messageType: 'wt-jshop-module-cart:update-products-count', cart: cart({ 0: first }), wtJshopCartApplied: true } }));
    window.dispatchEvent(new MessageEvent('message', { origin: 'https://other.test',
        data: { messageType: 'wt-jshop-module-cart:update-products-count', cart: cart({ 0: first }) } }));
    window.dispatchEvent(new MessageEvent('message', { origin: location.origin,
        data: { messageType: 'wt-jshop-module-cart:update-products-count', cart: { products: { 0: null } } } }));
    check(rows(1).length === 0, 'Ignore an already applied, cross-origin or malformed message');
    results.push({ scenario: 'Add and delete share a queue; stale outbound and malformed messages do not revert the cart', passed: true });

    const main = document.createElement('div');
    main.id = 'comjshop';
    main.innerHTML = '<form name="updateCart"></form>';
    document.body.appendChild(main);
    wtJshopModuleCart.applyCart(cart({ 0: first }));
    await confirmTotal({ 0: first }, 8);
    const oldView = await nextRequest();
    wtJshopModuleCart.applyCart(cart({ 4: second }));
    await confirmTotal({ 4: second }, 20);
    const newView = await nextRequest();
    newView.onSuccess('<p>Current cart page</p>');
    await tick();
    oldView.onSuccess('<p>Obsolete cart page</p>');
    await tick();
    check(main.textContent === 'Current cart page', 'Obsolete cart HTML ignored');
    results.push({ scenario: 'Out-of-order cart page responses preserve the latest HTML', passed: true });
    window.cartVerification = { browser: navigator.userAgent, passed: results.length, failed: 0, results };
    return window.cartVerification;
}
