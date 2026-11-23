async () => {
    const results = [];
    const requests = [];
    const errors = [];
    const check = (condition, description) => { if (!condition) throw new Error(description); };
    const waitFor = async (predicate, description) => {
        const deadline = Date.now() + 120000;
        while (!predicate()) {
            if (Date.now() > deadline) throw new Error(description);
            await new Promise(resolve => setTimeout(resolve, 50));
        }
    };
    window.addEventListener('error', event => { if (event.message) errors.push(event.message); });
    window.wtLiveCatalogProgress = { results, requests, errors };
    const originalRequest = Joomla.request;
    Joomla.request = options => {
        const record = { url: options.url, method: options.method, status: 'pending', data: {} };
        if (options.data instanceof FormData) {
            for (const [key, value] of options.data) {
                if (/^(?:product_id|category_id|quantity(?:\[\d+\])?|to)$/.test(key)) record.data[key] = value;
            }
        }
        requests.push(record);
        return originalRequest.call(Joomla, Object.assign({}, options, {
            onSuccess(response, xhr) {
                record.status = xhr.status;
                try {
                    const data = JSON.parse(response);
                    record.count_product = data.count_product;
                    record.messages = Array.isArray(data) ? data : undefined;
                    record.positions = data.products ? Object.keys(data.products) : [];
                } catch (error) { record.parseError = error.message; }
                return options.onSuccess(response, xhr);
            },
            onError(xhr) {
                record.status = xhr.status;
                if (options.onError) return options.onError(xhr);
            }
        }));
    };
    const state = () => Joomla.getOptions('wt_jshop_module_cart').cart;
    const confirmedCount = count => Number(state().count_product) === count && Number.isFinite(state().summ);
    const cartModules = Array.from(document.querySelectorAll('[data-wt-jshop-module-cart]')).filter(module => module.querySelector('[data-wt-jshop-module-cart-products]'));
    const verify = count => {
        check(Number(state().count_product) === count, 'Server cart count ' + count);
        const keys = Object.keys(state().products).sort();
        cartModules.forEach(module => {
            const rows = Array.from(module.querySelectorAll('[data-wt-jshop-module-cart-product-number]'));
            check(JSON.stringify(rows.map(row => row.getAttribute('data-wt-jshop-module-cart-product-number')).sort()) === JSON.stringify(keys), 'Module position keys agree with the real server');
            check(module.querySelector('[data-wt-jshop-module-cart-empty]').hidden === (count > 0), 'Empty state agrees');
        });
        check(new Set(cartModules.map(module => module.querySelector('[data-wt-jshop-module-cart-price-total]').textContent)).size === 1, 'Both module totals agree');
        document.querySelectorAll('.wt-jshopping-cart-module-icon .digit').forEach(node => check(Number(node.textContent) === count, 'Icon count agrees'));
    };
    check(location.host === 'joomla.local', 'Run against the requested real shop');
    check(cartModules.length === 2 && state().count_product === 0, 'Two cart module bodies and an initially empty guest cart');
    check(document.querySelectorAll('script[src*="plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart/js/wtjshopping-add-to-cart.js"]').length === 1, 'One plugin asset for all instances');
    check(!document.querySelector('script[src*="media/mod_wtjshoppingcart/js/wtjshopping-add-to-cart.js"]'), 'Asset loaded from the plugin');
    check(!document.body.textContent.includes('Undefined array key'), 'No JoomShopping product URL warnings');
    const button = document.querySelector('.wt-e2e-buy[data-product-id="3"]');
    check(button && !button.hasAttribute('wt-jshopping-add-to-cart-btn'), 'Configured CSS path works without a WT button marker');
    const initialUrl = location.href;
    const quantity = button.closest('.product').querySelector('.wt-e2e-list-qty');
    quantity.value = '2';
    button.querySelector('[wt-jshopping-add-to-cart-label]').click();
    await waitFor(() => confirmedCount(2) && !button.hasAttribute('aria-busy'), 'Real catalogue add did not update the cart');
    check(location.href === initialUrl, 'AJAX purchase stays on the catalogue');
    verify(2);
    check(state().summ === 2200, 'Server total for 2 x 1100');
    check(button.textContent.trim() === Joomla.getOptions('wt_jshop_add_to_cart').added_button_text + ' 2', 'Purchase button shows real cart quantity');
    results.push({ scenario: 'Full CSS catalogue button and quantity paths add into two initially empty modules through a real POST', passed: true });

    document.querySelector('.wt-jshopping-cart-module-icon').click();
    await waitFor(() => document.querySelector('.offcanvas.show'), 'Native offcanvas did not open');
    const offcanvas = document.querySelector('.offcanvas.show');
    check(getComputedStyle(offcanvas).visibility === 'visible', 'The native offcanvas is visible with the installed site theme');
    const up = offcanvas.querySelector('[data-product-task="quantity"][data-quantity="up"]');
    up.click();
    up.click();
    await waitFor(() => confirmedCount(4), 'Queued real refreshes did not produce quantity four');
    verify(4);
    check(state().summ === 4400, 'Server total after refreshes');
    const refreshes = requests.filter(r => r.url.includes('task=refresh'));
    check(refreshes.length === 2 && Object.values(refreshes[0].data).includes('3') && Object.values(refreshes[1].data).includes('4'), 'Second real refresh uses the first response');
    results.push({ scenario: 'Native offcanvas opens and rapid increments serialize real refresh requests and update both modules and the icon', passed: true });

    offcanvas.querySelector('[data-product-task="delete"]').click();
    await waitFor(() => confirmedCount(0), 'Real deletion did not empty the cart');
    verify(0);
    check(button.textContent.trim() === Joomla.getOptions('wt_jshop_add_to_cart').button_text, 'Restore ordinary purchase label after deletion');
    check(!button.classList.contains('btn-success'), 'Remove added-state class');
    results.push({ scenario: 'Delete the last real position and restore every empty state and the ordinary button label', passed: true });

    bootstrap.Offcanvas.getInstance(offcanvas).hide();
    const productId = button.dataset.productId;
    button.dataset.productId = '999999999';
    quantity.value = '1';
    const beforeError = requests.length;
    button.click();
    await waitFor(() => requests.length > beforeError && !button.hasAttribute('aria-busy'), 'Invalid product request did not finish');
    check(requests[requests.length - 1].messages?.length > 0, 'JoomShopping returned a real product validation message');
    verify(0);
    check(document.querySelector('#system-message-container')?.textContent.trim(), 'Display the real shop validation error');
    button.dataset.productId = productId;
    button.click();
    await waitFor(() => confirmedCount(1) && !button.hasAttribute('aria-busy'), 'Queue did not recover after real product error');
    verify(1);
    offcanvas.querySelector('[data-product-task="delete"]').click();
    await waitFor(() => confirmedCount(0), 'Final cleanup deletion failed');
    verify(0);
    results.push({ scenario: 'A real product validation error preserves the confirmed cart and a subsequent valid purchase succeeds', passed: true });
    const serverResponse = await fetch('/index.php?option=com_jshopping&controller=cart&task=view&ajax=2&wt_e2e=' + Date.now(), { cache: 'no-store' });
    const persisted = await serverResponse.json();
    check(Number(persisted.count_product) === 0, 'The server cart remains empty after cleanup');
    check(!errors.length && requests.every(request => request.status === 200 && !request.parseError), 'No browser exceptions or malformed cart responses');
    const report = { site: location.origin, url: location.href, browser: navigator.userAgent, passed: results.length, failed: 0, results, requests, errors, persistedCount: persisted.count_product };
    window.wtLiveCatalogReport = report;
    return report;
}
