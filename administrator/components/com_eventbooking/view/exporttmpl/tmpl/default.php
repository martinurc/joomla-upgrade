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

?>
<form action="index.php?option=com_eventbooking&view=exporttmpl" method="post" name="adminForm" id="adminForm"
      class="form form-horizontal">
    <div class="control-group">
        <div class="control-label"><?php echo Text::_('EB_TITLE'); ?></div>
        <div class="controls">
            <input type="text" name="title" value="<?php echo $this->escape($this->item->title); ?>"
                   class="input-xlarge form-control w-100" size="70"/>
        </div>
    </div>
    <div class="control-group">
        <div class="control-label">
			<?php echo EventbookingHelperHtml::getFieldLabel('Field', Text::_('EB_EXPORT_TMPL_FIELDS'), Text::_('EB_EXPORT_TMPL_FIELDS_EXPLAIN')); ?>
        </div>
        <div class="controls">
	        <?php
				foreach ($this->form->getFieldset() as $field)
				{
					echo $field->input;
				}
			?>
        </div>
    </div>
    <div class="control-group">
        <div class="control-label">
			<?php echo Text::_('EB_PUBLISHED'); ?>
        </div>
        <div class="controls">
			<?php echo $this->lists['published']; ?>
        </div>
    </div>
    <div class="clearfix"></div>
	<?php echo HTMLHelper::_('form.token'); ?>
    <input type="hidden" name="id" value="<?php echo (int) $this->item->id; ?>"/>
    <input type="hidden" name="task" value=""/>
</form>