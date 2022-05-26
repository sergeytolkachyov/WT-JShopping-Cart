# WT JShopping Cart
Bootstrap 5 Cart module for JoomShopping 5 and Joomla 4

The shopping cart module for the JoomShopping 5 and Joomla 4 online store has 6 output layouts:
- default - standard output of the JoomShopping bucket. Not related to Bootstrap at all.
bootstrap5-icon - displays the cart module as a link-button with the basket icon and the quantity of goods in the form of a badge. It is convenient to place such a module in the header of the site or the mobile version of the site in the lower or upper panels.
- bootstrap5-icon-and-text is a link-button similar to bootstrap5-icon, but with the word "basket" and the amount of goods in the basket.
- bootstrap5-list-group - the bucket module is output as of the Bootstrap 5 List group component. It can be placed in the right or left columns of the site. In this layout, you can display the attributes of the goods, the weight of the goods.
- bootstrap5-offcanvas -the bucket module is output as Bootstrap 5 Offcanvas component - floating to the right or panel on the left. This is essentially a bootstrap5-list-group layout wrapped in an Offcanvas component.In this layout, you can display the attributes of the goods, the weight of the goods.
- bootstrap5-icon-btn - this layout is a copy of the bootstrap5-icon layout with the following changes: the module is output not by a link (tag ), but by a button (tag <button>). Learn more about goals and settings below.
# Configuring the module using Bootstrap 5 Offcanvas
You want to display the basket as a panel floating out from the edge of the screen. According to Bootstrap 5.2 documentation to open or hide this panel, you need a toggler button (switch). It can be either a link (tag <a>) or a button (tag <button>).You can create such a button yourself in an HTML code type module and place it in the right place, or create a menu item with the necessary attributes. 

However, if you would like to show the number of products near the basket icon and at the same time use it to open the basket module in Offcanvas - use the layoutbootstrap5-icon-btn.

You create 2 modules with different layouts: one with the layout bootstrap5-offcanvas (you need to remember its ID), the second with the layout bootstrap5-icon-btn. In the settings of the second module, you need to specify the parameter"ID of the module with the offcanvas layout", so that when you click on the button with the basket, the first offcanvas module appears.This parameter is used to set the data-bs-target attribute of the button.

A module with the bootstrap5-offcanvas layout is recommended to be published in the debug position or similar so that the content of the module has the body tag as the parent element. This is due to the peculiarities of the CSS property z-index.
