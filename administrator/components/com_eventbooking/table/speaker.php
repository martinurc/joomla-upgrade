<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

/**
 * Class EventbookingTableSpeaker
 *
 * @property $id
 * @property $event_id
 * @property $name
 * @property $description
 * @property $facebook
 * @property $twitter
 * @property $linkedin
 * @property $url
 * @property $ordering
 */
class EventbookingTableSpeaker extends Table
{
	/**
	 * Constructor
	 *
	 * @param   JDatabaseDriver  $db  Database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__eb_speakers', 'id', $db);
	}
}
