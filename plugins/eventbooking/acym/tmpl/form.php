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
 * @var array $allLists
 * @var array $listIds
 */
?>
<div class="control-group">
	<div class="control-label">
		<?php
		echo EventbookingHelperHtml::getFieldLabel('acymailing_list_ids', Text::_('PLG_EB_ACYM_ASSIGN_TO_LIST_USER'), Text::_('PLG_EB_ACYM_ASSIGN_TO_LIST_USER_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<?php
		echo EventbookingHelperHtml::getChoicesJsSelect(
			HTMLHelper::_(
				'select.genericlist',
				$allLists,
				'acymailing_list_ids[]',
				'class="form-select" multiple="multiple" size="10"',
				'id',
				'name',
				$listIds
			)
		);
		?>
	</div>
</div>