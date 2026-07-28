<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

class EventbookingControllerMmtemplate extends EventbookingController
{
	/**
	 * Method to get mass mail template message
	 *
	 * @return void
	 */
	public function getMMTempalteMessage()
	{
		/* @var RADModelAdmin $model */
		$model      = $this->getModel('Mmtemplate');
		$mmTemplate = $model->getData();

		echo $mmTemplate ? $mmTemplate->message : '';

		$this->app->close();
	}
}
