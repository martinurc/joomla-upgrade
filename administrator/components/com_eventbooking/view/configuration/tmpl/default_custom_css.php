<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

if (EventbookingHelper::isJoomla4())
{
	$tabApiPrefix = 'uitab.';
}
else
{
	$tabApiPrefix = 'bootstrap.';
}

echo HTMLHelper::_($tabApiPrefix . 'addTab', 'configuration', 'custom-css', Text::_('EB_CUSTOM_CSS'));

$customCss = '';

if (file_exists(JPATH_ROOT . '/media/com_eventbooking/assets/css/custom.css'))
{
	$customCss = file_get_contents(JPATH_ROOT . '/media/com_eventbooking/assets/css/custom.css');
}

if (empty($this->editor))
{
?>
	<textarea class="form-control" name="custom_css" rows="20"><?php echo $customCss; ?></textarea>
<?php
}
else
{
	echo $this->editor->display('custom_css', $customCss, '100%', '550', '75', '8', false, null, null, null, ['syntax' => 'css']);
}

echo HTMLHelper::_($tabApiPrefix . 'endTab');
