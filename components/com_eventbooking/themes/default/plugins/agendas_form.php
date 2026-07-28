<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

/**
 * Layout varaible
 *
 * @var \Joomla\CMS\Form\Form $form
 */

foreach ($form->getFieldset() as $field)
{
	echo $field->input;
}
