window.wtJshopModuleCart = window.wtJshopModuleCart || {};

(wtJshopModuleCart => {

    wtJshopModuleCart.init = () => {
        let moduleIds = document.querySelectorAll('[data-wt-jshop-module-cart]');
        moduleIds.forEach((module) => {

            let moduleId = parseInt(module.getAttribute('data-wt-jshop-module-cart'));

            wtJshopModuleCart.initRemoveButtons(moduleId, module);

            wtJshopModuleCart.initChangeQuantityButtons(moduleId, module);

        });

    }

    /**
     * Register action for remove buttons for module
     *
     * @param {number} moduleId A Wt JSHopping cart module id
     * @param {HTMLElement} module A Wt JSHopping cart module
     */
    wtJshopModuleCart.initRemoveButtons = (moduleId, module) => {
        const removeProductButtons = module.querySelectorAll('button[data-product-task="delete"]');
        if (removeProductButtons) {
            removeProductButtons.forEach((removeProductButton) => {
                removeProductButton.addEventListener('click', (event) => {
                    const button = event.target;
                    let number_id = parseInt(button.getAttribute('data-product-number'));
                    wtJshopModuleCart.wtJshopModuleCartDeleteProduct(number_id, moduleId);
                });
            });
        }
    }

    /**
     * Register actions for change product quantity buttons for module
     *
     * @param {number} moduleId A Wt JSHopping cart module id
     * @param {HTMLElement} module A Wt JSHopping cart module
     */
    wtJshopModuleCart.initChangeQuantityButtons = (moduleId, module) => {
        const qtyProductButtonsUp = module.querySelectorAll('button[data-product-task="quantity"][data-quantity="up"]');
        const qtyProductButtonsDown = module.querySelectorAll('button[data-product-task="quantity"][data-quantity="down"]');
        if (qtyProductButtonsUp && qtyProductButtonsDown) {
            qtyProductButtonsUp.forEach((qtyProductButtonUp) => {
                qtyProductButtonUp.addEventListener('click', (event) => {
                    const button = event.target;
                    let number_id = parseInt(button.getAttribute('data-product-number'));
                    let direction = button.getAttribute('data-quantity');
                    wtJshopModuleCart.wtJshopModuleCartUpdateProductQty(number_id, moduleId, direction);
                });
            });
            qtyProductButtonsDown.forEach((qtyProductButtonDown) => {
                qtyProductButtonDown.addEventListener('click', (event) => {
                    const button = event.target;
                    let number_id = parseInt(button.getAttribute('data-product-number'));
                    let direction = button.getAttribute('data-quantity');
                    wtJshopModuleCart.wtJshopModuleCartUpdateProductQty(number_id, moduleId, direction);
                });
            });
        }
    }

    /**
     * Update product quantity
     *
     * @param {number} number_id The product position in product list in JoomShopping cart
     * @param {number} moduleId The current WT JShopping cart module id
     * @param {string} direction Increase or decrease product quantity: "up" or "down"
     */
    wtJshopModuleCart.wtJshopModuleCartUpdateProductQty = (number_id, moduleId, direction) => {
        let product_qunatities = Joomla.getOptions('wt_jshop_module_cart_product_qunatities');
        let product_qty = product_qunatities[number_id];

        if (direction === 'up') {
            product_qty++;
        } else {
            product_qty--;
        }
        // You should delete the product from cart
        if (product_qty < 1) {
            console.warn('[WT JShopping cart module]: You should remove the product from cart');
            return;
        }
        product_qunatities[number_id] = product_qty;
        let options = {};
        options.wt_jshop_module_cart_product_qunatities = product_qunatities;
        Joomla.loadOptions(options);

        let data = new FormData();
        for (var key in product_qunatities) {
            // skip loop if the property is from prototype
            if (!product_qunatities.hasOwnProperty(key)) continue;
            var value = product_qunatities[key];
            data.append('quantity[' + key + ']', value);
        }

        Joomla.request({
            url: Joomla.getOptions('system.paths').rootFull + 'index.php?option=com_jshopping&controller=cart&task=refresh&ajax=1',
            method: 'POST',
            data: data,
            onSuccess: (response, xhr) => {
                if (response !== '') {
                    let cart = JSON.parse(response);

                    let count_product = parseInt(cart.count_product);
                    wtJshopModuleCart.wtJshopModuleCartUpdateCartProducts(cart);
                    wtJshopModuleCart.wtJshopModuleCartUpdateCartIcon(count_product);
                }
            }
        });

    }

    wtJshopModuleCart.wtJshopModuleCartDeleteProduct = (number_id, moduleId) => {
        Joomla.request({
            url: Joomla.getOptions('system.paths').rootFull + 'index.php?option=com_jshopping&controller=cart&task=delete&ajax=1&number_id=' + number_id,
            method: 'GET',
            onSuccess: (response, xhr) => {
                if (response !== '') {
                    let cart = JSON.parse(response);

                    let count_product = parseInt(cart.count_product);
                    let module = document.querySelector('[data-wt-jshop-module-cart="' + moduleId + '"]');
                    let product = module.querySelector('[data-wt-jshop-module-cart-product-number="' + number_id + '"]');
                    product.parentElement.removeChild(product);
                    let product_qunatities = Joomla.getOptions('wt_jshop_module_cart_product_qunatities');
                    delete product_qunatities[number_id];
                    let options = {};
                    options.wt_jshop_module_cart_product_qunatities = product_qunatities;
                    Joomla.loadOptions(options);

                    let productCartPriceTotal = document.querySelector('[data-wt-jshop-module-cart-price-total]');
                    if (productCartPriceTotal) {
                        let moduleOptions = Joomla.getOptions('wt_jshop_module_cart_module_options');
                        let currency_code = moduleOptions.currency_code;
                        productCartPriceTotal.innerHTML = cart.price_product + ' ' + currency_code;
                    }

                    wtJshopModuleCart.wtJshopModuleCartUpdateCartIcon(count_product);
                    wtJshopModuleCart.updateMainJShoppingCartHTML();
                }
            }
        });

    }

    /**
     * Update the products count in all the modules icons
     *
     * @param {number} count_product The product count for modules icons
     */
    wtJshopModuleCart.wtJshopModuleCartUpdateCartIcon = (count_product) => {

        let wtJshopModuleCartIcons = document.querySelectorAll('.wt-jshopping-cart-module-icon');
        if (wtJshopModuleCartIcons) {
            wtJshopModuleCartIcons.forEach((wtJshopModuleCartIcon) => {
                let digit = wtJshopModuleCartIcon.querySelector('.digit');
                digit.innerHTML = count_product;
            });
        }
    }

    /**
     * Update product quantities and prices in modules and cart page
     *
     * @param {object} cart JoomShopping cart data json
     */
    wtJshopModuleCart.wtJshopModuleCartUpdateCartProducts = (cart) => {
        let products = cart.products;
        let moduleOptions = Joomla.getOptions('wt_jshop_module_cart_module_options');
        let currency_code = moduleOptions.currency_code;
        for (var key in products) {
            // skip loop if the property is from prototype
            if (!products.hasOwnProperty(key)) continue;
            let product = products[key];

            let moduleCartProductListItem = document.querySelector('[data-wt-jshop-module-cart-product-number="' + key + '"]');
            let productQuantity = moduleCartProductListItem.querySelector('[data-wt-jshop-module-cart-product-quantity]');
            productQuantity.innerHTML = product.quantity;

            let productPrice = moduleCartProductListItem.querySelector('[data-wt-jshop-module-cart-product-price]');
            productPrice.innerHTML = product.price + ' ' + currency_code;

            let productPriceTotal = moduleCartProductListItem.querySelector('[data-wt-jshop-module-cart-product-price-total]');
            productPriceTotal.innerHTML = ((parseInt(product.price)) * (parseInt(product.quantity))) + ' ' + currency_code;

        }
        let productCartPriceTotal = document.querySelector('[data-wt-jshop-module-cart-price-total]');
        if (productCartPriceTotal) {
            productCartPriceTotal.innerHTML = cart.price_product + ' ' + currency_code;
        }

        wtJshopModuleCart.updateMainJShoppingCartHTML();
    }

    /**
     * If we are in JoomShopping cart page - we should update the main page data too.
     * So we detect a cart form[name="updateCart"].
     * Do the ajax request to get HTML from JoomShopping
     */
    wtJshopModuleCart.updateMainJShoppingCartHTML = () => {
        let isCart = document.querySelector('form[name="updateCart"]');
        if (isCart) {
            Joomla.request({
                url: Joomla.getOptions('system.paths').rootFull + 'index.php?option=com_jshopping&controller=cart&task=view&ajax=1',
                method: 'GET',
                onSuccess: (response, xhr) => {
                    if (response !== '') {
                        let cartHtml = response;
                        let mainCart = document.getElementById('comjshop');
                        mainCart.innerHTML = cartHtml;
                    }
                }
            });
        }
    }

})(wtJshopModuleCart)


document.addEventListener('DOMContentLoaded', () => {

    wtJshopModuleCart.init();

});

/**
 * Update icons and modules data.
 * We need in
 * - cart.count_product
 * - cart.products - object with objects - product.quantity and product.price
 * - cart.price_product - total cart price
 */
window.addEventListener('message', event => {
    // Avoid cross origins
    if (event.origin !== window.location.origin) return;
    // Check message type
    if (event.data.messageType === 'wt-jshop-module-cart:update-products-count') {
        // Set and submit values
        wtJshopModuleCart.wtJshopModuleCartUpdateCartProducts(event.data.cart);
        let count_product = parseInt(cart.count_product);
        wtJshopModuleCart.wtJshopModuleCartUpdateCartIcon(count_product);
    }
});

