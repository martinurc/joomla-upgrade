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
 * @var array                    $options
 * @var array                    $listIds
 * @var array                    $groupOptions
 * @var Joomla\Registry\Registry $params
 */
?>
<div class="control-group">
	<div class="control-label">
		<?php
		echo EventbookingHelperHtml::getFieldLabel(
			'mailchimp_list_ids',
			Text::_('PLG_EB_MAILCHIMP_ASSIGN_TO_LISTS'),
			Text::_('PLG_EB_ACYMAILING_ASSIGN_TO_LISTS_EXPLAIN')
		);
		?>
	</div>
	<div class="controls">
		<?php
		echo EventbookingHelperHtml::getChoicesJsSelect(
			HTMLHelper::_(
				'select.genericlist',
				$options,
				'mailchimp_list_ids[]',
				'class="inputbox" multiple="multiple" size="10"',
				'value',
				'text',
				$listIds
			)
		);
		?>
	</div>
</div>
<?php
if (count($groupOptions))
{
?>
	<div class="control-group">
		<div class="control-label">
			<?php
			echo EventbookingHelperHtml::getFieldLabel(
				'mailchimp_group_ids',
				Text::_('PLG_EB_MAILCHIMP_ADD_TO_GROUPS'),
				Text::_('PLG_EB_MAILCHIMP_ADD_TO_GROUPS_EXPLAIN')
			);
			?>
		</div>
		<div class="controls">
			<?php
			echo EventbookingHelperHtml::getChoicesJsSelect(
				HTMLHelper::_(
					'select.genericlist',
					$groupOptions,
					'mailchimp_group_ids[]',
					'class="form-select advSelect" multiple="multiple" size="10"',
					'value',
					'text',
					explode(',', $params->get('mailchimp_group_ids', ''))
				)
			);
			?>
		</div>
	</div>
<?php
}


