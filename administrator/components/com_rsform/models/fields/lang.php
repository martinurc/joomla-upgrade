<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\ListField;

FormHelper::loadFieldClass('list');

if (version_compare(JVERSION, '4.0', '<'))
{
	JLoader::registerAlias('Joomla\\CMS\\Form\\Field\\ListField', 'JFormFieldList');
}

class JFormFieldLang extends ListField
{
	protected $type = 'Lang';

	protected function getOptions()
	{
		// Initialize variables.
		$options = array();

		$languages = LanguageHelper::getKnownLanguages(JPATH_SITE);

		if (empty($this->element['nodefault']))
		{
			Factory::getLanguage()->load('com_rsform');

			$options[] = HTMLHelper::_('select.option', '', Text::_('RSFP_SUBMISSIONS_ALL_LANGUAGES'));
		}

		foreach ($languages as $language => $properties)
		{
			$options[] = HTMLHelper::_('select.option', $language, $properties['name']);
		}

		reset($options);
		
		return $options;
	}
}