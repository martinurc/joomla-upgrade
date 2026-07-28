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
use Joomla\CMS\Uri\Uri;

if ($this->isDirectMenuLink && $this->introText)
{
	$description = $this->introText;
}
else
{
	$description = $this->category ? $this->category->description: $this->introText;
}

$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$clearfixClass   = $bootstrapHelper->getClassMapping('clearfix');
?>
<div id="eb-category-page-timeline" class="eb-container">
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
		<div class="eb-category-description <?php echo $clearfixClass; ?>">
			<?php
				if (!empty($this->category->image))
				{
					$this->category->image = EventbookingHelperHtml::getCleanImagePath($this->category->image);
				}

				if (!empty($this->category->image) && file_exists(JPATH_ROOT . '/images/com_eventbooking/categories/thumb/' . basename($this->category->image)))
				{
					$rootUri = Uri::root(true);
				?>
					<a href="<?php echo $rootUri . '/' . $this->category->image; ?>" class="eb-modal" target="_blank"><img src="<?php echo $rootUri . '/images/com_eventbooking/categories/thumb/' . basename($this->category->image); ?>" alt="<?php echo $this->category->image_alt ?: $this->category->name; ?>" class="eb-thumb-left" /></a>
				<?php
				}

				echo $description;
			?>
		</div>
	<?php
	}

	if (count($this->categories))
	{
		// This is for backward compatible purpose
		if (EventbookingHelperHtml::isLayoutOverridden('common/categories.php'))
		{
			$layoutData = [
				'categories' => $this->categories,
				'categoryId' => $this->categoryId,
				'config'     => $this->config,
				'Itemid'     => $this->Itemid,
				'params'     => $this->params,
			];

			echo EventbookingHelperHtml::loadCommonLayout('common/categories.php', $layoutData);
		}
		else
		{
			echo $this->loadCommonLayout('categories/default_items.php');
		}
	}

	if ($this->config->get('show_search_bar', 0) && $this->params->get('display_events_type') != 3)
	{
		echo $this->loadCommonLayout('common/search_filters.php');
	}

	if (count($this->items))
	{
		if (EventbookingHelperHtml::isLayoutOverridden('common/events_timeline.php'))
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

			echo EventbookingHelperHtml::loadCommonLayout('common/events_timeline.php', $layoutData);
		}
		else
		{
			echo $this->loadCommonLayout('common/events_timeline_layout.php');
		}
	}
	elseif(count($this->categories) == 0)
	{
	?>
		<p class="text-info"><?php echo Text::_('EB_NO_EVENTS') ?></p>
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

	<form method="post" name="adminForm" id="adminForm" action="<?php echo Route::_('index.php?option=com_eventbooking&view=category&layout=timeline&id=' . $this->categoryId . '&Itemid=' . $this->Itemid); ?>">
		<input type="hidden" name="id" value="0" />
		<input type="hidden" name="task" value="" />
	</form>
</div>