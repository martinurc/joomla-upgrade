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

/**
 * Layout variables
 *
 * @var array $groupIds
 */
?>
<div class="control-group">
	<div class="control-label">
		<?php
		echo EventbookingHelperHtml::getFieldLabel(
			'joomla_group_ids',
			Text::_('PLG_EVENTBOOKING_JOOMLA_ASSIGN_TO_JOOMLA_GROUPS'),
			Text::_('PLG_EVENTBOOKING_JOOMLA_ASSIGN_TO_JOOMLA_GROUPS_EXPLAIN')
		);
		?>
	</div>
	<div class="controls">
		<?php
		echo EventbookingHelperHtml::getChoicesJsSelect(
			HTMLHelper::_('access.usergroup', 'joomla_group_ids[]', $groupIds, ' multiple="multiple" size="6" ', false)
		);
		?>
	</div>
</div>