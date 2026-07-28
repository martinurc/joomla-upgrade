<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;

$listOrder	= $this->escape($this->filter_order);
$listDirn	= $this->escape($this->filter_order_Dir);

HTMLHelper::_('behavior.core');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('stylesheet', 'com_rsform/directory.css', array('relative' => true, 'version' => 'auto'));

Text::script('RSFP_SUBM_DIR_PLEASE_SELECT_AT_LEAST_ONE');
if ($this->directory->AllowCSVFullDownload)
{
	Text::script('COM_RSFORM_SUBMISSIONS_DIRECTORY_AN_ERROR_HAS_OCCURRED_ATTEMPTING_TO_CONTINUE_IN_A_FEW_SECONDS');
}
?>

<?php if ($this->params->get('show_page_heading', 1)) { ?>
<h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
<?php } ?>

<form action="<?php echo $this->escape($this->url); ?>" method="post" name="adminForm" id="adminForm" class="form-inline">
	<?php if ($this->hasSearchFields || $this->directory->enablecsv) { ?>
	<div class="rsfp-directory-search">
		<?php if ($this->hasSearchFields) { ?>
		<?php echo Text::_('RSFP_SEARCH'); ?> <input type="text" id="rsfilter" name="filter_search" value="<?php echo $this->escape($this->filter_search); ?>" data-directory-change="submit" />
		<button type="button" class="btn btn-primary" data-directory-click="submit"><?php echo Text::_('RSFP_GO'); ?></button>
		<button type="button" class="btn btn-secondary" data-directory-click="reset"><?php echo Text::_('RSFP_RESET'); ?></button>
		<?php } ?>
		<?php
        if ($this->directory->enablecsv)
        {
            if (!$this->directory->AllowCSVFullDownload)
            {
                ?>
                <button data-directory-click="downloadCSV" type="button" class="btn btn-secondary"><?php echo Text::_('RSFP_SUBM_DIR_DOWNLOAD_CSV'); ?></button>
                <?php
            }
            else
            {
                ?>
                <button data-directory-click="downloadFullCSV" data-directory-params="[<?php echo $this->limit; ?>,<?php echo $this->total; ?>]" type="button" class="btn btn-secondary"><?php echo Text::_('COM_RSFORM_DOWNLOAD_ALL_AS_CSV'); ?></button>
                <?php
            }
            ?>
		    <div class="clearfix"></div>
		    <?php
        }
        ?>
	</div>
	<?php
    }
	if ($this->directory->enablecsv && $this->directory->AllowCSVFullDownload)
    {
        ?>
        <div class="rsform-dir-progress-wrapper" style="display: none;"><div class="rsform-dir-progress-bar" id="dirProgressBar">0%</div></div>
        <?php
    }
    ?>
	<div class="clearfix"></div>
    <?php
    if ($this->dynamicFilters && is_array($this->dynamicFilters))
    {
        ?>
        <div class="rsfp-directory-dynamic-filters">
            <?php
            foreach ($this->dynamicFilters['name'] as $index => $fieldName)
            {
                $this->componentId = $this->getFieldComponentId($fieldName, $this->formId);

                if ($this->componentId === null)
                {
                    Factory::getApplication()->enqueueMessage(Text::sprintf('COM_RSFORM_DIRECTORY_DYNAMIC_FIELD_NOT_FOUND', $fieldName, $this->formId), 'warning');
                    continue;
                }

	            $this->fieldId = OutputFilter::stringURLSafe($fieldName);
	            $this->fieldName = $this->fieldLabel = $fieldName;
                if ($this->componentId > 0)
                {
	                $this->fieldProperties = RSFormProHelper::getComponentProperties($this->componentId);
	                if (isset($this->fieldProperties['CAPTION']))
	                {
		                $this->fieldLabel = $this->fieldProperties['CAPTION'];
	                }
                }
                else
                {
                    $this->fieldLabel = Text::_('RSFP_' . $fieldName);
                }

	            $this->fieldValues = array(HTMLHelper::_('select.option', '', Text::_('COM_RSFORM_DIRECTORY_FILTER_PLEASE_SELECT')));
                $this->selectedValue = isset($this->dynamicSearch[$fieldName]) ? $this->dynamicSearch[$fieldName] : '';
	            $tmpValues = RSFormProHelper::explode(RSFormProHelper::isCode($this->dynamicFilters['value'][$index]));
                foreach ($tmpValues as $tmpValue)
                {
                    if (strpos($tmpValue, '|') !== false)
                    {
                        list($value, $label) = explode('|', $tmpValue, 2);
                    }
                    else
                    {
                        $value = $label = $tmpValue;
                    }
                    $this->fieldValues[] = HTMLHelper::_('select.option', $value, $label);
                }
                echo $this->loadTemplate('dynamicfilter');
            }
            ?>
        </div>
        <?php
    }
    ?>
	<?php 
		$directoryLayout = $this->loadTemplate('layout');
        try
        {
            eval($this->directory->ListScript);
        }
        catch (Throwable $e)
        {
            Factory::getApplication()->enqueueMessage(htmlspecialchars($e->getMessage(), ENT_COMPAT, 'utf-8'), 'warning');
        }
		echo $directoryLayout;
	?>

	<?php echo $this->pagination->getListFooter(); ?>

	<input type="hidden" name="option" value="com_rsform" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="controller" value="directory" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
</form>