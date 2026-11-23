async () => {
    const phase = window.wtLiveVariantsPhase || 'add';
    const results = [];
    const requests = [];
    const check = (condition, description) => { if (!condition) throw new Error(description); };
    const waitFor = async (predicate, description) => {
        const deadline = Date.now() + 120000;
        while (!predicate()) {
            if (Date.now() > deadline) throw new Error(description);
            await new Promise(resolve => setTimeout(resolve, 50));
        }
    };
    const state = () => Joomla.getOptions('wt_jshop_module_cart').cart;
    const confirmedCount = count => Number(state().count_product) === count && Number.isFinite(state().summ);
    const settings = Joomla.getOptions('wt_jshop_add_to_cart');
    const form = document.forms.product;
    const button = form.querySelector('button.wt-e2e-buy');
    const modules = Array.from(document.querySelectorAll('[data-wt-jshop-module-cart]')).filter(module => module.querySelector('[data-wt-jshop-module-cart-products]'));
    const offcanvas = document.querySelector('.offcanvas');
    const originalRequest = Joomla.request;
    window.wtLiveVariantsProgress = { phase, results, requests };
    Joomla.request = options => {
        const record = { url: options.url, method: options.method, data: {}, status: 'pending' };
        if (options.data instanceof FormData) {
            for (const [key, value] of options.data) {
                if (/^(?:product_id|category_id|quantity(?:\[\d+\])?|jshop_attr_id\[\d+\]|to)$/.test(key)) record.data[key] = value;
            }
        }
        requests.push(record);
        return originalRequest.call(Joomla, Object.assign({}, options, {
            onSuccess(response, xhr) {
                record.status = xhr.status;
                const data = JSON.parse(response);
                record.count_product = data.count_product;
                record.products = data.products;
                record.messages = Array.isArray(data) ? data : undefined;
                return options.onSuccess(response, xhr);
            },
            onError(xhr) { record.status = xhr.status; return options.onError && options.onError(xhr); }
        }));
    };
    const verify = count => {
        check(Number(state().count_product) === count, 'Real variant cart count');
        const keys = Object.keys(state().products).sort();
        modules.forEach(module => {
            const rows = Array.from(module.querySelectorAll('[data-wt-jshop-module-cart-product-number]'));
            check(JSON.stringify(rows.map(row => row.getAttribute('data-wt-jshop-module-cart-product-number')).sort()) === JSON.stringify(keys), 'Both modules retain distinct variant position keys');
            check(module.querySelector('[data-wt-jshop-module-cart-empty]').hidden === (count > 0), 'Variant empty state');
        });
        check(new Set(modules.map(module => module.querySelector('[data-wt-jshop-module-cart-price-total]').textContent)).size === 1, 'Variant totals agree in both modules');
        document.querySelectorAll('.wt-jshopping-cart-module-icon .digit').forEach(node => check(Number(node.textContent) === count, 'Variant icon count'));
        check(button.querySelector('[wt-jshopping-add-to-cart-label]').textContent === (count ? settings.added_button_text + ' ' + count : settings.product_button_text), 'Aggregate product button label');
    };
    check(location.host === 'joomla.local' && form && button && modules.length === 2, 'Real product view and both module bodies');
    check(button.matches(settings.product_button_selector) && !button.hasAttribute('wt-jshopping-add-to-cart-btn'), 'Use the stored full product CSS path');
    if (phase === 'add') {
        verify(0);
        const choose = async colour => {
            const colourField = form.elements['jshop_attr_id[2]'];
            const sizeField = form.elements['jshop_attr_id[3]'];
            colourField.value = colour;
            colourField.dispatchEvent(new Event('change', { bubbles: true }));
            sizeField.value = '3';
            sizeField.dispatchEvent(new Event('change', { bubbles: true }));
            form.elements.quantity.value = '1';
            await new Promise(resolve => setTimeout(resolve, 500));
            check(colourField.value === colour && sizeField.value === '3', 'Native JoomShopping retains the selected attributes');
        };
        await choose('2');
        button.querySelector('[wt-jshopping-add-to-cart-label]').click();
        await waitFor(() => confirmedCount(1) && !button.hasAttribute('aria-busy'), 'First real attributed purchase');
        verify(1);
        check(requests[0].data['jshop_attr_id[2]'] === '2' && requests[0].data['jshop_attr_id[3]'] === '3', 'Send selected attribute fields');
        results.push({ scenario: 'The real product form submits its selected attributes using the plugin CSS paths', passed: true });

        await choose('0');
        button.querySelector('[wt-jshopping-add-to-cart-label]').click();
        await waitFor(() => confirmedCount(2) && !button.hasAttribute('aria-busy'), 'Second real attributed purchase');
        verify(2);
        const products = Object.values(state().products);
        check(products.length === 2 && products.every(product => Number(product.product_id) === 8), 'Two real cart positions for one product');
        check(JSON.stringify(products[0].attributes_value) !== JSON.stringify(products[1].attributes_value), 'Real server variants differ');
        const attributes = Array.from(offcanvas.querySelectorAll('[data-wt-jshop-module-cart-product-attributes]'), node => node.textContent);
        check(attributes.length === 2 && attributes[0] !== attributes[1], 'Show the correct attributes for both variant rows');
        const list = modules.find(module => module !== offcanvas);
        check(!list.querySelector('[data-wt-jshop-module-cart-product-image]') && !list.querySelector('[data-wt-jshop-module-cart-product-attributes]'), 'Preserve individual image and attribute settings');
        results.push({ scenario: 'A second variant creates a second row in both modules while the purchase button aggregates the product quantity', passed: true });
        document.querySelector('.wt-jshopping-cart-module-icon').click();
        await waitFor(() => offcanvas.classList.contains('show'), 'Open the native variant offcanvas');
        check(getComputedStyle(offcanvas).visibility === 'visible', 'Variant offcanvas is visible');
    } else {
        verify(2);
        check(Object.keys(state().products).length === 2, 'Server-rendered cart preserves both variants after navigation');
        results.push({ scenario: 'A real page navigation through the newly generated product link preserves both variants and the server-rendered button state', passed: true });
        document.querySelector('.wt-jshopping-cart-module-icon').click();
        await waitFor(() => offcanvas.classList.contains('show'), 'Open native offcanvas after navigation');
        offcanvas.querySelector('[data-product-task="delete"]').click();
        await waitFor(() => confirmedCount(1), 'Delete one real variant');
        verify(1);
        results.push({ scenario: 'Deleting one real variant removes it from both modules and changes the product button quantity to one', passed: true });
        offcanvas.querySelector('[data-product-task="delete"]').click();
        await waitFor(() => confirmedCount(0), 'Delete last real variant');
        verify(0);
        check(!button.classList.contains('btn-success'), 'Restore normal product button styling after the last deletion');
        results.push({ scenario: 'Deleting the last real variant restores both empty carts, the icon and the normal product form button', passed: true });
        bootstrap.Offcanvas.getInstance(offcanvas).hide();
        const serverResponse = await fetch('/index.php?option=com_jshopping&controller=cart&task=view&ajax=2&wt_e2e=' + Date.now(), { cache: 'no-store' });
        const persisted = await serverResponse.json();
        check(Number(persisted.count_product) === 0, 'Both variant deletions persist in the real server cart');
    }
    check(requests.every(request => request.status === 200 && !request.messages), 'All real variant mutation requests succeeded');
    const report = {
        site: location.origin, url: location.href, phase, passed: results.length, failed: 0, results, requests,
        newProductUrl: offcanvas.querySelector('[data-wt-jshop-module-cart-product-link]')?.href,
        finalCount: state().count_product,
    };
    window.wtLiveVariantsReport = report;
    return report;
}
