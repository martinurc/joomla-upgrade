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
use Joomla\Registry\Registry;

/**
 * Layout variables
 *
 * @var EventbookingTableEvent $row
 */

$params = new Registry($row->params ?? '{}');
?>
<div class="control-group">
	<div class="control-label">
		<?php echo EventbookingHelperHtml::getFieldLabel('enable_move_registrants', Text::_('EB_ENABLE_MOVE_REGISTRANTS'), Text::_('EB_ENABLE_MOVE_REGISTRANTS_EXPLAIN')); ?>
	</div>
	<div class="controls">
		<?php echo EventbookingHelperHtml::getBooleanInput('enable_move_registrants', $params->get('enable_move_registrants', 1)) ?>
	</div>
</div>
