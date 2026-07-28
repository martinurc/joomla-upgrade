<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Table\Table;

defined('_JEXEC') or die;

class EventbookingModelExporttmpl extends RADModelAdmin
{
	/**
	 * Update country_id make it the same with id
	 *
	 * @param   JTable    $row
	 * @param   RADInput  $input
	 * @param   bool      $isNew
	 */
	/**
	 * Pre-process data before custom field is being saved to database
	 *
	 * @param   Table  $row
	 * @param   RADInput                 $input
	 * @param   bool                     $isNew
	 */
	protected function beforeStore($row, $input, $isNew)
	{
		$fields               = [];
		$exportTemplateFields = $input->get('export_tmpl_fields', [], 'array');

		foreach ($exportTemplateFields as $exportTemplateField)
		{
			if (!empty($exportTemplateField['field']))
			{
				$fields[] = $exportTemplateField['field'];
			}
		}

		$input->set('fields', json_encode($fields));
	}
}
