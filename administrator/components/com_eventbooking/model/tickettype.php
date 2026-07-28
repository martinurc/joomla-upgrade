<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

class EventbookingModelTickettype extends RADModelAdmin
{
	/**
	 * Constructor
	 *
	 * @param   array  $config
	 */
	public function __construct($config = [])
	{
		$config['table'] = '#__eb_ticket_types';

		parent::__construct($config);
	}

	/**
	 * Validate to make sure data entered for event is valid before saving
	 *
	 * @param   RADInput  $input
	 *
	 * @return array
	 */
	public function validateFormInput($input)
	{
		$config     = EventbookingHelper::getConfig();
		$dateFormat = str_replace('%', '', $config->get('date_field_format', '%Y-%m-%d')) . ' H:i';

		$dateFields = [
			'publish_up',
			'publish_down',
		];

		foreach ($dateFields as $field)
		{
			$dateValue = $input->getString($field);

			if ($dateValue)
			{
				try
				{
					$date = DateTime::createFromFormat($dateFormat, $dateValue);

					if ($date !== false)
					{
						$input->set($field, $date->format('Y-m-d H:i:s'));
					}
				}
				catch (Exception $e)
				{
					// Do nothing
				}
			}
		}

		return parent::validateFormInput($input);
	}

	/**
	 * Prepare data before storing into table object
	 *
	 * @param   EventbookingTableTickettype  $row
	 * @param   RADInput                     $input
	 * @param   bool                         $isNew
	 *
	 * @return void
	 */
	protected function beforeStore($row, $input, $isNew)
	{
		parent::beforeStore($row, $input, $isNew);

		if (!$input->exists('weight'))
		{
			$input->set('weight', 1);
		}
	}
}
