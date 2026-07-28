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

$pageHeading = $this->params->get('page_heading') ?: Text::_('EB_CALENDAR');

HTMLHelper::_('bootstrap.tooltip');
?>
<div id="eb-calendar-page" class="eb-container">
	<?php
	if ($this->params->get('show_page_heading', 1))
	{
		if ($this->input->getInt('hmvc_call'))
		{
			$hTag = 'h2';
		}
		else
		{
			$hTag = 'h1';
		}
	?>
		<<?php echo $hTag; ?> class="eb-page-heading"><?php echo $this->escape($pageHeading); ?></<?php echo $hTag; ?>>
	<?php
	}

	if (EventbookingHelper::isValidMessage($this->params->get('intro_text')))
	{
	?>
		<div class="eb-description"><?php echo HTMLHelper::_('content.prepare', $this->params->get('intro_text')); ?></div>
	<?php
	}
	?>
	<div id='eb_full_calendar'></div>
</div>
