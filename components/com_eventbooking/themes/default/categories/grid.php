<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

Factory::getApplication()->getDocument()->addStyleSheet(
	Uri::root(true) . '/media/com_eventbooking/assets/css/categorygrid.min.css',
	['version' => EventbookingHelper::getInstalledVersion()]
);

$description = $this->category ? $this->category->description: $this->introText;

/* @var EventbookingHelperBootstrap $bootstrapHelper */
$bootstrapHelper = EventbookingHelperBootstrap::getInstance();
$rowFluid        = $bootstrapHelper->getClassMapping('row-fluid');
$clearfix        = $bootstrapHelper->getClassMapping('clearfix');
?>
<div id="eb-categories-grid-page" class="eb-container">
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
        <<?php echo $hTag; ?> class="eb-page-heading"><?php echo $this->escape($this->params->get('page_heading'));?></<?php echo $hTag; ?>>
	<?php
	}

	if ($description)
	{
	?>
        <div class="eb-leading-description <?php echo $clearfix; ?>"><?php echo $description;?></div>
	<?php
	}

	if (count($this->items))
	{
		echo $this->loadTemplate('items');
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
</div>