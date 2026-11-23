<?php

/**
 * @package    WT JShopping Cart
 * @version    1.1.1
 * @author     Sergey Tolkachyov
 * @copyright  Copyright (c) 2022 - 2024 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://web-tolk.ru
 */

namespace Joomla\Module\Wtjshoppingcart\Site\Fields;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;

/**
 * List modules for the offcanvas target.
 *
 * @since  1.0.5
 */
class ModulelistField extends ListField
{
    /**
     * The field type.
     *
     * @var    string
     * @since  1.0.5
     */
    protected $type = 'Modulelist';
    /**
     * Return the available module options.
     *
     * @return  array  The selection options.
     *
     * @since   1.0.5
     */
    protected function getOptions(): array
    {

        $options = [
            HTMLHelper::_('select.option', 0, Text::_('MOD_WTJSHOPPINGCART_FIELD_BOOTSTRAP5_OFFCANVAS_TARGET_ID_NOT_SELECTED'))
        ];
        $comModules = Factory::getApplication()->bootComponent('com_modules')->getMVCFactory();
        /** @var \Joomla\Component\Modules\Administrator\Model\ModulesModel $modulesModel */
        $modulesModel = $comModules->createModel('Modules', 'Administrator', ['ignore_request' => true]);
        $modulesModel->setState('client_id', 0);
        $modulesModel->setState('filter.module', 'mod_wtjshoppingcart');
        $modules = $modulesModel->getItems();
        if (!count($modules)) {
            return [
                HTMLHelper::_('select.option', 0, Text::_('MOD_WTJSHOPPINGCART_FIELD_BOOTSTRAP5_OFFCANVAS_TARGET_ID_NO_MODULES'))
            ];
        }

        foreach ($modules as $module) {
            $position = !empty($module->position) ? $module->position : 'not set';
            $published = !empty($module->published) ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED');
            $option_title = $module->title . ' (' . Text::_('MOD_WTJSHOPPINGCART_FIELD_BOOTSTRAP5_OFFCANVAS_TARGET_ID_POSITION') . ' ' . $position . ', ' . $published . ' )';
            $options[] = HTMLHelper::_('select.option', $module->id, $option_title);
        }

        return $options;
    }
}
