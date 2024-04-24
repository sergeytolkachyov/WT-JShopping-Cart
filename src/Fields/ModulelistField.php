<?php
/**
 * @package    WT JShopping Cart
 * @version    1.0.5
 * @author Sergey Tolkachyov <https://web-tolk.ru>
 * @сopyright (c) 2022 - April 2024 Sergey Tolkachyov. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link https://web-tolk.ru
 */
namespace Joomla\Module\Wtjshoppingcart\Site\Fields;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Language\Text;

class ModulelistField extends ListField
{

    protected $type = 'Modulelist';

    protected function getOptions():array
    {

        $options = [
            HTMLHelper::_('select.option', 0, Text::_('MOD_WTJSHOPPINGCART_FIELD_BOOTSTRAP5_OFFCANVAS_TARGET_ID_NOT_SELECTED'))
        ];
        $comModules = Factory::getApplication()->bootComponent('com_modules')->getMVCFactory();
        /** @var \Joomla\Component\Cache\Administrator\Model\CacheModel $model */
        $modulesModel = $comModules->createModel('Modules', 'Administrator', ['ignore_request' => true]);
        $modulesModel->setState('client_id', 0);
        $modulesModel->setState('filter.module', 'mod_wtjshoppingcart');
        $modules = $modulesModel->getItems();

        if(!count($modules))
        {
            return [
                HTMLHelper::_('select.option', 0, Text::_('MOD_WTJSHOPPINGCART_FIELD_BOOTSTRAP5_OFFCANVAS_TARGET_ID_NO_MODULES'))
            ];
        }

        foreach ($modules as $module)
        {
            $position = !empty($module->position) ? $module->position : 'not set';
            $published = !empty($module->published) ? Text::_('JPUBLISHED') : Text::_('JUNPUBLISHED');
            $option_title = $module->title.' ('.Text::_('MOD_WTJSHOPPINGCART_FIELD_BOOTSTRAP5_OFFCANVAS_TARGET_ID_POSITION').' '. $position .', '.$published.' )';
            $options[] = HTMLHelper::_('select.option', $module->id, $option_title);
        }

        return $options;
    }
}