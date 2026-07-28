<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
?>
<table class="admintable" cellpadding="4" cellspacing="1">
	<?php foreach ($this->staticHeaders as $header) { ?>
	<tr>
		<td><b><?php echo Text::_('RSFP_'.$header); ?></b></td>
	</tr>
	<tr>
		<td>
			<?php
			if ($header == 'confirmed')
			{
				echo $this->staticFields->confirmed ? Text::_('RSFP_YES') : Text::_('RSFP_NO');
			}
			else
			{
				echo $this->staticFields->{$header};
			}
			?>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<?php } ?>
	<?php foreach ($this->fields as $field) { ?>
	<tr>
		<td><b><?php echo $field[0]; ?></b></td>
	</tr>
	<tr>
		<td><?php echo $field[1]; ?></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
	<?php } ?>
</table>