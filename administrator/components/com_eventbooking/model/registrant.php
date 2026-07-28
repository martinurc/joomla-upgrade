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
use Joomla\CMS\User\UserHelper;

class EventbookingModelRegistrant extends EventbookingModelCommonRegistrant
{
	/**
	 * Instantiate the model.
	 *
	 * @param   array  $config  configuration data for the model
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->state->insert('filter_event_id', 'int', 0);
	}

	/**
	 * Initial registrant data
	 *
	 * @see RADModelAdmin::initData()
	 */
	public function initData()
	{
		parent::initData();

		$this->data->event_id = $this->state->filter_event_id;
	}

	/**
	 * @param $file
	 * @param $filename
	 *
	 * @return int
	 * @throws Exception
	 */
	public function import($file, $filename = '')
	{
		$app         = Factory::getApplication();
		$config      = EventbookingHelper::getConfig();
		$registrants = EventbookingHelperData::getDataFromFile($file, $filename);

		$imported  = 0;
		$todayDate = Factory::getDate()->toSql();

		if (count($registrants))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);
			$query->select('name, title')
				->from('#__eb_payment_plugins');
			$db->setQuery($query);
			$plugins = $db->loadObjectList('title');

			foreach ($registrants as $registrant)
			{
				if (empty($registrant['event_id']))
				{
					continue;
				}

				/* @var EventbookingTableRegistrant $row */
				$row = $this->getTable();

				if (!empty($registrant['id']))
				{
					$isNew = false;
					$row->load($registrant['id']);
				}
				else
				{
					$isNew = true;
				}

				if (isset($registrant['email']))
				{
					$registrant['email'] = trim($registrant['email']);
				}

				if ($registrant['register_date'])
				{
					try
					{
						$registerDate = DateTime::createFromFormat($config->date_format, $registrant['register_date']);

						if ($registerDate === false)
						{
							$registerDate                = Factory::getDate($registrant['register_date']);
							$registrant['register_date'] = $registerDate->format('Y-m-d');
						}
						else
						{
							$registrant['register_date'] = $registerDate->format('Y-m-d');
						}
					}
					catch (Exception $e)
					{
						$registrant['register_date'] = $todayDate;
					}
				}
				else
				{
					$registrant ['register_date'] = $todayDate;
				}

				if ($registrant['payment_method'] && isset($plugins[$registrant['payment_method']]))
				{
					$registrant['payment_method'] = $plugins[$registrant['payment_method']]->name;
				}

				$row->bind($registrant);

				if (!$row->transaction_id)
				{
					$row->transaction_id = strtoupper(UserHelper::genRandomPassword());
				}

				if (!$row->registration_code)
				{
					$row->registration_code = EventbookingHelperRegistration::getRegistrationCode();
				}

				if ($row->number_registrants > 1)
				{
					$row->is_group_billing = 1;
				}

				$row->store();

				$registrantId = $row->id;

				$fields = self::getEventFields($row->event_id, $config);

				if (count($fields))
				{
					$query->clear()
						->delete('#__eb_field_values')
						->where('registrant_id = ' . $registrantId);
					$db->setQuery($query);
					$db->execute();

					foreach ($fields as $fieldName => $field)
					{
						$fieldValue = $registrant[$fieldName] ?? '';
						$fieldId    = $field->id;

						if ($field->fieldtype == 'Checkboxes' || $field->multiple)
						{
							$fieldValue = json_encode(explode(', ', $fieldValue));
						}

						$query->clear()
							->insert('#__eb_field_values')
							->columns('registrant_id, field_id, field_value')
							->values("$registrantId, $fieldId, " . $db->quote($fieldValue));
						$db->setQuery($query);
						$db->execute();
					}
				}

				if ($isNew && $row->published == 1)
				{
					$app->triggerEvent('onAfterPaymentSuccess', [$row]);
				}

				$imported++;
			}
		}

		return $imported;
	}

	/**
	 * Get all custom fields of the given event
	 *
	 * @param   int  $eventId
	 *
	 * @pram RADConfig $config
	 *
	 * @return array
	 */
	public static function getEventFields($eventId, $config)
	{
		static $fields;

		if (!isset($fields[$eventId]))
		{
			$db    = Factory::getDbo();
			$query = EventbookingHelperRegistration::getBaseEventFieldsQuery($eventId);
			$query->clear('select')
				->clear('order')
				->select('id, name, fieldtype')
				->where('is_core = 0');

			$db->setQuery($query);
			$fields[$eventId] = $db->loadObjectList('name');
		}

		return $fields[$eventId];
	}
}
