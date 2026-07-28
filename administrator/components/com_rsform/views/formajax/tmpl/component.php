<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$count = 0;
foreach ($this->fields as $fieldType => $fields)
{
    if ($fields)
    {
        ?>
		<div class="alert alert-error" id="rsformerror<?php echo $count; ?>" style="display:none;"></div>
		<div class="form-horizontal">
		<?php
        foreach ($fields as $field)
        {
            if (strpos($field->type, 'hidden') !== false)
            {
				echo $field->body;
				continue;
			}
            ?>
			<div class="control-group" id="id<?php echo $field->name; ?>">
				<div class="control-label">
					<?php echo $field->label; ?>
				</div>
				<div class="controls">
					<?php echo $field->body; ?>
				</div>
			</div>
		    <?php
        }
        ?>
		</div>
	    <?php
    }
    if ($fieldType == 'general')
    {
        ?>
		<div class="form-horizontal">
			<div class="control-group">
				<div class="control-label">
					<label for="Published"><?php echo Text::_('JPUBLISHED'); ?></label>
				</div>
				<?php echo $this->published; ?>
			</div>
		</div>
	    <?php
    }
    if ($fieldType != 'editor')
    {
        echo '{rsfsep}';
    }

    $count++;
}