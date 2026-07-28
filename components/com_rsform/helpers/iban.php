<?php
/**
 * @package RSForm! Pro
 * @copyright (C) 2007-2019 www.rsjoomla.com
 * @license GPL, http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

class RSFormIBAN
{
	private $value;

	public function __construct($value)
	{
		require_once __DIR__ . '/php-iban/php-iban.php';
		$this->value = $value;
	}

	public function validate()
	{
		return verify_iban($this->value);
	}
}