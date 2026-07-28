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
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

if (version_compare(JVERSION, '4.4.99', '>'))
{
	JLoader::registerAlias('JFormFieldList', '\\Joomla\\CMS\\Form\\Field\\ListField');
}
else
{
	FormHelper::loadFieldClass('list');
}

class JFormFieldEBExportField extends JFormFieldList
{
	/**
	 * The form field type.
	 *
	 * @var string
	 */
	protected $type = 'ebexportfield';

	/**
	 * Get list of options for this field
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		if (EventbookingHelper::isJoomla4())
		{
			$this->layout = 'joomla.form.field.list-fancy-select';
		}

		$config = EventbookingHelper::getConfig();
		$db     = Factory::getDbo();
		$query  = $db->getQuery(true)
			->select('name, title')
			->from('#__eb_fields')
			->where('published = 1')
			->where('hide_on_export = 0')
			->where('fieldtype NOT IN (' . implode(',', $db->quote(['Heading', 'Message'])) . ')')
			->order('title');
		$db->setQuery($query);
		$rowFields = $db->loadObjectList();

		$options   = [];
		$options[] = HTMLHelper::_('select.option', '', Text::_('EB_SELECT_FIELD'));

		if ($config->get('export_id', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'id', Text::_('EB_ID'));
		}

		if ($config->get('export_event_id', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'event_id', Text::_('EB_EVENT_ID'));
		}

		$options[] = HTMLHelper::_('select.option', 'title', Text::_('EB_EVENT'));

		if ($config->get('export_event_date', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'event_date', Text::_('EB_EVENT_DATE'));
		}

		if ($config->get('export_event_end_date'))
		{
			$options[] = HTMLHelper::_('select.option', 'event_end_date', Text::_('EB_EVENT_END_DATE'));
		}

		if ($config->get('export_category'))
		{
			$options[] = HTMLHelper::_('select.option', 'category_name', Text::_('EB_CATEGORY'));
		}

		if ($config->get('export_user_id', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'user_id', Text::_('EB_USER_ID'));
		}

		if ($config->get('export_username', 0))
		{
			$options[] = HTMLHelper::_('select.option', 'username', Text::_('EB_USERNAME'));
		}

		$options[] = HTMLHelper::_('select.option', 'registration_group_name', Text::_('EB_GROUP'));

		foreach ($rowFields as $rowField)
		{
			$options[] = HTMLHelper::_('select.option', $rowField->name, $rowField->title);
		}

		// Special field for ticket types output
		if (PluginHelper::isEnabled('eventbooking', 'tickettypes'))
		{
			$options[] = HTMLHelper::_('select.option', 'eb_ticket_types_plugin', Text::_('EB_TICKET_TYPES'));
		}

		if ($config->get('export_number_registrants', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'number_registrants', Text::_('EB_NUMBER_REGISTRANTS'));
		}

		if ($config->get('export_amount', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'total_amount', Text::_('EB_AMOUNT'));
		}

		if ($config->get('export_discount_amount', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'discount_amount', Text::_('EB_DISCOUNT_AMOUNT'));
		}

		if ($config->get('export_late_fee', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'late_fee', Text::_('EB_LATE_FEE'));
		}

		if ($config->get('export_tax_rate', 0))
		{
			$options[] = HTMLHelper::_('select.option', 'tax_rate', Text::_('EB_TAX_RATE'));
		}

		if ($config->get('export_tax_amount', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'tax_amount', Text::_('EB_TAX_AMOUNT'));
		}

		if ($config->get('export_payment_processing_fee', 0))
		{
			$options[] = HTMLHelper::_('select.option', 'payment_processing_fee', Text::_('EB_PAYMENT_FEE'));
		}

		if ($config->get('export_gross_amount', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'amount', Text::_('EB_GROSS_AMOUNT'));
		}

		if ($config->activate_deposit_feature)
		{
			if ($config->get('export_deposit_amount', 1))
			{
				$options[] = HTMLHelper::_('select.option', 'deposit_amount', Text::_('EB_DEPOSIT_AMOUNT'));
			}

			if ($config->get('export_due_amount', 1))
			{
				$options[] = HTMLHelper::_('select.option', 'due_amount', Text::_('EB_DUE_AMOUNT'));
			}
		}

		if ($config->enable_coupon)
		{
			$options[] = HTMLHelper::_('select.option', 'coupon_code', Text::_('EB_COUPON'));
		}

		if ($config->get('export_registration_date', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'register_date', Text::_('EB_REGISTRATION_DATE'));
		}

		if ($config->get('export_payment_method', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'payment_method', Text::_('EB_PAYMENT_METHOD'));
		}

		if ($config->activate_tickets_pdf)
		{
			$options[] = HTMLHelper::_('select.option', 'ticket_number', Text::_('EB_TICKET_NUMBER'));
			$options[] = HTMLHelper::_('select.option', 'ticket_code', Text::_('EB_TICKET_CODE'));
		}

		if ($config->get('export_ticket_qrcode', 0))
		{
			$options[] = HTMLHelper::_('select.option', 'ticket_qrcode', Text::_('EB_TICKET_QRCODE'));
		}

		if ($config->get('export_transaction_id', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'transaction_id', Text::_('EB_TRANSACTION_ID'));
		}

		if ($config->get('export_payment_date'))
		{
			$options[] = HTMLHelper::_('select.option', 'payment_date', Text::_('EB_PAYMENT_DATE'));
		}

		if ($config->activate_deposit_feature
			&& $config->get('export_deposit_payment_transaction_id', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'deposit_payment_transaction_id', Text::_('EB_DEPOSIT_PAYMENT_TRANSACTION_ID'));
		}

		if ($config->get('export_payment_status', 1))
		{
			$options[] = HTMLHelper::_('select.option', 'payment_status', Text::_('EB_PAYMENT_STATUS'));
		}

		if ($config->activate_checkin_registrants)
		{
			if ($config->get('export_checked_in', 1))
			{
				$options[] = HTMLHelper::_('select.option', 'checked_in', Text::_('EB_CHECKED_IN'));
			}

			if ($config->get('export_checked_in_at', 1))
			{
				$options[] = HTMLHelper::_('select.option', 'checked_in_at', Text::_('EB_CHECKED_IN_TIME'));
			}

			if ($config->get('export_checked_out_at', 1))
			{
				$options[] = HTMLHelper::_('select.option', 'checked_out_at', Text::_('EB_CHECKED_OUT_TIME'));
			}
		}

		if ($config->activate_invoice_feature)
		{
			$options[] = HTMLHelper::_('select.option', 'invoice_number', Text::_('EB_INVOICE_NUMBER'));
			$options[] = HTMLHelper::_('select.option', 'invoice_date', Text::_('EB_INVOICE_DATE'));
		}

		if ($config->export_subscribe_to_newsletter)
		{
			$options[] = HTMLHelper::_('select.option', 'subscribe_newsletter', Text::_('EB_SUBSCRIBE_TO_NEWSLETTER'));
		}

		if ($config->export_language)
		{
			$options[] = HTMLHelper::_('select.option', 'language', Text::_('EB_LANGUAGE'));
		}

		return $options;
	}
}
