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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

class EventbookingViewRegisterBase extends RADViewHtml
{
	/**
	 * Bootstrap helper
	 *
	 * @var EventbookingHelperBootstrap
	 */
	protected $bootstrapHelper;

	/**
	 * Array contains Html Select List which will be displayed on registration form
	 *
	 * @var array
	 */
	protected $lists = [];

	/**
	 * Messages
	 *
	 * @var RADConfig
	 */
	protected $message;

	/**
	 * Field suffix, use on multilingual website
	 *
	 * @var string
	 */
	protected $fieldSuffix = null;

	/*
	 *  Flag to mark if form grid is enabled for registration form
	 */
	protected $enableFormGrid = false;

	/**
	 * Set common data for registration form
	 *
	 * @param   RADConfig  $config
	 * @param   array      $data
	 */
	protected function setCommonViewData($config, &$data, $paymentTypeChange = 'showDepositAmount(this);')
	{
		$user        = Factory::getUser();
		$input       = $this->input;
		$paymentType = $input->post->getInt('payment_type', $config->get('default_payment_type', 0));

		if ($user->id && !isset($data['first_name']))
		{
			//Load the name from Joomla default name
			[$firstName, $lastName] = EventbookingHelper::callOverridableHelperMethod(
				'Registration',
				'detectFirstAndLastNameFromFullName',
				[$user->name]
			);

			$data['first_name'] = $firstName;
			$data['last_name']  = $lastName;
		}

		if ($user->id && !isset($data['email']))
		{
			$data['email'] = $user->email;
		}

		if ($config->get('auto_populate_form_data') === '0' && !$this->input->getInt('captcha_invalid'))
		{
			$data = [];
		}

		if (empty($data['country']))
		{
			$data['country'] = $config->default_country;
		}

		$currentYear              = date('Y');
		$expMonth                 = $input->post->getInt('exp_month', date('n'));
		$expYear                  = $input->post->getInt('exp_year', $currentYear);
		$this->lists['exp_month'] = HTMLHelper::_('select.integerlist', 1, 12, 1, 'exp_month', [
			'list.select'   => $expMonth,
			'option.format' => '%02d',
			'list.attr'     => 'class="input-medium form-select"',
			'id'            => 'exp_month',
		]);

		$this->lists['exp_year'] = HTMLHelper::_('select.integerlist', $currentYear, $currentYear + 10, 1, 'exp_year', [
			'list.select' => $expYear,
			'list.attr'   => 'class="input-medium form-select"',
			'id'          => 'exp_year',
		]);
		$options                 = [];

		// This is just here to avoid someone override layout and get warning
		$this->lists['card_type'] = HTMLHelper::_('select.genericlist', $options, 'card_type', ' class="form-select" ', 'value', 'text');

		$options = [];

		if ($config->get('enable_full_payment', 1))
		{
			$options[] = HTMLHelper::_('select.option', 0, Text::_('EB_FULL_PAYMENT'));
		}
		else
		{
			$paymentType = 1;
		}

		$options[]                   = HTMLHelper::_('select.option', 1, Text::_('EB_DEPOSIT_PAYMENT'));
		$this->lists['payment_type'] = HTMLHelper::_(
			'select.genericlist',
			$options,
			'payment_type',
			' class="input-large form-select" onchange="' . $paymentTypeChange . '" ',
			'value',
			'text',
			$paymentType
		);

		$this->message     = EventbookingHelper::getMessages();
		$this->fieldSuffix = EventbookingHelper::getFieldSuffix();
	}

	/**
	 * Get ID of terms and conditions article for the given event
	 *
	 * @param   EventbookingTableEvent  $event
	 * @param   RADConfig               $config
	 *
	 * @return int
	 */
	protected function getTermsAndConditionsArticleId($event, $config)
	{
		if ($event->enable_terms_and_conditions != 2)
		{
			$enableTermsAndConditions = $event->enable_terms_and_conditions;
		}
		else
		{
			$enableTermsAndConditions = $config->accept_term;
		}

		if ($enableTermsAndConditions)
		{
			if ($event->article_id > 0)
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true)
					->select('*')
					->from('#__content')
					->where('id = ' . (int) $event->article_id)
					->where($db->quoteName('state') . ' = 1');
				$db->setQuery($query);

				if ($db->loadObject())
				{
					// Valid article
					return $event->article_id;
				}
			}

			return $config->article_id;
		}

		return 0;
	}

	/**
	 * Method to calculate
	 *
	 * @param   array     $rowFields
	 * @param   stdClass  $event
	 *
	 * @return void
	 */
	protected function setFieldsContainerSize($rowFields, $event = null)
	{
		$config = EventbookingHelper::getConfig();
		$params = new Registry($event->params ?? '{}');

		if ($event)
		{
			$numberFieldsPerRow = (int) $params->get('number_fields_per_row', $config->get('number_fields_per_row', 0));
		}
		else
		{
			$numberFieldsPerRow = (int) $config->get('number_fields_per_row', 0);
		}

		if ($numberFieldsPerRow > 0)
		{
			$containerSize = [
				2 => 'eb-one-half',
				3 => 'eb-one-third',
				4 => 'eb-one-quarter',
			];

			$fieldContainerSize = $containerSize[$numberFieldsPerRow];

			foreach ($rowFields as $rowField)
			{
				$rowField->container_size = $fieldContainerSize;
			}

			$this->enableFormGrid = true;
		}
		else
		{
			foreach ($rowFields as $rowField)
			{
				if ($rowField->container_size)
				{
					$this->enableFormGrid = true;
					break;
				}
			}
		}
	}
}
