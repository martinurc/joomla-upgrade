<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

$field_link=array('name','formatname','firstname','lastname','avatar','avatar_mini','username');

?>
<tr class="profile<?php echo substr(md5($this->user->username),0,10) ?>">
<?php if($this->params->def('col1_enable', 0)) : 
	$fields=$this->params->def('col1_fields', array());
?>
<td>

	
		<?php if(is_array($fields)) foreach($fields as $field) : ?>
			
				<div class="<?php echo $field ?>">
					
				<?php if(in_array($field,$field_link)) : ?><a href="<?php echo $this->user->getLink($this->url_options); ?>"><?php endif; ?>
					<?php  echo $this->user->getField($field,true); ?>
				<?php if(in_array($field,$field_link)) : ?></a><?php endif; ?>
					
				</div>
			
		<?php endforeach; ?>


</td>
<?php endif; ?>

<?php if($this->params->def('col2_enable', 0)) : 
	$fields=$this->params->def('col2_fields', array());
?>
<td>

	<?php if(is_array($fields)) foreach($fields as $field) : ?>
		
			<div class="<?php echo $field ?>">
				
			<?php if(in_array($field,$field_link)) : ?><a href="<?php echo $this->user->getLink($this->url_options); ?>"><?php endif; ?>
				<?php  echo $this->user->getField($field,true); ?>
			<?php if(in_array($field,$field_link)) : ?></a><?php endif; ?>
				
			</div>
		
	<?php endforeach; ?>

</td>
<?php endif; ?>

<?php if($this->params->def('col3_enable', 0)) : 
	$fields=$this->params->def('col3_fields', array());
?>
<td>

	<?php if(is_array($fields)) foreach($fields as $field) : ?>
		
			<div class="<?php echo $field ?>">
				
			<?php if(in_array($field,$field_link)) : ?><a href="<?php echo $this->user->getLink($this->url_options); ?>"><?php endif; ?>
				<?php  echo $this->user->getField($field,true); ?>
			<?php if(in_array($field,$field_link)) : ?></a><?php endif; ?>
				
			</div>
		
	<?php endforeach; ?>

</td>
<?php endif; ?>

<?php if($this->params->def('col4_enable', 0)) : 
	$fields=$this->params->def('col4_fields', array());
?>
<td>

	<?php if(is_array($fields)) foreach($fields as $field) : ?>
		
			<div class="<?php echo $field ?>">
				
			<?php if(in_array($field,$field_link)) : ?><a href="<?php echo $this->user->getLink($this->url_options); ?>"><?php endif; ?>
				<?php  echo $this->user->getField($field,true); ?>
			<?php if(in_array($field,$field_link)) : ?></a><?php endif; ?>
				
			</div>
		
	<?php endforeach; ?>

</td>
<?php endif; ?>

<?php if($this->params->def('col5_enable', 0)) : 
	$fields=$this->params->def('col5_fields', array());
?>
<td>

	<?php if(is_array($fields)) foreach($fields as $field) : ?>
		
			<div class="<?php echo $field ?>">
				
			<?php if(in_array($field,$field_link)) : ?><a href="<?php echo $this->user->getLink($this->url_options); ?>"><?php endif; ?>
				<?php  echo $this->user->getField($field,true); ?>
			<?php if(in_array($field,$field_link)) : ?></a><?php endif; ?>
				
			</div>
		
	<?php endforeach; ?>

</td>
<?php endif; ?>

<?php if($this->params->def('col6_enable', 0)) : 
	$fields=$this->params->def('col6_fields', array());
?>
<td>

	<?php if(is_array($fields)) foreach($fields as $field) : ?>
		
			<div class="<?php echo $field ?>">
				
			<?php if(in_array($field,$field_link)) : ?><a href="<?php echo $this->user->getLink($this->url_options); ?>"><?php endif; ?>
				<?php  echo $this->user->getField($field,true); ?>
			<?php if(in_array($field,$field_link)) : ?></a><?php endif; ?>
				
			</div>
		
	<?php endforeach; ?>

</td>
<?php endif; ?>
</tr>