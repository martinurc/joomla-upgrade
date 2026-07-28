<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

// Add Custom Fields
//if(class_exists('FieldsHelper')) FieldsHelper::prepareForm('com_users.user', $this->form, $this->data);

// Set Title
$this->document->setTitle($this->document->title.' - '.JsnHelper::getFormatName($this->data));

// Set Pathway
JFactory::getApplication()->getPathway()->addItem(JsnHelper::getFormatName($this->data));

// Load Events Dispatcher
$dispatcher	= JEventDispatcher::getInstance();

$this->user=JsnHelper::getUser($this->data->id);

$avatar=$this->form->getField('avatar');

?>
<!-- Main Container -->
<div class="jsn-p">

	<?php 
		echo(implode(' ',$dispatcher->trigger('renderBeforeProfile',array($this->data,$this->config))));
	?>

	<div class="jsn-p-opt">
		<?php if (JFactory::getApplication()->input->get('back')=='1') : ?>
				<?php if(JFactory::getUser()->id == $this->data->id) $other_id=''; else $other_id='&user_id='.$this->data->id; ?> 
				<a class="btn btn-xs btn-default" href="#" onclick="window.history.back();return false;">
						<i class="jsn-icon jsn-icon-share"></i> <?php echo JText::_('COM_JSN_BACK'); ?></a>
		<?php endif; ?>
		<?php if (JFactory::getUser()->id == $this->data->id || JFactory::getUser()->authorise('core.edit', 'com_users')) : ?>
				<?php if(JFactory::getUser()->id == $this->data->id) $other_id=''; else $other_id='&user_id='.$this->data->id; ?> 
				<a class="btn btn-xs btn-default" href="<?php echo JRoute::_('index.php?option=com_users&view=profile&layout=edit'.$other_id,false);?>">
						<i class="jsn-icon jsn-icon-cog"></i> <?php echo JText::_('COM_USERS_EDIT_PROFILE'); ?></a>
		<?php endif; ?>
		<?php if ($this->config->get('profile_contact_btn',1) && JFactory::getUser()->id != $this->data->id) :
			$db=JFactory::getDbo();
			$query=$db->getQuery(true)->select($db->quoteName('id'))->from('#__contact_details as c')->where($db->quoteName('user_id').'='.$this->data->id)->where($db->quoteName('published').'=1');
			$db->setQuery($query);
			$contactMenu=JFactory::getApplication()->getMenu()->getItems('link','index.php?option=com_contact&view=featured', true);
			if(count($contactMenu)) $cItemid = $contactMenu->id;
			else $cItemid='';
			if($contact=$db->loadResult()) : ?>
					<a class="btn btn-xs btn-default" href="<?php echo JRoute::_('index.php?option=com_contact&view=contact&Itemid='.$cItemid.'&id='.$contact,false);?>">
						<i class="jsn-icon jsn-icon-paper-plane"></i> <?php echo JText::_('JGLOBAL_EMAIL'); ?></a>
			<?php endif; ?>
		<?php endif; ?>
		<?php 
			echo(implode(' ',$dispatcher->trigger('renderProfileButtons',array($this->data,$this->config))));
		?>
	</div>

	<!-- Top Container -->
	<div class="jsn-p-top <?php echo ($avatar ? 'jsn-p-top-a' : ''); ?>">

		<!-- Avatar Container -->
		<?php
			if($avatar) :
		?> 
			<div class="jsn-p-avatar">
				<?php
					echo $this->user->getField('avatar');
				?>
			</div>
		<?php
			endif;
		?>

		<!-- Title Container -->
		<div class="jsn-p-title">
			<h3>
				<?php echo $this->user->getField('formatname'); ?>
			</h3>

			<?php if($this->config->get('status',1)) : ?>	
				<?php echo $this->user->getField('status'); ?>
			<?php endif; ?>
		</div>

		<!-- Before Fields Container -->
		<div class="jsn-p-before-fields">
				<?php 
					$registerdate=$this->form->getField('registerdate');
					$lastvisitdate=$this->form->getField('lastvisitdate');
					if( $registerdate || $lastvisitdate ) : ?>
						<div class="jsn-p-dates">
							<?php if($registerdate) : ?>
							<div class="jsn-p-date-reg">
								<b><?php echo JText::_('COM_JSN_MEMBER_SINCE'); ?></b> <?php echo $this->user->getField('registerdate'); ?>
							</div>
							<?php endif; ?>
							<?php if($lastvisitdate) : ?>
							<div class="jsn-p-date-last">
								<b><?php echo JText::_('COM_JSN_LASTVISITDATE'); ?></b> <?php echo $this->user->getField('lastvisitdate'); ?>
							</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				<?php 
				echo(implode(' ',$dispatcher->trigger('renderBeforeFields',array($this->data,$this->config))));
				?>
		</div>		
	</div>

	<!-- Fields Container -->
	<div class="jsn-p-fields">
	<?php 
		$tabs=$dispatcher->trigger('renderTabs',array($this->data,$this->config)); 
		
		$fields_output=implode(' ',$dispatcher->trigger('renderTabBeforeFields',array($this->data,$this->config)));
		$fields_output.=$this->loadTemplate('fields');
		$fields_output.=$this->loadTemplate('params');
		$fields_output.=implode(' ',$dispatcher->trigger('renderTabAfterFields',array($this->data,$this->config)));
		
		if($this->config->get('profile_fg_tabs',1)) echo($fields_output);
		else echo('<fieldset><legend>'.JText::_('COM_JSN_PROFILE_INFO').'</legend><div>'.$fields_output.'</div></fieldset>');
	
		$titles=array();
		$contents=array();
	
		foreach($tabs as $tab)
		{
			if (is_object($tab[0]))
		    {
		        foreach ($tab as $tabobject)
		        {
		            $contents[]='<fieldset><legend>'.$tabobject->title.'</legend>'.$tabobject->content.'</fieldset>';
		        }
		    }
		    else
				$contents[]='<fieldset><legend>'.$tab[0].'</legend>'.$tab[1].'</fieldset>';
		}

		echo(implode(' ',$contents));
	
	?>
	</div>

	<!-- Bottom Container -->
	<div class="jsn-p-bottom">

		<!-- After Fields Container -->
		<div class="jsn-p-after-fields">
			<?php 
				echo(implode(' ',$dispatcher->trigger('renderAfterFields',array($this->data,$this->config))));
			?>
		</div>
	</div>
</div>

<?php 
echo(implode(' ',$dispatcher->trigger('renderAfterProfile',array($this->data,$this->config))));
?>

