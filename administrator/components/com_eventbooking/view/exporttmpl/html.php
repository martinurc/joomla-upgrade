<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;

class EventbookingViewExporttmplHtml extends RADViewItem
{
	/**
	 * The export template fields selection form
	 *
	 * @var Form
	 */
	protected $form;

	protected function prepareView()
	{
		parent::prepareView();

		$form                           = Form::getInstance(
			'export_tmpl_fields',
			JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/exporttmpl/forms/export_tmpl_fields.xml'
		);
		$formData['export_tmpl_fields'] = [];

		if ($this->item->fields)
		{
			$fields = json_decode($this->item->fields, true);
		}
		else
		{
			$fields = [];
		}

		foreach ($fields as $field)
		{
			$formData['export_tmpl_fields'][] = [
				'field' => $field,
			];
		}

		$form->bind($formData);

		$this->form = $form;

		return true;
	}
}
