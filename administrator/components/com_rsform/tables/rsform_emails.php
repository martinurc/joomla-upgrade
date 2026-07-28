<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class TableRsform_Emails extends Table
{
	/**
	 * Primary Key
	 *
	 * @var int
	 */
	
	public $id;
	public $formId;
	public $from = '{global:mailfrom}';
	public $fromname = '{global:fromname}';
	public $replyto = '';
	public $replytoname = '';
	public $to = '';
	public $cc = '';
	public $bcc = '';
	public $subject = '';
	public $mode = 1;
	public $message = '';
		
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	public function __construct(& $db)
	{
		parent::__construct('#__rsform_emails', 'id', $db);
	}
}