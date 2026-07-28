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

/**
 * Layout variables
 *
 * @var string $socialUrl
 */

if (empty($this->config->social_sharing_buttons))
{
	$shareOptions = [
		'Facebook',
		'Twitter',
		'LinkedIn',
		'Delicious',
		'Digg',
		'Pinterest',
	];
}
else
{
	$shareOptions = explode(',', $this->config->social_sharing_buttons);
}

$title = $this->item->title;

/* @var EventbookingHelperBootstrap $bootstrapHelper*/
$bootstrapHelper   = $this->bootstrapHelper;
?>
<div class="eb-social-sharing-buttons" class="row-fluid">
	<?php
	if (in_array('Facebook', $shareOptions))
	{
		$alt       = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'FaceBook');
		$iconClass = $bootstrapHelper->getClassMapping('fa fab fa-facebook-square');

		echo '<a href="https://www.facebook.com/sharer.php?u=' . rawurlencode($socialUrl) . '&amp;t=' . rawurlencode($title) . '" title="' . $alt . '" class="btn eb-btn-facebook" target="blank" >
					<i class="' . $iconClass . '"></i>                        
			 </a>';
	}

	if (in_array('Twitter', $shareOptions))
	{
		$alt       = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'Twitter');
		$iconClass = $bootstrapHelper->getClassMapping('fa fab fa-twitter-square');

		echo '<a href="https://twitter.com/?status=' . rawurlencode($title . ' ' . $socialUrl) . '" title="' . $alt . '" class="btn eb-btn-twitter" target="blank" >
				  <i class="' . $iconClass . '"></i>  
			  </a>';
	}

	if (in_array('LinkedIn', $shareOptions))
	{
		$alt       = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'LinkedIn');
		$iconClass = $bootstrapHelper->getClassMapping('fa fab fa-linkedin-square');

		echo '<a href="https://www.linkedin.com/shareArticle?mini=true&amp;url=' . rawurlencode($socialUrl) . '&amp;title=' . $title . '" title="' . $alt . '" target="blank" class="btn eb-btn-linkedin">
				   <i class="' . $iconClass . '"></i>       
			  </a>';
	}

	if (in_array('Delicious', $shareOptions))
	{
		$alt       = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'Delicious');
		$iconClass = $bootstrapHelper->getClassMapping('fa fab fa-delicious');

		echo '<a href="https://del.icio.us/post?url=' . rawurlencode($socialUrl) . '&amp;title=' . $title . '" title="' . $alt . '" target="blank" class="btn eb-btn-delicious">
				   <i class="' . $iconClass . '"></i>       
			  </a>';
	}

	if (in_array('Digg', $shareOptions))
	{
		$alt       = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'Digg');
		$iconClass = $bootstrapHelper->getClassMapping('fa fab fa-digg');

		echo '<a href="https://digg.com/submit?url=' . rawurlencode($socialUrl) . '&amp;title=' . $title . '" title="' . $alt . '" target="blank" class="btn eb-btn-digg">
				   <i class="' . $iconClass . '"></i>       
			  </a>';
	}

	if (in_array('Pinterest', $shareOptions))
	{
		$alt       = Text::sprintf('EB_SUBMIT_ITEM_IN_SOCIAL_NETWORK', $title, 'Pinterest');
		$iconClass = $bootstrapHelper->getClassMapping('fa fab fa-pinterest-square');

		echo '<a href="https://www.pinterest.com/pin/create/button/?url=' . rawurlencode($socialUrl) . '" title="' . $alt . '" target="blank" class="btn eb-btn-pinterest">
				   <i class="' . $iconClass . '"></i>       
			  </a>';
	}
	?>
</div>