<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;

$this->document->setTitle($this->document->title.' - '.JsnHelper::getFormatName($this->data));
$dispatcher	= JEventDispatcher::getInstance();

?>

<?php 
echo(implode(' ',$dispatcher->trigger('renderBeforeProfile',array($this->data,$this->config))));
?>

<div class="jsn_profile" style="display:block;">
	<div class="profile jsn_profile_full <?php echo $this->pageclass_sfx?>">

	<?php 
	echo(implode(' ',$dispatcher->trigger('renderBeforeFields',array($this->data,$this->config))));
	?>

	</div>
	
	<div class="jsn-p-fields" style="">
	<?php 
		$tabs=$dispatcher->trigger('renderTabs',array($this->data,$this->config)); 

		$titles=array();
		$contents=array();
	
		foreach($tabs as $tab)
		{
			$contents[]='<fieldset><legend>'.$tab[0].'</legend>'.$tab[1].'</fieldset>';
		}

		echo(implode(' ',$contents));
		
		if(count($tabs)==1) echo "<style>#jsn-profile-tabs{display:none !important;}.z-tabs.flat.horizontal > .z-container > .z-content > .z-content-inner{padding:0}</style>";
		else echo "<style>.jsn-p-fields{margin-top:2em;}.jsn_social .jsn_social_content{padding-top:0px;}</style>";
	?>
	</div>
	<style>
	</style>

	<!-- Bottom Container -->
	<div class="jsn-p-bottom">

		<!-- After Fields Container -->
		<div class="jsn-p-after-fields">
			<?php 
				echo(implode(' ',$dispatcher->trigger('renderAfterFields',array($this->data,$this->config))));
			?>
		</div>
	</div>

	<?php 
	echo(implode(' ',$dispatcher->trigger('renderAfterFields',array($this->data,$this->config))));
	?>

	
	</div>



	<?php 
	echo(implode(' ',$dispatcher->trigger('renderAfterProfile',array($this->data,$this->config))));
	?>