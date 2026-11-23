<?php

/**
 * @package    WT JShopping Cart
 * @version    1.2.0
 * @author     Sergey Tolkachyov
 * @copyright  Copyright (c) 2022 - 2026 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://web-tolk.ru
 * @since      1.2.0
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/** @var object $module */
/** @var \Joomla\Registry\Registry $params */
/** @var object $cart */
$moduleId = (int) $module->id;
$targetId = max(0, (int) $params->get('bs_target_id', 0));
?>
<button type="button" id="wt_jshopping_cart_<?php echo $moduleId; ?>"
        data-wt-jshop-module-cart="<?php echo $moduleId; ?>"
        class="uk-button uk-button-default wt_jshopping_cart wt-jshopping-cart-module-icon <?php echo htmlspecialchars((string) $params->get('moduleclass_sfx', ''), ENT_QUOTES, 'UTF-8'); ?>"
        title="<?php echo htmlspecialchars(Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'), ENT_QUOTES, 'UTF-8'); ?>"
        aria-label="<?php echo htmlspecialchars(Text::_('MOD_WTJSHOPPINGCART_GO_TO_CART'), ENT_QUOTES, 'UTF-8'); ?>"
        aria-haspopup="dialog"
        <?php if ($targetId > 0) : ?>
            uk-toggle="target: #wt_jshopping_cart_<?php echo $targetId; ?>"
            aria-controls="wt_jshopping_cart_<?php echo $targetId; ?>"
        <?php else : ?>
            disabled aria-disabled="true"
        <?php endif; ?>>
    <span uk-icon="icon: cart" aria-hidden="true"></span>
    <span class="uk-badge uk-margin-small-left digit" data-wt-jshop-module-cart-count aria-live="polite" aria-atomic="true"><?php echo (float) $cart->count_product; ?></span>
</button>
<?php if ($params->get('show_debug', 0)) : ?>
    <details class="uk-margin-small-top">
        <summary><?php echo Text::_('MOD_WTJSHOPPINGCART_DEBUG'); ?></summary>
        <?php dump($cart); ?>
    </details>
<?php endif; ?>
