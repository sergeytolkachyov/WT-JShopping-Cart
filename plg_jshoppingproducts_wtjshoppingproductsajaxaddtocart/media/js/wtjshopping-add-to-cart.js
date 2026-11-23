/**
 * @file AJAX add-to-cart web asset for WT JShopping Products AJAX Add to Cart.
 *
 * @version    1.2.0
 * @author     Sergey Tolkachyov
 * @copyright  Copyright (c) 2022 - 2026 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @since      1.2.0
 * @see        https://web-tolk.ru
 */

((Joomla) => {
    'use strict';

    const buttonAttribute = 'wt-jshopping-add-to-cart-btn';
    const buttonSelector = '[' + buttonAttribute + ']';
    const quantitySelector = '[wt-jshopping-add-to-cart-qty]';
    const legacyListSelector = '#comjshop_list_product, .jshop_list_product';
    const legacyLinkSelector = '#comjshop_list_product a.button_buy[href], .jshop_list_product a.button_buy[href]';
    const settings = Joomla.getOptions('wt_jshop_add_to_cart', {});
    const pending = new WeakSet();
    let queue = Promise.resolve();
    let buttonNumber = 0;

    const readSelector = (value, fallback) => {
        const selector = typeof value === 'string' ? value.trim() : '';
        if (!selector) return fallback;

        try {
            document.querySelector(selector);
            return selector;
        } catch (error) {
            if (error.name === 'SyntaxError') return fallback;
            throw error;
        }
    };

    // Keep full CSS paths intact for the browser's selector engine.
    const listButtonSelector = readSelector(settings.list_button_selector, buttonSelector) + ', ' + legacyLinkSelector;
    const productButtonSelector = readSelector(settings.product_button_selector, buttonSelector);
    const listQuantitySelector = readSelector(settings.list_quantity_selector, quantitySelector);
    const productQuantitySelector = readSelector(settings.product_quantity_selector, quantitySelector);

    const sameOriginUrl = value => {
        if (!value) return null;
        try {
            const url = new URL(value, document.baseURI);
            return url.origin === window.location.origin ? url : null;
        } catch (error) {
            if (error instanceof TypeError) return null;
            throw error;
        }
    };

    const isProductId = value => /^[0-9]+$/.test(value) && Number(value) > 0;

    const isCartAddLink = url => {
        const params = url.searchParams;
        const option = params.get('option');
        const controller = params.get('controller');
        const task = params.get('task');
        const cart = params.get('to');
        if (!isProductId(params.get('product_id')) || (option && option !== 'com_jshopping') || (cart && cart !== 'cart')) {
            return false;
        }
        if (controller || task) {
            return controller === 'cart' && task === 'add';
        }
        // The stock SEF router keeps the product ID in the query string.
        return /\/add(?:\.html)?\/?$/i.test(url.pathname);
    };

    const quantityValue = field => field && !field.disabled && typeof field.value === 'string' ? field.value.trim() : '';
    const quantityIn = (container, selector) => container ? quantityValue(container.querySelector(selector)) : '';

    const linkedQuantity = (control, selector) => {
        const id = control.getAttribute(buttonAttribute);
        const field = id && document.getElementById(id);
        return field && field.matches(selector) ? quantityValue(field) : '';
    };

    const listQuantityFor = control => {
        const linked = linkedQuantity(control, listQuantitySelector);
        if (linked) return linked;

        const form = control.form;
        if (form && !form.contains(control)) return quantityIn(form, listQuantitySelector);

        const item = control.closest('.product');
        if (item) return quantityIn(item, listQuantitySelector);

        for (let parent = control.parentElement; parent && parent !== document.body; parent = parent.parentElement) {
            if (parent.matches(legacyListSelector) || parent.querySelectorAll(listButtonSelector).length > 1) break;
            const quantity = quantityIn(parent, listQuantitySelector);
            if (quantity || parent.tagName === 'FORM') return quantity;
        }

        return '';
    };

    const escapeMessage = message => {
        const text = document.createElement('span');
        text.textContent = message;
        return text.innerHTML;
    };

    const showRequestError = () => {
        const fallback = Joomla.Text._('JERROR_LAYOUT_ERROR_HAS_OCCURRED_WHILE_PROCESSING_YOUR_REQUEST');
        const message = Joomla.Text._('WT_JSHOP_ADD_TO_CART_ERROR', fallback);
        Joomla.renderMessages({ error: [escapeMessage(message)] });
    };

    const updateButton = (control, cart, productId) => {
        if (!settings.added_button_text || !productId) return;
        const label = control.querySelector && control.querySelector('[wt-jshopping-add-to-cart-label]');
        if (!label) return;

        const quantity = Object.keys(cart.products).reduce((total, key) => {
            const row = cart.products[key];
            return Number(row.product_id) === Number(productId) ? total + Number(row.quantity) : total;
        }, 0);
        if (!Number.isFinite(quantity)) return;
        if (!Joomla.getOptions('wt_jshop_module_cart')) Joomla.loadOptions({ wt_jshop_module_cart: {} });
        const state = Joomla.getOptions('wt_jshop_module_cart');
        state.buttons = state.buttons || {};
        let key = control.getAttribute('data-wt-jshopping-cart-button');
        if (!key) {
            key = 'button-' + ++buttonNumber;
            control.setAttribute('data-wt-jshopping-cart-button', key);
        }
        if (!state.buttons[key]) {
            const productButton = control.type === 'submit';
            state.buttons[key] = {
                productId,
                text: control.getAttribute('data-wt-jshopping-add-to-cart-text')
                    || (productButton ? settings.product_button_text : settings.button_text)
                    || Joomla.Text._(productButton ? 'JSHOP_ADD_TO_CART' : 'JSHOP_BUY', '')
            };
        }
        const displayedQuantity = Number(quantity.toFixed(settings.quantity_precision));
        label.textContent = quantity > 0 ? settings.added_button_text + ' ' + displayedQuantity : state.buttons[key].text;
        const classes = (settings.added_button_class || '').split(/\s+/).filter(Boolean);
        if (classes.length) {
            if (quantity > 0) control.classList.add(...classes);
            else control.classList.remove(...classes);
        }
    };

    const productIdFor = control => {
        if (control.dataset && isProductId(control.dataset.productId)) return control.dataset.productId;
        if (control.tagName === 'A') {
            const url = sameOriginUrl(control.href);
            return url && isCartAddLink(url) ? url.searchParams.get('product_id') : null;
        }
        const form = control.form;
        const field = form && form.querySelector('[name="product_id"]');
        if (field && isProductId(field.value)) return field.value;
        const url = form && sameOriginUrl(control.getAttribute('formaction') || form.action);
        return url && url.searchParams.get('product_id');
    };

    const updateButtons = cart => {
        if (!cart || !cart.products) return;
        document.querySelectorAll(listButtonSelector + ', ' + productButtonSelector).forEach(control => {
            updateButton(control, cart, productIdFor(control));
        });
    };

    document.addEventListener('wt-jshop-module-cart:updated', event => updateButtons(event.detail.cart));
    document.addEventListener('DOMContentLoaded', () => updateButtons(Joomla.getOptions('wt_jshop_module_cart', {}).cart));

    const publishResponse = (response, control, productId) => {
        const cart = JSON.parse(response);
        if (Array.isArray(cart)) {
            const messages = cart.filter(entry => entry && typeof entry.message === 'string' && entry.message.trim())
                .map(entry => escapeMessage(entry.message));
            if (messages.length) {
                Joomla.renderMessages({ error: messages });
                return;
            }
        }
        if (!cart || !cart.products || typeof cart.products !== 'object' || cart.count_product == null || cart.price_product == null
            || !Number.isFinite(Number(cart.count_product)) || !Number.isFinite(Number(cart.price_product))
            || !Object.keys(cart.products).every(key => cart.products[key]
                && Number.isFinite(Number(cart.products[key].quantity)) && Number(cart.products[key].quantity) > 0)) {
            showRequestError();
            return;
        }

        // Quantity controls read Joomla's existing options for their next cart refresh.
        const quantities = Joomla.getOptions('wt_jshop_module_cart_product_qunatities', {});
        if (quantities) {
            const updatedQuantities = Object.keys(cart.products).map(key => [key, cart.products[key].quantity]);
            Object.keys(quantities).forEach(key => delete quantities[key]);
            updatedQuantities.forEach(([key, quantity]) => {
                quantities[key] = quantity;
            });
        }
        const api = window.wtJshopModuleCart;
        const cartMessage = { messageType: 'wt-jshop-module-cart:update-products-count', cart };
        if (api && typeof api.applyCart === 'function') {
            api.applyCart(cart);
            cartMessage.wtJshopCartApplied = true;
        } else {
            updateButtons(cart);
            updateButton(control, cart, productId);
        }
        window.postMessage(cartMessage, window.location.origin);

        const message = Joomla.Text._('WT_JSHOP_ADD_TO_CART_SUCCESS', '');
        if (message) Joomla.renderMessages({ message: [escapeMessage(message)] });
    };

    const addToCart = (control, url, data = null) => {
        if (pending.has(control)) return;
        pending.add(control);
        control.setAttribute('aria-busy', 'true');
        url.searchParams.set('ajax', '1');
        if (data) data.set('ajax', '1');
        const productId = (data && data.get('product_id')) || url.searchParams.get('product_id');

        const finish = () => {
            pending.delete(control);
            control.removeAttribute('aria-busy');
        };

        const task = () => new Promise((resolve, reject) => {
            Joomla.request({
                url: url.href,
                method: data ? 'POST' : 'GET',
                data,
                onSuccess: resolve,
                onError: reject
            });
        }).then(response => publishResponse(response, control, productId));
        const api = window.wtJshopModuleCart;
        if (api && typeof api.enqueueMutation === 'function') {
            api.enqueueMutation(task).catch(showRequestError).then(finish, finish);
        } else {
            queue = queue.then(task).catch(showRequestError).then(finish, finish);
        }
    };

    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        if (typeof event.target.closest !== 'function') return;
        const control = event.target.closest(listButtonSelector);
        if (!control) return;

        if (control.tagName === 'A') {
            if ((control.target && control.target.toLowerCase() !== '_self') || control.hasAttribute('download')) return;
            const url = sameOriginUrl(control.href);
            if (!url || !isCartAddLink(url)) return;
            const quantity = listQuantityFor(control);
            if (quantity) url.searchParams.set('quantity', quantity);
            event.preventDefault();
            addToCart(control, url);
            return;
        }

        if (control.type !== 'button') return;
        const productId = control.dataset.productId;
        const categoryId = control.dataset.productCategoryId;
        const url = sameOriginUrl(settings.url);
        if (!url || !isProductId(productId) || !/^[0-9]+$/.test(categoryId)) return;

        const data = new FormData();
        data.set('product_id', productId);
        data.set('category_id', categoryId);
        data.set('quantity', listQuantityFor(control) || '1');
        data.set('to', 'cart');
        event.preventDefault();
        addToCart(control, url, data);
    });

    document.addEventListener('submit', event => {
        const control = event.submitter;
        if (event.defaultPrevented || !control || !control.matches(productButtonSelector)) return;

        const form = event.target;
        const url = sameOriginUrl(control.getAttribute('formaction') || form.action);
        if (!url) return;
        const data = new FormData(form);
        if (control.name) data.append(control.name, control.value);
        if (!isProductId(data.get('product_id') || url.searchParams.get('product_id'))) return;

        const destination = data.get('to') || url.searchParams.get('to');
        if (destination && destination !== 'cart' && !control.matches(buttonSelector)) return;

        const selectedQuantity = linkedQuantity(control, productQuantitySelector) || quantityIn(form, productQuantitySelector);
        const standardQuantity = data.get('quantity');
        data.set('quantity', selectedQuantity || (typeof standardQuantity === 'string' && standardQuantity.trim()) || '1');
        data.set('to', 'cart');
        event.preventDefault();
        addToCart(control, url, data);
    });
})(Joomla);
