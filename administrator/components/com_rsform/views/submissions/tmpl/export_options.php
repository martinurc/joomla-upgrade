<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
?>
<table class="admintable" width="100%">
	<tr>
		<td style="width: 235px;" align="right" class="key">
			<span class="<?php echo $this->tooltipClass; ?>" title="<?php echo RSFormProHelper::getTooltipText(Text::_('RSFP_EXPORT_HEADERS'), Text::_('RSFP_EXPORT_HEADERS_DESC')); ?>">
                <label for="ExportHeaders"><?php echo Text::_('RSFP_EXPORT_HEADERS');?></label>
			</span>
		</td>
		<td>
			<input type="checkbox" onchange="updateCSVPreview();" name="ExportHeaders" id="ExportHeaders" value="1" checked="checked" />
		</td>
	</tr>
    <tr>
        <td style="width: 235px;" align="right" class="key">
			<span class="<?php echo $this->tooltipClass; ?>" title="<?php echo RSFormProHelper::getTooltipText(Text::_('RSFP_EXPORT_MULTIPLE_SEPARATOR'), Text::_('RSFP_EXPORT_MULTIPLE_SEPARATOR_DESC')); ?>">
                <label for="ExportMultipleSeparator"><?php echo Text::_('RSFP_EXPORT_MULTIPLE_SEPARATOR');?></label>
			</span>
        </td>
        <td>
            <input type="text" class="rs_inp rs_5" style="text-align: center" name="ExportMultipleSeparator" id="ExportMultipleSeparator" value="<?php echo $this->escape($this->multipleSeparator); ?>" size="5" />
        </td>
    </tr>
	<?php if ($this->exportType == 'csv') { ?>
	<tr>
		<td style="width: 235px;" align="right" class="key">
		<span class="<?php echo $this->tooltipClass; ?>" title="<?php echo RSFormProHelper::getTooltipText(Text::_('RSFP_EXPORT_STRIP_LINES'), Text::_('RSFP_EXPORT_STRIP_LINES_DESC')); ?>">
            <label for="StripLines"><?php echo Text::_('RSFP_EXPORT_STRIP_LINES');?></label>
		</span>
		</td>
		<td>
			<input type="checkbox" id="StripLines" name="StripLines" value="1" />
		</td>
	</tr>
	<tr>
		<td style="width: 235px;" align="right" class="key">
			<span class="<?php echo $this->tooltipClass; ?>" title="<?php echo RSFormProHelper::getTooltipText(Text::_('RSFP_EXPORT_DELIMITER'), Text::_('RSFP_EXPORT_DELIMITER_DESC')); ?>">
                <label for="ExportDelimiter"><?php echo Text::_('RSFP_EXPORT_DELIMITER');?></label>
			</span>
		</td>
		<td>
			<input type="text" class="rs_inp rs_5" style="text-align: center" onkeyup="updateCSVPreview();" name="ExportDelimiter" id="ExportDelimiter" value="," size="5" />
		</td>
	</tr>
	<tr>
		<td style="width: 235px;" align="right" class="key">
			<span class="<?php echo $this->tooltipClass; ?>" title="<?php echo RSFormProHelper::getTooltipText(Text::_('RSFP_EXPORT_ENCLOSURE'), Text::_('RSFP_EXPORT_ENCLOSURE_DESC')); ?>">
                <label for="ExportFieldEnclosure"><?php echo Text::_('RSFP_EXPORT_ENCLOSURE');?></label>
			</span>
		</td>
		<td>
			<input type="text" class="rs_inp rs_5" style="text-align: center" onkeyup="updateCSVPreview();" name="ExportFieldEnclosure" id="ExportFieldEnclosure" value="&quot;" size="5" />
		</td>
	</tr>
	<?php } ?>
</table>