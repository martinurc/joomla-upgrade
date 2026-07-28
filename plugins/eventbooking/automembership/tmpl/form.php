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
 * @var array $lists
 */
?>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('auto_membership_plan_ids', Text::_('EB_AUTO_SUBSCRIBE_PLANS'), Text::_('EB_AUTO_SUBSCRIBE_PLANS_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<?php echo EventbookingHelperHtml::getChoicesJsSelect($lists['auto_membership_plan_ids']); ?>
	</div>
</div>

