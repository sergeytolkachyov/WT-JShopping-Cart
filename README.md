[![Version](https://img.shields.io/github/release/sergeytolkachyov/WT-JShopping-Cart.svg)]() [![Status](https://img.shields.io/badge/Status-stable-green.svg)]() [![JoomlaVersion](https://img.shields.io/badge/Joomla-4.x-orange.svg)]() [![JoomShoppingVersion](https://img.shields.io/badge/JoomShopping-5.x-important.svg)]() [![DocumentationRus](https://img.shields.io/badge/Documentation-rus-blue.svg)](https://web-tolk.ru/dev/joomla-modules/wt-jshopping-cart-modul-bootstrap-5-korziny-dlya-joomshopping-5-i-joomla-4?utm_source=github) [![DocumentationEng](https://img.shields.io/badge/Documentation-eng-blueviolet.svg)](https://web-tolk.ru/en/dev/joomla-modules/wt-jshopping-cart-modul-bootstrap-5-korziny-dlya-joomshopping-5-i-joomla-4?utm_source=github)
# WT JShopping Cart
Bootstrap 5 and UIkit cart module for JoomShopping 5 and Joomla. Choose `bootstrap5-offcanvas` or `uikit-offcanvas` for an AJAX cart panel.
https://web-tolk.ru/en/dev/joomla-modules/wt-jshopping-cart-modul-bootstrap-5-korziny-dlya-joomshopping-5-i-joomla-4

The shopping cart module has 8 output layouts:
- `default` - standard output of the JoomShopping bucket. Not related to Bootstrap at all.
- `bootstrap5-icon` - displays the cart module as a link-button with the basket icon and the quantity of goods in the form of a badge. It is convenient to place such a module in the header of the site or the mobile version of the site in the lower or upper panels.
- `bootstrap5-icon-and-text` is a link-button similar to bootstrap5-icon, but with the word "basket" and the amount of goods in the basket.
- `bootstrap5-list-group` - the bucket module is output as of the Bootstrap 5 List group component. It can be placed in the right or left columns of the site. In this layout, you can display the attributes of the goods, the weight of the goods.
- `bootstrap5-offcanvas` -the bucket module is output as Bootstrap 5 Offcanvas component - floating to the right or panel on the left. This is essentially a `bootstrap5-list-group` layout wrapped in an Offcanvas component.In this layout, you can display the attributes of the goods, the weight of the goods.
- `bootstrap5-icon-btn` - this layout is a copy of the bootstrap5-icon layout with the following changes: the module is output not by a link (tag), but by a button (tag `<button>`). Learn more about goals and settings below.
- `uikit-offcanvas` - a native UIkit offcanvas cart panel for YOOtheme, with product controls and totals.
- `uikit-icon-btn` - a native UIkit cart icon button with a synchronized product counter.

For YOOtheme, create a module with `uikit-offcanvas` and another with `uikit-icon-btn`. In the button settings, select the panel's module ID. Use the existing offcanvas position setting to choose the left or right side, and publish the panel in `debug` or a similar position near `body`. The template must provide UIkit CSS/JS; YOOtheme does this automatically. The UIkit panel uses the `uikit` product row by default.

The module includes the `default` and `uikit` product rows. For a custom row, put its PHP file in `templates/YOUR_TEMPLATE/html/layouts/modules/mod_wtjshoppingcart/product/` and enter its filename without `.php` in the module settings. On upgrade, existing named rows in `layouts/modules/mod_wtjshoppingcart/product/` remain available. If you edited a shared `default.php` or `uikit.php` directly, copy it to the template override directory. Both offcanvas layouts render new AJAX rows from the `<template>` in the panel itself.
# Configuring the module using Bootstrap 5 Offcanvas
You want to display the basket as a panel floating out from the edge of the screen. According to Bootstrap 5.2 documentation to open or hide this panel, you need a toggler button (switch). It can be either a link (tag `<a>`) or a button (tag <button>).You can create such a button yourself in an HTML code type module and place it in the right place, or create a menu item with the necessary attributes. 

However, if you would like to show the number of products near the basket icon and at the same time use it to open the cart module in Offcanvas - use the layout `bootstrap5-icon-btn`.

You create 2 modules with different layouts: one with the layout bootstrap5-offcanvas (you need to remember its ID), the second with the layout bootstrap5-icon-btn. In the settings of the second module, you need to specify the parameter"ID of the module with the offcanvas layout", so that when you click on the button with the basket, the first offcanvas module appears.This parameter is used to set the data-bs-target attribute of the button.

A module with the bootstrap5-offcanvas layout is recommended to be published in the debug position or similar so that the content of the module has the body tag as the parent element. This is due to the peculiarities of the CSS property z-index.

# Installing the Joomla package

Install the ZIP whose name starts with `pkg_wtjshoppingcart` in Joomla. It includes the cart module and the JoomShopping 5 plugin `jshoppingproducts/wtjshoppingproductsajaxaddtocart`. Enable the plugin in Joomla's Plugins manager and publish the module on the shop pages. The plugin's Ajax tab contains the selector settings and the examples for JoomShopping list and product templates. One plugin configuration applies to every cart module instance; the plugin installs and loads its own add-to-cart script.

Selectors can be full CSS paths, such as `#catalogue .product > .actions > button.buy`, or selector lists. Quantity fields are read from the clicked product or submitted form. When upgrading, move any custom selectors from the old module settings to the plugin once. The standalone module displays and updates the cart; AJAX purchase buttons are enabled by the plugin.

# Building with Phing

The Joomla package manifest `pkg_wtjshoppingcart.xml`, installer `pkg_script.php`, and system translations in `language/` are at the repository root. The package manifest declares `mod_wtjshoppingcart/` and `plg_jshoppingproducts_wtjshoppingproductsajaxaddtocart` as child folders named after their Joomla elements.

Open the project's `phing.xml` with the Phing executable registered by Process Forge as `phing-packager-phing`. Run `3. Package release` for an installable package in `.packages/`. `5. Package dev` builds the developer package. `Package module only` builds the standalone module ZIP.

The package target uses the shared Phing Packager to prepare metadata for each extension and adds their files under the declared folders in the final ZIP. Set `PHING_PACKAGER_ROOT` or `-DpackagerRoot=<tool directory>` to the shared tool directory, or use the project's ignored `.agents/build/local.properties` binding.
