<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormRule;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class JFormRuleSubmissionvaluesduplicatecheck extends FormRule
{
	public function test($element, $value, $group = null, $input = null, $form = null)
	{
		if ($value && isset($value->name) && is_array($value->name))
		{
			if (count(array_unique($value->name)) !== count($value->name))
			{
				$values = array_count_values($value->name);
				foreach ($values as $fieldName => $count)
				{
					if ($count > 1)
					{
						Factory::getApplication()->enqueueMessage(Text::sprintf('COM_RSFORM_MAKE_SURE_FIELDS_ARE_UNIQUE_FIELD_IS_SPECIFIED_MULTIPLE_TIMES', $fieldName), 'warning');
					}
				}

				return false;
			}
		}

		return true;
	}
}