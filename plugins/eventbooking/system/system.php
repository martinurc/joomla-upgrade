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
use Joomla\CMS\Plugin\CMSPlugin;

class plgEventbookingSystem extends CMSPlugin
{
	/**
	 * Database object.
	 *
	 * @var    JDatabaseDriver
	 */
	protected $db;

	/**
	 * This method is run after registration record is stored into database
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	public function onAfterStoreRegistrant($row)
	{
		if (strpos($row->payment_method, 'os_offline') === false)
		{
			return;
		}

		$config = EventbookingHelper::getConfig();

		if ($config->activate_invoice_feature)
		{
			$this->processInvoiceNumber($row);
		}

		// Update coupon usage
		if ($this->getRegistrationCouponId($row))
		{
			$this->updateCouponUsage($row);
		}

		if ($config->unpublish_event_when_full || $config->hide_event_when_full)
		{
			$this->processUnpublishEvent($row->event_id);
		}
	}

	/**
	 * This method is run after when the status of the registration changed from Pending -> Active
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	public function onAfterPaymentSuccess($row)
	{
		$config = EventbookingHelper::getConfig();

		if ($config->activate_invoice_feature && !$row->invoice_number)
		{
			$this->processInvoiceNumber($row);
		}

		// Update coupon usage, increase by 1
		if (!$row->coupon_usage_calculated && $this->getRegistrationCouponId($row))
		{
			$this->updateCouponUsage($row);
		}

		if ($config->multiple_booking)
		{
			$this->updateCartRegistrationRecordsStatus($row, $config);
		}

		if (($config->unpublish_event_when_full || $config->hide_event_when_full)
			&& strpos($row->payment_method, 'os_offline') === false)
		{
			$this->processUnpublishEvent($row->event_id);
		}

		if ($config->get('activate_tickets_pdf') && !$row->ticket_code)
		{
			$this->generateTicketNumbersForRegistration($row);
		}

		// Delete waiting list entries of same event for the user
		$this->deleteWaitingListEntries($row);
	}

	/**
	 * @param   EventbookingTableRegistrant  $row
	 */
	public function onAfterEditRegistrant($row)
	{
		$config = EventbookingHelper::getConfig();

		if ($config->activate_invoice_feature
			&& !$row->invoice_number
			&& $row->published == 0
			&& strpos($row->payment_method, 'os_offline') !== false)
		{
			$this->processInvoiceNumber($row);
		}
	}

	/***
	 * Generate invoice number of the registration record
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	private function processInvoiceNumber($row)
	{
		if (!EventbookingHelper::callOverridableHelperMethod('Registration', 'needInvoice', [$row]))
		{
			return;
		}

		$invoiceNumber = EventbookingHelper::callOverridableHelperMethod('Registration', 'getInvoiceNumber', [$row]);

		$config = EventbookingHelper::getConfig();

		$row->invoice_number = $invoiceNumber;

		if (property_exists($row, 'formatted_invoice_number'))
		{
			$row->formatted_invoice_number = EventbookingHelper::callOverridableHelperMethod('Helper', 'formatInvoiceNumber', [$row->invoice_number, $config, $row]);
		}

		$row->store();

		$db    = $this->db;
		$query = $db->getQuery(true);
		$query->update('#__eb_registrants')
			->set('invoice_number=' . $db->quote($invoiceNumber));

		if (property_exists($row, 'formatted_invoice_number'))
		{
			$query->set('formatted_invoice_number = ' . $db->quote($row->formatted_invoice_number));
		}

		$query->where('id=' . $row->id . ' OR cart_id=' . $row->id . ' OR group_id=' . $row->id);

		$db->setQuery($query)
			->execute();
	}

	/**
	 * Update coupon usage, increase number usage by 1
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	private function updateCouponUsage($row)
	{
		if ($row->cart_id > 0)
		{
			return;
		}

		$row->coupon_usage_calculated = 1;

		if (property_exists($row, 'coupon_usage_restored'))
		{
			$row->coupon_usage_restored = 0;
		}

		$row->store();

		$couponUsageTimes = empty($row->coupon_usage_times) ? 1 : $row->coupon_usage_times;

		$couponId = $this->getRegistrationCouponId($row);

		$db    = $this->db;
		$query = $db->getQuery(true);
		$query->update('#__eb_coupons')
			->set('used = used + ' . $couponUsageTimes)
			->set('used_amount = used_amount + ' . (float) $row->coupon_discount_amount)
			->where('id = ' . (int) $couponId);
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Restore coupon usage when someone cancel his registration or the registration is deleted
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	private function restoreCouponUsage($row)
	{
		if ($row->cart_id > 0 || $row->group_id > 0 || $row->coupon_usage_restored > 0)
		{
			return;
		}

		$couponUsageTimes = empty($row->coupon_usage_times) ? 1 : $row->coupon_usage_times;
		$rowCoupon        = new EventbookingTableCoupon($this->db);

		if (!$rowCoupon->load($row->coupon_id))
		{
			// Invalid coupon
			return;
		}

		$rowCoupon->used        -= $couponUsageTimes;
		$rowCoupon->used_amount -= (float) $row->coupon_discount_amount;

		if ($rowCoupon->used < 0)
		{
			$rowCoupon->used = 0;
		}

		if ($rowCoupon->used_amount < 0)
		{
			$rowCoupon->used_amount = 0;
		}

		$rowCoupon->store();

		if (property_exists($row, 'coupon_usage_restored'))
		{
			$row->coupon_usage_restored = 1;
		}

		$row->store();
	}

	/**
	 * Unpublish event when it is full
	 *
	 * @param   int  $eventId
	 */
	private function processUnpublishEvent($eventId)
	{
		$config = EventbookingHelper::getConfig();

		$event = EventbookingHelperDatabase::getEvent($eventId, null, null, true);

		// Unpublish the event when it is full
		if ($event->event_capacity && $event->total_registrants >= $event->event_capacity)
		{
			$db    = $this->db;
			$query = $db->getQuery(true)
				->update('#__eb_events')
				->where('id = ' . (int) $eventId);

			if ($config->unpublish_event_when_full)
			{
				$query->set('published = 0');
			}

			if ($config->hide_event_when_full)
			{
				$query->set('hidden = 1');
			}

			$db->setQuery($query)
				->execute();
		}
	}

	/**
	 * Mark all registration records in cart paid when the payment completed
	 *
	 * @param   EventbookingTableRegistrant  $row
	 * @param   RADConfig                    $config
	 */
	private function updateCartRegistrationRecordsStatus($row, $config)
	{
		$db    = $this->db;
		$query = $db->getQuery(true);
		$query->update('#__eb_registrants')
			->set('published = 1')
			->set('payment_date = NOW()')
			->set('transaction_id = ' . $db->quote($row->transaction_id))
			->where('cart_id = ' . (int) $row->id);
		$db->setQuery($query);
		$db->execute();

		if ($config->collect_member_information_in_cart)
		{
			$groupBillingQuery = $db->getQuery(true);
			$groupBillingQuery->select('id')
				->from('#__eb_registrants')
				->where('id = ' . $row->id . ' OR cart_id = ' . $row->id);
			$db->setQuery($groupBillingQuery);
			$billingRecordIds = $db->loadColumn();

			$query->clear('where')
				->where('group_id IN (' . implode(',', $billingRecordIds) . ')');
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Generate Ticket Number, Ticket Code for registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	private function generateTicketNumbersForRegistration($row)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_eventbooking/table/event.php';

		$config = EventbookingHelper::getConfig();

		$db    = $this->db;
		$query = $db->getQuery(true);

		if ($config->get('multiple_booking'))
		{
			$query->select('*')
				->from('#__eb_registrants')
				->where('id = ' . $row->id . ' OR cart_id = ' . $row->id);
			$db->setQuery($query);
			$rows = $db->loadObjectList();
		}
		else
		{
			$rows = [$row];
		}

		/* @var EventbookingTableRegistrant $rowRegistrant */
		foreach ($rows as $rowRegistrant)
		{
			$rowEvent = new EventbookingTableEvent($this->db);
			$rowEvent->load($rowRegistrant->event_id);

			if (!$rowEvent->activate_tickets_pdf)
			{
				continue;
			}

			// Get the next ticket number
			$query->clear()
				->select('MAX(ticket_number)')
				->from('#__eb_registrants')
				->where('event_id = ' . $rowRegistrant->event_id);
			$db->setQuery($query);
			$ticketNumber = (int) $db->loadResult() + 1;

			$ticketNumber = max($ticketNumber, $rowEvent->ticket_start_number);

			if ($rowRegistrant->is_group_billing)
			{
				$ticketCode = EventbookingHelperRegistration::getTicketCode();

				$query->clear()
					->update('#__eb_registrants')
					->set('ticket_code = ' . $db->quote($ticketCode))
					->where('id = ' . $rowRegistrant->id);
				$db->setQuery($query);
				$db->execute();

				if ($rowRegistrant->id == $row->id)
				{
					$row->ticket_code = $ticketCode;
				}

				$query->clear()
					->select('id')
					->from('#__eb_registrants')
					->where('group_id = ' . $rowRegistrant->id)
					->order('id');
				$db->setQuery($query);

				$memberIds = $db->loadColumn();

				foreach ($memberIds as $memberId)
				{
					$ticketCode = EventbookingHelperRegistration::getTicketCode();

					$query->clear()
						->update('#__eb_registrants')
						->set('ticket_code = ' . $db->quote($ticketCode))
						->set('ticket_number = ' . $ticketNumber)
						->where('id = ' . $memberId);
					$db->setQuery($query);
					$db->execute();

					$ticketNumber++;
				}
			}
			else
			{
				$ticketCode = EventbookingHelperRegistration::getTicketCode();

				$query->clear()
					->update('#__eb_registrants')
					->set('ticket_code = ' . $db->quote($ticketCode))
					->set('ticket_number = ' . $ticketNumber)
					->where('id = ' . $rowRegistrant->id);
				$db->setQuery($query);
				$db->execute();

				if ($rowRegistrant->id == $row->id)
				{
					$row->ticket_code   = $ticketCode;
					$row->ticket_number = $ticketNumber;
				}
			}
		}

		$row->store();
	}

	/**
	 * Method is called when someone cancel their registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	public function onRegistrationCancel($row)
	{
		if ($row->coupon_id > 0)
		{
			$this->restoreCouponUsage($row);
		}

		$config = EventbookingHelper::getConfig();

		if ($config->unpublish_event_when_full || $config->hide_event_when_full)
		{
			$event = EventbookingHelperDatabase::getEvent($row->event_id, null, null, true);

			// Re-published, re-shown the event when someone cancels registration
			if ($event->event_capacity > $event->total_registrants)
			{
				$db      = $this->db;
				$query   = $db->getQuery(true)
					->update('#__eb_events')
					->where('id = ' . $row->event_id);
				$execute = false;

				if ($config->hide_event_when_full && $event->hidden > 0)
				{
					$query->set('hidden = 0');
					$execute = true;
				}

				if ($config->unpublish_event_when_full && $event->published == 0)
				{
					$query->set('published = 1');
					$execute = true;
				}

				if ($execute)
				{
					$db->setQuery($query)
						->execute();
				}
			}
		}
	}

	/**
	 *    Method is called when a registration record
	 *
	 * @param   EventbookingTableRegistrant  $row
	 */
	public function onBeforeDeleteRegistrant($row)
	{
		if ($row->coupon_id > 0)
		{
			$this->restoreCouponUsage($row);
		}
	}

	/**
	 * Delete waiting list entries of the user when he becomes registrants
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return void
	 */
	private function deleteWaitingListEntries($row): void
	{
		$db    = $this->db;
		$query = $db->getQuery(true)
			->delete('#__eb_registrants')
			->where('event_id = ' . $row->event_id)
			->where('published IN(3,4)')
			->where('id != ' . $row->id);

		if ($row->user_id > 0)
		{
			$query->where('(user_id = ' . $row->user_id . ' OR email = ' . $db->quote($row->email) . ')');
		}
		else
		{
			$query->where('email = ' . $db->quote($row->email));
		}

		$db->setQuery($query)
			->execute();
	}

	/**
	 * Get ID of the coupon used for registration
	 *
	 * @param   EventbookingTableRegistrant  $row
	 *
	 * @return int
	 */
	private function getRegistrationCouponId($row)
	{
		if ($row->coupon_id > 0)
		{
			return $row->coupon_id;
		}

		$config = EventbookingHelper::getConfig();

		if ($config->multiple_booking)
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('coupon_id')
				->from('#__eb_registrants')
				->where('cart_id = ' . $row->id)
				->where('coupon_id > 0');
			$db->setQuery($query);

			return (int) $db->loadResult();
		}

		return 0;
	}
}
