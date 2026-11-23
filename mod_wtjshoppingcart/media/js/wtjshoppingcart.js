/**
 * @file Cart controls and updates for WT JShopping Cart.
 *
 * @version    1.2.0
 * @author     Sergey Tolkachyov
 * @copyright  Copyright (c) 2022 - 2026 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @since      1.1.0
 * @see        https://web-tolk.ru
 */

((wtJshopModuleCart) => {
    'use strict';

    let queue = Promise.resolve();
    let cartRevision = 0;
    const removeListeners = new WeakSet();
    const quantityListeners = new WeakSet();

    const state = () => {
        if (!Joomla.getOptions('wt_jshop_module_cart')) {
            Joomla.loadOptions({ wt_jshop_module_cart: {} });
        }
        return Joomla.getOptions('wt_jshop_module_cart');
    };

    const formatPrice = value => {
        const options = Joomla.getOptions('wt_jshop_module_cart_module_options', {});
        const format = options.price_format || {};
        const decimals = Number(format.decimal_count === undefined ? 2 : format.decimal_count);
        const number = Number(value);
        if (!Number.isFinite(number)) return '';
        const factor = Math.pow(10, decimals);
        const rounded = Math.sign(number) * Math.round((Math.abs(number) + Number.EPSILON) * factor) / factor;
        const parts = rounded.toFixed(Math.max(0, decimals)).split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, () => format.thousand_separator === undefined ? '' : format.thousand_separator);
        return (format.currency_format || '00 Symb')
            .split('00').join(parts.join(format.decimal_symbol === undefined ? '.' : format.decimal_symbol))
            .split('Symb').join(options.currency_code || '');
    };

    const validCart = cart => cart && cart.products && typeof cart.products === 'object'
        && cart.count_product != null && cart.price_product != null
        && Number.isFinite(Number(cart.count_product)) && Number.isFinite(Number(cart.price_product))
        && Object.keys(cart.products).every(key => {
            const product = cart.products[key];
            return product && Number.isFinite(Number(product.quantity)) && Number(product.quantity) > 0;
        });

    const escapeMessage = message => {
        const element = document.createElement('span');
        element.textContent = message;
        return element.innerHTML;
    };

    wtJshopModuleCart.showRequestError = error => {
        const fallback = Joomla.Text._('JERROR_LAYOUT_ERROR_HAS_OCCURRED_WHILE_PROCESSING_YOUR_REQUEST');
        const messages = Array.isArray(error) ? error : [Joomla.Text._('WT_JSHOP_ADD_TO_CART_ERROR', fallback)];
        Joomla.renderMessages({ error: messages.map(escapeMessage) });
    };

    wtJshopModuleCart.readCartResponse = response => {
        const cart = JSON.parse(response);
        if (Array.isArray(cart)) {
            const messages = cart.filter(entry => entry && typeof entry.message === 'string' && entry.message.trim())
                .map(entry => entry.message);
            if (messages.length) throw messages;
        }
        if (!validCart(cart)) throw new Error('Invalid cart response');
        return cart;
    };

    // Adds, quantity changes and deletions share one queue and the last confirmed cart.
    wtJshopModuleCart.enqueueMutation = task => {
        const result = queue.then(task);
        queue = result.catch(() => {});
        return result;
    };

    const request = options => new Promise((resolve, reject) => {
        Joomla.request(Object.assign({}, options, { onSuccess: resolve, onError: reject }));
    });

    const cartUrl = (task, ajax, number) => {
        const routes = Joomla.getOptions('wt_jshop_module_cart_module_options', {}).cart_urls || {};
        const path = routes[task] || 'index.php?option=com_jshopping&controller=cart&task=' + task;
        const url = new URL(path, Joomla.getOptions('system.paths').rootFull);
        url.searchParams.set('ajax', ajax);
        if (number !== undefined) url.searchParams.set('number_id', number);
        return url.href;
    };

    // Cart mutations may omit href. A cart index can also be reused for a different product.
    const withProductLinks = (products, source, replaceExisting = false) => {
        const result = Object.assign(Array.isArray(products) ? [] : {}, products);
        Object.entries(products).forEach(([key, product]) => {
            const routed = source && source[key];
            if (!product.product_id || !routed || !routed.href || (product.href && !replaceExisting)
                || String(routed.product_id) !== String(product.product_id)
                || String(routed.category_id) !== String(product.category_id)) return;
            result[key] = Object.assign({}, product, { href: routed.href });
        });
        return result;
    };

    // cart.view with ajax=2 returns routed product links and the discounted total.
    const refreshCartDetails = (cart, revision, refreshMain) => {
        request({
            url: cartUrl('view', 2),
            method: 'GET',
            headers: { 'Cache-Control': 'no-cache' }
        }).then(response => {
            if (revision !== cartRevision) return;
            const { products, summ } = wtJshopModuleCart.readCartResponse(response);
            cart.products = withProductLinks(cart.products, products, true);
            wtJshopModuleCart.wtJshopModuleCartUpdateCartProducts(cart);

            const amount = typeof summ === 'string' && summ.trim() !== '' ? Number(summ) : summ;
            if (!Number.isFinite(amount)) throw new Error('Invalid cart total');
            cart.summ = amount;
            const price = formatPrice(amount);
            document.querySelectorAll('[data-wt-jshop-module-cart-price-total]').forEach(total => {
                total.innerHTML = price;
            });
        }).catch(error => {
            if (revision === cartRevision) wtJshopModuleCart.showRequestError(error);
        }).then(() => {
            if (revision !== cartRevision) return;
            notify(cart);
            if (refreshMain) wtJshopModuleCart.updateMainJShoppingCartHTML();
        });
    };

    const mutate = task => wtJshopModuleCart.enqueueMutation(task).catch(wtJshopModuleCart.showRequestError);

    const notify = cart => document.dispatchEvent(new CustomEvent('wt-jshop-module-cart:updated', { detail: { cart } }));

    const safeUrl = (value, base, local = true) => {
        try {
            const url = new URL(value, base);
            return (!local || url.origin === window.location.origin) && /^https?:$/.test(url.protocol) ? url.href : '';
        } catch (error) {
            if (error instanceof TypeError) return '';
            throw error;
        }
    };

    const updateRow = (row, key, product) => {
        const options = Joomla.getOptions('wt_jshop_module_cart_module_options', {});
        const paths = Joomla.getOptions('system.paths');
        row.setAttribute('data-wt-jshop-module-cart-product-number', key);
        row.querySelectorAll('[data-product-task]').forEach(button => button.setAttribute('data-product-number', key));
        const text = (selector, value) => {
            const element = row.querySelector(selector);
            if (element) element.textContent = value;
        };
        const price = (selector, value) => {
            const element = row.querySelector(selector);
            if (element) element.innerHTML = formatPrice(value);
        };
        text('[data-wt-jshop-module-cart-product-name]', product.product_name || '');
        text('[data-wt-jshop-module-cart-product-quantity]', product.quantity);
        price('[data-wt-jshop-module-cart-product-price]', product.price);
        price('[data-wt-jshop-module-cart-product-price-total]', Number(product.price) * Number(product.quantity));
        const attributes = [];
        [product.attributes_value, product.free_attributes_value].forEach(values => {
            Object.keys(values || {}).forEach(id => {
                const attribute = values[id];
                if (attribute) attributes.push(attribute.attr + ': ' + attribute.value);
            });
        });
        text('[data-wt-jshop-module-cart-product-attributes]', attributes.join(', '));
        const link = row.querySelector('[data-wt-jshop-module-cart-product-link]');
        if (link) {
            const href = typeof product.href === 'string' && product.href.trim()
                && safeUrl(product.href.replace(/&amp;/g, '&'), paths.rootFull);
            if (href) link.setAttribute('href', href);
            else link.removeAttribute('href');
        }
        const image = row.querySelector('[data-wt-jshop-module-cart-product-image]');
        if (image) {
            const imageBase = new URL(options.image_path || 'components/com_jshopping/files/img_products/', paths.rootFull).href;
            const src = product.thumb_image ? safeUrl(product.thumb_image, imageBase, false) : '';
            image.hidden = !src;
            image.alt = product.product_name || '';
            if (src) image.setAttribute('src', src);
            else image.removeAttribute('src');
        }
    };

    wtJshopModuleCart.init = () => {
        document.querySelectorAll('[data-wt-jshop-module-cart]').forEach(module => {
            const moduleId = Number(module.getAttribute('data-wt-jshop-module-cart'));
            wtJshopModuleCart.initRemoveButtons(moduleId, module);
            wtJshopModuleCart.initChangeQuantityButtons(moduleId, module);
        });
        const cart = state().cart;
        if (validCart(cart)) {
            renderCart(cart);
            notify(cart);
        }
    };

    wtJshopModuleCart.initRemoveButtons = (moduleId, module) => {
        if (removeListeners.has(module)) return;
        removeListeners.add(module);
        module.addEventListener('click', event => {
            if (typeof event.target.closest !== 'function') return;
            const button = event.target.closest('button[data-product-task="delete"]');
            if (!button || !module.contains(button)) return;
            event.preventDefault();
            wtJshopModuleCart.wtJshopModuleCartDeleteProduct(Number(button.getAttribute('data-product-number')), moduleId);
        });
    };

    wtJshopModuleCart.initChangeQuantityButtons = (moduleId, module) => {
        if (quantityListeners.has(module)) return;
        quantityListeners.add(module);
        module.addEventListener('click', event => {
            if (typeof event.target.closest !== 'function') return;
            const button = event.target.closest('button[data-product-task="quantity"]');
            if (!button || !module.contains(button)) return;
            event.preventDefault();
            wtJshopModuleCart.wtJshopModuleCartUpdateProductQty(
                Number(button.getAttribute('data-product-number')), moduleId, button.getAttribute('data-quantity')
            );
        });
    };

    wtJshopModuleCart.wtJshopModuleCartUpdateProductQty = (number_id, _moduleId, direction) => mutate(() => {
        const quantities = Object.assign({}, Joomla.getOptions('wt_jshop_module_cart_product_qunatities', {}));
        if (!Object.prototype.hasOwnProperty.call(quantities, number_id)) return;
        const quantity = Number(quantities[number_id]) + (direction === 'up' ? 1 : -1);
        if (!Number.isFinite(quantity) || quantity < 1) return;
        quantities[number_id] = quantity;
        const data = new FormData();
        Object.keys(quantities).forEach(key => data.append('quantity[' + key + ']', quantities[key]));
        return request({
            url: cartUrl('refresh', 1),
            method: 'POST', data
        }).then(response => wtJshopModuleCart.applyCart(wtJshopModuleCart.readCartResponse(response)));
    });

    wtJshopModuleCart.wtJshopModuleCartDeleteProduct = (number_id, _moduleId) => mutate(() => {
        const quantities = Joomla.getOptions('wt_jshop_module_cart_product_qunatities', {});
        if (!Object.prototype.hasOwnProperty.call(quantities, number_id)) return;
        return request({
            url: cartUrl('delete', 1, number_id),
            method: 'GET'
        }).then(response => wtJshopModuleCart.applyCart(wtJshopModuleCart.readCartResponse(response)));
    });

    wtJshopModuleCart.wtJshopModuleCartUpdateCartIcon = count_product => {
        document.querySelectorAll('.wt-jshopping-cart-module-icon .digit').forEach(digit => {
            digit.textContent = count_product;
        });
    };

    wtJshopModuleCart.wtJshopModuleCartUpdateCartProducts = cart => {
        const options = Joomla.getOptions('wt_jshop_module_cart_module_options', {});
        document.querySelectorAll('[data-wt-jshop-module-cart]').forEach(module => {
            const settings = (options.modules || {})[module.getAttribute('data-wt-jshop-module-cart')] || {};
            const list = module.querySelector('[data-wt-jshop-module-cart-products]') || module;
            const template = document.getElementById(settings.template_id || 'wt-jshop-cart-product-template-' + module.getAttribute('data-wt-jshop-module-cart'));
            const rows = {};
            module.querySelectorAll('[data-wt-jshop-module-cart-product-number]').forEach(row => {
                const key = row.getAttribute('data-wt-jshop-module-cart-product-number');
                if (Object.prototype.hasOwnProperty.call(cart.products, key)) rows[key] = row;
                else row.remove();
            });
            Object.keys(cart.products).forEach(key => {
                let row = rows[key];
                if (!row && template && template.content.firstElementChild) {
                    row = template.content.firstElementChild.cloneNode(true);
                    list.appendChild(row);
                }
                if (row) updateRow(row, key, cart.products[key]);
            });
            const empty = module.querySelector('[data-wt-jshop-module-cart-empty]');
            if (empty) empty.hidden = Object.keys(cart.products).length > 0;
            if (list !== module) list.hidden = Object.keys(cart.products).length === 0;
        });
    };

    const renderCart = cart => {
        const quantities = Joomla.getOptions('wt_jshop_module_cart_product_qunatities', {});
        Object.keys(quantities).forEach(key => delete quantities[key]);
        Object.keys(cart.products).forEach(key => { quantities[key] = cart.products[key].quantity; });
        Joomla.loadOptions({ wt_jshop_module_cart_product_qunatities: quantities });
        state().cart = cart;
        wtJshopModuleCart.wtJshopModuleCartUpdateCartProducts(cart);
        wtJshopModuleCart.wtJshopModuleCartUpdateCartIcon(Number(cart.count_product));
    };

    wtJshopModuleCart.applyCart = (cart, refreshMain = true) => {
        if (!validCart(cart)) return false;
        const previous = state().cart;
        const updated = Object.assign({}, cart, { products: withProductLinks(cart.products, previous && previous.products) });
        delete updated.summ;
        renderCart(updated);
        refreshCartDetails(updated, ++cartRevision, refreshMain);
        return true;
    };

    wtJshopModuleCart.updateMainJShoppingCartHTML = () => {
        if (!document.querySelector('form[name="updateCart"]')) return;
        const revision = cartRevision;
        request({
            url: cartUrl('view', 1),
            method: 'GET'
        }).then(html => {
            const mainCart = document.getElementById('comjshop');
            if (html && mainCart && revision === cartRevision) {
                mainCart.innerHTML = html;
                notify(state().cart);
            }
        }).catch(wtJshopModuleCart.showRequestError);
    };

    document.addEventListener('DOMContentLoaded', () => wtJshopModuleCart.init());

    window.addEventListener('message', event => {
        if (event.origin !== window.location.origin) return;
        if (event.source === window && event.data && event.data.wtJshopCartApplied) return;
        if (event.data && event.data.messageType === 'wt-jshop-module-cart:update-products-count' && event.data.cart) {
            wtJshopModuleCart.applyCart(event.data.cart);
        }
    });
})(window.wtJshopModuleCart = window.wtJshopModuleCart || {});
