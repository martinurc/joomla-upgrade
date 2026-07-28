<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\ListField;

FormHelper::loadFieldClass('list');

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\Field\\ListField', 'JFormFieldList');
}

class JFormFieldFormLayouts extends ListField
{
	protected $type = 'FormLayouts';
	
	protected function getOptions()
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_rsform/helpers/rsform.php';

		// Initialize variables.
		$options = array();

		if ($allLayouts = RSFormProHelper::getFormLayouts())
        {
            if ($allLayouts['html5Layouts'])
            {
                foreach ($allLayouts['html5Layouts'] as $layout)
                {
                    $options[] = HTMLHelper::_('select.option', $layout, Text::_('RSFP_LAYOUT_'.str_replace('-', '_', $layout)));
                }
            }
        }

        reset($options);

        return $options;
	}
}
