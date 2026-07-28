<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

$dispatcher	= JEventDispatcher::getInstance();

?>

<?php 
echo(implode(' ',$dispatcher->trigger('renderBeforeList',array($this->items,$this->config))));
?>

<div class="jsn_list table-responsive">
<?php if ($this->params->get('show_page_heading')) : ?>
	<div class="page-header">
		<h2><?php echo $this->params->get('page_heading'); ?></h2>
	</div>
<?php endif; ?>
<?php

if ($this->params->def('search_enabled', 0) && !(JFactory::getApplication()->input->get('search','0') && $this->params->def('search_hideform', 0)))
{
	echo $this->loadTemplate('search');
}
?>
<div id="jsn_listresult">
<?php 
echo(implode(' ',$dispatcher->trigger('renderBeforeResultList',array($this->items,$this->config))));
?>
<?php
if(count($this->items)>0 && !($this->params->def('search_enabled',0) && !$this->params->def('search_showuser',0) && !JFactory::getApplication()->input->get('search',0)))
{
	if($this->params->def('export', 0))
	{
		$url = JUri::getInstance()->toString();
		$uri = new JUri($url);
		$uri->setVar('start',0);
		$uri->setVar('limit',0);
		$uri->setVar('layout','export');
		$uri->setVar('format','raw');
		?>
			<div class="pull-right jsn-export"><a target="_blank" href="<?php echo $uri->toString(); ?>" class="btn"><i class="jsn-icon jsn-icon-share"></i> <?php echo JText::_('COM_JSN_EXPORT'); ?></a></div>
		<?php
	}
	if($this->params->def('show_total', 1))
	{
		echo('<div class="jsn-total"><span class="label label-warning">'.$this->pagination->total.' '.JText::_('COM_JSN_MEMBERS').'</span></div>');
	}
}
if(!isset($this->items) || !is_array($this->items) || empty($this->items))
{
	?>
	<div class="alert alert-warning">
	<?php echo(JText::_('COM_JSN_NORESULT')); ?>
	</div>
	<?php
}

?>


<?php // Add pagination links ?>
<?php //if (!empty($this->items)) : ?>
	<?php if (($this->params->def('show_pagination', 1)==2 || $this->params->def('show_pagination', 1)==3) && $this->pagination->pagesTotal > 1) : ?>
	<div class="pagination" style="clear:both">

		

		<?php echo $this->pagination->getPagesLinks(); ?>

		<?php if ($this->params->def('show_pagination_results', 1)) : ?>
			<p class="counter">
				<?php echo $this->pagination->getPagesCounter(); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php endif; ?>
<?php  //endif; ?>


<?php if(is_array($this->items) && count($this->items)) : ?>
<style>
 .listusers img{width:40px;}
 .listusers .name,.listusers .formatname{font-weight:bold;}
</style>

<table class="table table-striped listusers">	

<?php
	// Table Header
	if(($this->params->def('col1_enable', 0) && $this->params->def('col1_header', '')) || ($this->params->def('col2_enable', 0) && $this->params->def('col2_header', '')) ||  ($this->params->def('col3_enable', 0) && $this->params->def('col3_header', '')) || ($this->params->def('col4_enable', 0) && $this->params->def('col4_header', '')) || ($this->params->def('col5_enable', 0) && $this->params->def('col5_header', '')) || ($this->params->def('col6_enable', 0) && $this->params->def('col6_header', '')))
	{
		echo('<tr>');
		if($this->params->def('col1_enable', 0)) echo('<th>'.JText::_($this->params->def('col1_header', '')).'</th>');
		if($this->params->def('col2_enable', 0)) echo('<th>'.JText::_($this->params->def('col2_header', '')).'</th>');
		if($this->params->def('col3_enable', 0)) echo('<th>'.JText::_($this->params->def('col3_header', '')).'</th>');
		if($this->params->def('col4_enable', 0)) echo('<th>'.JText::_($this->params->def('col4_header', '')).'</th>');
		if($this->params->def('col5_enable', 0)) echo('<th>'.JText::_($this->params->def('col5_header', '')).'</th>');
		if($this->params->def('col6_enable', 0)) echo('<th>'.JText::_($this->params->def('col6_header', '')).'</th>');
		echo('</tr>');
	}
?>

<?php

$this->url_options = array();
$this->url_options['Itemid'] = $this->params->def('profile_menuid', '');
if($this->params->def('profile_back', 1)) $this->url_options['back'] = 1;

global $JSNLIST_DISPLAYED_ID;
if(is_array($this->items)) foreach($this->items as $item)
{
	$JSNLIST_DISPLAYED_ID=$item->id;
	$this->user = JsnHelper::getUser($item->id);
	echo $this->loadTemplate('user');
}
$JSNLIST_DISPLAYED_ID=false;
?>

</table>
<?php endif; ?>

<?php // Add pagination links ?>
<?php //if (!empty($this->items)) : ?>
	<?php if (($this->params->def('show_pagination', 1)==1 || $this->params->def('show_pagination', 1)==3) && $this->pagination->pagesTotal > 1) : ?>
	<div class="pagination" style="clear:both">

		<?php if ($this->params->def('show_pagination_results', 1)) : ?>
			<p class="counter">
				<?php echo $this->pagination->getPagesCounter(); ?>
			</p>
		<?php endif; ?>

		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>
	<?php endif; ?>
<?php  //endif; ?>
<?php 
echo(implode(' ',$dispatcher->trigger('renderAfterResultList',array($this->items,$this->config))));
?>
</div>

</div>

<?php 
echo(implode(' ',$dispatcher->trigger('renderAfterList',array($this->items,$this->config))));
?>