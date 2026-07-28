<?php
/**
 * @package            Joomla
 * @subpackage         Event Booking
 * @author             Tuan Pham Ngoc
 * @copyright          Copyright (C) 2010 - 2024 Ossolution Team
 * @license            GNU/GPL, see LICENSE.php
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

$form                    = Form::getInstance('common_tags', JPATH_ADMINISTRATOR . '/components/com_eventbooking/view/configuration/forms/common_tags.xml');
$formData['common_tags'] = [];

if ($config->common_tags)
{
	$commonTags = json_decode($config->common_tags, true);
}
else
{
	$commonTags = [];
}

foreach ($commonTags as $commonTag)
{
	$formData['common_tags'][] = [
		'name'  => $commonTag['name'],
		'value' => $commonTag['value'],
	];
}

$form->bind($formData);

?>
<p class="text-info"><?php echo Text::_('EB_COMMON_TAGS_EXPLAIN'); ?></p>
<?php

foreach ($form->getFieldset() as $field)
{
	echo $field->input;
}