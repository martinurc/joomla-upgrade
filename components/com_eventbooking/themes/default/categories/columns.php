<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

$description = $this->category ? $this->category->description: $this->introText;
?>
<div id="eb-categories-columns-page" class="eb-container<?php if ($this->config->activate_transparent) echo ' eb-container-transparent'; ?>">
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
        <div class="eb-description"><?php echo $description;?></div>
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