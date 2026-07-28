<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

abstract class EventbookingHelperModal
{
	/**
	 * @param   string  $selector
	 * @param   string  $containerClass
	 * @param   string  $iframeHeight
	 */
	public static function iframeModal($selector = '', $containerClass = 'eb-modal-container', $iframeHeight = '480px')
	{
		static $scriptLoaded = false;

		static $loadedSelectors = [];

		if ($scriptLoaded === false)
		{
			$document = Factory::getApplication()->getDocument();
			$rootUri  = Uri::root(true);
			$document->addScript($rootUri . '/media/com_eventbooking/assets/js/tingle/tingle.min.js')
				->addStyleSheet($rootUri . '/media/com_eventbooking/assets/js/tingle/tingle.min.css');
			$scriptLoaded = true;

			$config = EventbookingHelper::getConfig();

			if ($config->activate_transparent)
			{
				$document->addStyleDeclaration('.tingle-modal-box {background-color: transparent;};');
			}
		}

		// Sometime, we just only want to load modal script
		if (empty($selector))
		{
			return;
		}

		if (isset($loadedSelectors[$selector]))
		{
			return;
		}

		$script = <<<SCRIPT
			document.addEventListener('DOMContentLoaded', function () {
		        [].slice.call(document.querySelectorAll('$selector')).forEach(function (link) {
		            link.addEventListener('click', function (e) {
		            	e.preventDefault();
		                var modal = new tingle.modal({
		                	cssClass: ['$containerClass'],
		                    onClose: function () {
		                        modal.destroy();
		                    }
		                });		                
		                modal.setContent('<iframe width="100%" height="$iframeHeight" src="' + link.href + '" frameborder="0" allowfullscreen></iframe>');
		                modal.open();
		            });
		        });
		    });
SCRIPT;

		Factory::getApplication()->getDocument()->addScriptDeclaration($script);

		$loadedSelectors[$selector] = true;
	}
}
