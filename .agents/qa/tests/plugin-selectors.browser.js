async () => {
    const results = [];
    const check = (condition, description) => { if (!condition) throw new Error(description); };
    const tick = () => new Promise(resolve => setTimeout(resolve, 0));
    const nextRequest = async () => {
        await tick();
        check(requests.length === 1, 'One request for one purchase');
        return requests.shift();
    };
    const reply = async (request, quantity) => {
        const cart = {
            products: { 0: { product_id: 9, category_id: 2, product_name: 'Product', quantity, price: 10.25 } },
            count_product: quantity, price_product: quantity * 10.25,
        };
        request.onSuccess(JSON.stringify({ ...cart, summ: 0 }));
        const view = await nextRequest();
        check(view.method === 'GET' && view.url.includes('task=view&ajax=2'), 'Read the updated server total once');
        view.onSuccess(JSON.stringify({ ...cart, summ: cart.price_product }));
        await tick();
    };
    const options = Joomla.getOptions('wt_jshop_add_to_cart');
    const labels = () => Array.from(document.querySelectorAll('[wt-jshopping-add-to-cart-label]'), e => e.textContent);
    const mode = new URL(location.href).searchParams.get('selectors');
    if (mode === 'invalid') {
        document.getElementById('list-buy').querySelector('span').click();
        let request = await nextRequest();
        check(request.data.get('quantity') === '1', 'Invalid list selector falls back to the WT button');
        await reply(request, 2);
        document.querySelector('#product-form [name="quantity"]').value = '2.25';
        document.getElementById('product-buy').click();
        request = await nextRequest();
        check(request.data.get('quantity') === '2.25', 'Invalid product selectors fall back to WT/form quantity');
        await reply(request, 4.25);
        results.push({ scenario: 'Invalid plugin selectors fall back to working WT list and product controls', passed: true });
    } else {
        check(options.list_button_selector.includes(' > ') && options.list_button_selector.includes(':is('), 'Use CSS paths emitted by the real plugin fixture');
        check(Joomla.getOptions('wt_jshop_module_cart_module_options').modules[2].list_button_selector === '[obsolete-module-selector]', 'Conflicting legacy module options present');
        document.querySelectorAll('[wt-jshopping-add-to-cart-btn]').forEach(e => e.removeAttribute('wt-jshopping-add-to-cart-btn'));
        document.querySelectorAll('[wt-jshopping-add-to-cart-qty]').forEach(e => e.removeAttribute('wt-jshopping-add-to-cart-qty'));
        const custom = document.getElementById('custom-buy');
        custom.querySelector('[wt-jshopping-add-to-cart-label]').innerHTML = '<strong><span>Order</span></strong>';
        custom.querySelector('strong span').click();
        let request = await nextRequest();
        check(request.data.get('quantity') === '1.5' && request.data.get('product_id') === '9', 'Full list button/quantity paths and nested click target');
        await reply(request, 2.5);
        check(labels().every(value => value === 'In cart 2.5'), 'Plugin settings apply to all list and product controls');
        check(Array.from(document.querySelectorAll('.wt-jshopping-cart-module-icon .digit')).every(e => e.textContent === '2.5'), 'Both cart module counters');
        check(Array.from(document.querySelectorAll('[data-wt-jshop-module-cart-product-quantity]')).every(e => e.textContent === '2.5'), 'Both module rows');
        results.push({ scenario: 'Full CSS list paths with child combinators and :is alternatives handle nested targets and ignore legacy module settings', passed: true });

        const form = document.getElementById('product-form');
        form.querySelector('[name="quantity"]').value = '2.75';
        document.getElementById('product-buy').querySelector('span').click();
        request = await nextRequest();
        check(request.method === 'POST' && request.data.get('quantity') === '2.75', 'Full form quantity path');
        check(request.data.get('jshop_attr_id[1]') === '17', 'Preserve product attribute fields');
        await reply(request, 5.25);
        check(labels().every(value => value === 'In cart 5.25'), 'Full product button selector updates all labels');
        results.push({ scenario: 'Full product form button and quantity paths work without WT button/quantity markers and preserve attributes', passed: true });

        document.getElementById('list-buy').click();
        request = await nextRequest();
        check(request.data.get('quantity') === '1', 'Do not read a quantity from a neighboring product');
        await reply(request, 6.25);
        results.push({ scenario: 'A global quantity path stays scoped to the clicked product', passed: true });

        const oldProduct = document.getElementById('custom-buy').closest('.product');
        const replacement = oldProduct.cloneNode(true);
        oldProduct.remove();
        document.body.appendChild(replacement);
        replacement.querySelector('[site-qty]').value = '1.25';
        replacement.querySelector('[wt-jshopping-add-to-cart-label]').click();
        request = await nextRequest();
        check(request.data.get('quantity') === '1.25', 'Delegation handles new controls matching the full CSS path');
        await reply(request, 7.5);
        check(labels().every(value => value === 'In cart 7.5'), 'One configuration for dynamic controls and all modules');
        check(Joomla.getOptions('wt_jshop_add_to_cart').list_button_selector === options.list_button_selector, 'Module updates preserve the plugin configuration');
        results.push({ scenario: 'Dynamically inserted product controls use the same full CSS paths and global plugin options', passed: true });
    }
    const report = { browser: navigator.userAgent, mode, passed: results.length, failed: 0, results };
    window.pluginSelectorsVerification = report;
    return report;
}
