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
 * @var string $dependencyEventIds
 * @var string $dependencyType
 */
?>
<div class="control-group">
	<div class="control-label">
		<?php
		echo EventbookingHelperHtml::getFieldLabel(
			'dependency_event_ids',
			Text::_('PLG_EB_DEPENDENCY_EVENT_IDS'),
			Text::_('PLG_EB_DEPENDENCY_EVENT_IDS_EXPLAIN')
		);
		?>
	</div>
	<div class="controls">
		<input type="text" name="dependency_event_ids" value="<?php echo $dependencyEventIds ?>" class="input-xxlarge form-control"/>
	</div>
</div>
<div class="control-group">
	<div class="control-label">
		<?php
		echo EventbookingHelperHtml::getFieldLabel('dependency_type', Text::_('PLG_EB_DEPENDENCY_TYPE')); ?>
	</div>
	<div class="controls">
		<?php
		$options   = [];
		$options[] = HTMLHelper::_('select.option', 'all', Text::_('PLG_EB_DEPENDENCY_TYPE_ALL'));
		$options[] = HTMLHelper::_('select.option', 'one', Text::_('PLG_EB_DEPENDENCY_TYPE_ONE'));

		echo HTMLHelper::_('select.genericlist', $options, 'dependency_type', 'class="form-select"', 'value', 'text', $dependencyType);
		?>
	</div>
</div>

