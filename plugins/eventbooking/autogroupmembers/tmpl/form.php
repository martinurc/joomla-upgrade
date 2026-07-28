<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

/**
 * Layout variables
 *
 * @var \Joomla\Registry\Registry $params
 */
?>
<div class="control-group">
	<label class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('enable_auto_group_members', Text::_('EB_ENABLE_AUTO_GROUP_MEMBERS')); ?>
	</label>
	<div class="controls">
		<?php echo EventbookingHelperHtml::getBooleanInput('enable_auto_group_members', $params->get('enable_auto_group_members', $params->get('default_enable_auto_group_members', 0))); ?>
	</div>
</div>
