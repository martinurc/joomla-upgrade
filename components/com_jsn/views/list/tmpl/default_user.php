<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

?>

<div class="jsn-l-w<?php echo $this->span; ?> jsn-l profile<?php echo substr(md5($this->user->username),0,10) ?>">

	<!-- Top Container -->
	<div class="jsn-l-top <?php echo ($this->config->get('avatar',1) ? 'jsn-l-top-a' : ''); ?>">

		<!-- Avatar Container -->
		<?php
			if($this->config->get('avatar',1)) :
		?> 
			<div class="jsn-l-avatar">
				<a href="<?php echo $this->user->getLink($this->url_options); ?>">
				<?php
					echo $this->user->getField('avatar_mini',true);
				?>
				</a>
			</div>
		<?php
			endif;
		?>

		<!-- Title Container -->
		<div class="jsn-l-title">
			<h3>
				<a href="<?php echo $this->user->getLink($this->url_options); ?>">
				<?php echo $this->user->getField('formatname'); ?>
				</a>
			</h3>

			<?php if($this->config->get('status',1)) : ?>	
				<?php echo $this->user->getField('status'); ?>
			<?php endif; ?>
		</div>

		<div class="jsn-l-fields">
			<?php 
			$fields=$this->params->def('list_fields', array());
			if(is_array($fields)) foreach($fields as $field) : ?>
				<?php $value=$this->user->getField($field,true); if(!empty($value)) : ?>
				<div class="<?php echo $field ?>"><?php if($this->params->def('show_titles', 0)) : ?><span class="jsn-l-field-title"><?php echo JText::_($this->fields_title[$field]['title']); ?>: </span><?php endif; ?><span class="jsn-l-field-value"><?php  echo $value; ?></span></div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>	
	</div>
</div>
