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
 * @var string $relatedEventIds
 */
?>
<div class="control-group">
	<div class="control-label">
		<?php
		echo EventbookingHelperHtml::getFieldLabel(
			'related_event_ids',
			Text::_('PLG_EB_RELATED_EVENTS_IDS'),
			Text::_('PLG_EB_DEPENDENCY_EVENT_IDS_EXPLAIN')
		);
		?>
	</div>
	<div class="controls">
		<input type="text" name="related_event_ids" value="<?php echo $relatedEventIds ?>" class="input-xxlarge form-control"/>
	</div>
</div>

