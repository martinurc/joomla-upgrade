<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

class EventbookingViewFieldRaw extends RADViewHtml
{
	/**
	 * List of options from parent field
	 *
	 * @var string[]
	 */
	protected $options;

	/**
	 * Set data and display the view
	 *
	 * @return void
	 * @throws Exception
	 */
	public function display()
	{
		$this->setLayout('options');
		$fieldId = Factory::getApplication()->input->getInt('field_id', 0);
		$db      = Factory::getDbo();
		$query   = $db->getQuery(true)
			->select('`values`')
			->from('#__eb_fields')
			->where('id=' . $fieldId);
		$db->setQuery($query);
		$options       = explode("\r\n", $db->loadResult());
		$this->options = $options;

		parent::display();
	}
}
