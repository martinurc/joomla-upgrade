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
use Joomla\CMS\Router\Route;

/**
 * Layout variables
 * -----------------
 * @var   string $controlGroupClass
 * @var   int $articleId
 */

$termLink = EventbookingHelperHtml::getArticleUrl($articleId);

if (!$termLink)
{
	return;
}

if ($this->config->open_article_on_new_window)
{
	$linkAttrs = 'target="_blank"';
}
else
{
	$linkAttrs = 'class="eb-colorbox-term"';
}
?>
<div class="<?php echo $controlGroupClass;  ?> eb-terms-and-conditions-container">
	<label class="checkbox">
		<input type="checkbox" name="accept_term" value="1" class="validate[required]<?php echo $this->bootstrapHelper->getFrameworkClass('uk-checkbox', 1); ?>" data-errormessage="<?php echo Text::_('EB_ACCEPT_TERMS');?>" />
		<?php echo Text::_('EB_ACCEPT') . ' <a ' . $linkAttrs . ' href="' . Route::_($termLink) . '"><strong>' . Text::_('EB_TERM_AND_CONDITION') . '</strong></a>'; ?>
	</label>
</div>