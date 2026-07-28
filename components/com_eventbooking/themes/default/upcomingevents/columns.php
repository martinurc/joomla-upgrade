<?php
/**
 * @package        	Joomla
 * @subpackage		Event Booking
 * @author  		Tuan Pham Ngoc
 * @copyright    	Copyright (C) 2010 - 2024 Ossolution Team
 * @license        	GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

if ($this->isDirectMenuLink && $this->introText)
{
	$description = $this->introText;
}
else
{
	$description = $this->category ? $this->category->description: $this->introText;
}
?>
<div id="eb-upcoming-events-page-columns" class="eb-container<?php if ($this->config->activate_transparent) echo ' eb-container-transparent'; ?>">
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
        <<?php echo $hTag; ?> class="eb-page-heading">
			<?php
			echo $this->escape($this->params->get('page_heading'));

			if ($this->config->get('enable_ics_export'))
			{
				echo EventbookingHelperHtml::loadCommonLayout('common/ics_export.php');
			}
			?>
        </<?php echo $hTag; ?>>
	<?php
	}

	if ($description)
	{
	?>
		<div class="eb-description"><?php echo $description;?></div>
	<?php
	}

	if ($this->config->get('show_search_bar', 0))
	{
		echo $this->loadCommonLayout('common/search_filters.php');
	}

	if (count($this->items))
	{
		if (EventbookingHelperHtml::isLayoutOverridden('common/events_columns.php'))
		{
			$layoutData = [
				'events'          => $this->items,
				'config'          => $this->config,
				'Itemid'          => $this->Itemid,
				'nullDate'        => $this->nullDate,
				'ssl'             => 0,
				'viewLevels'      => $this->viewLevels,
				'category'        => $this->category,
				'bootstrapHelper' => $this->bootstrapHelper,
				'params'          => $this->params,
			];

			echo EventbookingHelperHtml::loadCommonLayout('common/events_columns.php', $layoutData);
		}
		else
		{
			echo $this->loadCommonLayout('common/events_columns_layout.php');
		}
	}
	else
	{
	?>
		<p class="text-info"><?php echo Text::_('EB_NO_UPCOMING_EVENTS') ?></p>
	<?php
	}

	if ($this->pagination->total > $this->pagination->limit)
	{
	?>
		<div class="pagination">
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
	<?php
	}
	?>

	<form method="post" name="adminForm" id="adminForm" action="<?php echo Route::_('index.php?option=com_eventbooking&view=upcomingevents&layout=columns&Itemid=' . $this->Itemid); ?>">
		<input type="hidden" name="id" value="0" />
		<input type="hidden" name="task" value="" />
	</form>
</div>