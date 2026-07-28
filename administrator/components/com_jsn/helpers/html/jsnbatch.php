<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Extended Utility class for batch processing widgets.
 *
 * @package     Joomla.Libraries
 * @subpackage  HTML
 * @since       1.7
 */
abstract class JHtmlJsnbatch
{
	/**
	 * Display a batch widget for the access level selector.
	 *
	 * @return  string  The necessary HTML for the widget.
	 *
	 * @since   1.7
	 */
	public static function access()
	{
		JHtml::_('bootstrap.tooltip', '.modalTooltip', array('container' => '.modal-body'));

		// Create the batch selector to change an access level on a selection list.
		return
			'<label id="batch-access-lbl" for="batch-access" class="modalTooltip" '
			. 'title="' . JHtml::tooltipText('COM_JSN_BATCH_ACCESS', 'COM_JSN_BATCH_ACCESS_DESC') . '">'
			. JText::_('COM_JSN_BATCH_ACCESS')
			. '</label>'
			. JHtml::_(
				'access.assetgrouplist',
				'batch[assetgroup_id]', '',
				'class="inputbox"',
				array(
					'title' => JText::_('JNONE'),
					'id' => 'batch-access'
				)
			);
	}
	
	public static function accessview()
	{
		JHtml::_('bootstrap.tooltip', '.modalTooltip', array('container' => '.modal-body'));

		// Create the batch selector to change an access level on a selection list.
		return
			'<label id="batch-access-lbl" for="batch-access" class="modalTooltip" '
			. 'title="' . JHtml::tooltipText('COM_JSN_BATCH_ACCESSVIEW', 'COM_JSN_BATCH_ACCESSVIEW_DESC') . '">'
			. JText::_('COM_JSN_BATCH_ACCESSVIEW')
			. '</label>'
			. JHtml::_(
				'access.assetgrouplist',
				'batch[assetgroupview_id]', '',
				'class="inputbox"',
				array(
					'title' => JText::_('JNONE'),
					'id' => 'batch-accessview'
				)
			);
	}
	
	public static function fieldgroup()
	{
		JHtml::_('bootstrap.tooltip', '.modalTooltip', array('container' => '.modal-body'));

		// Create the batch selector to change an access level on a selection list.
		$dataArray=array('0'=>JText::_('JNONE'));
		$db=JFactory::getDbo();
		$query=$db->getQuery(true);
		$query->select('id, title')->from('#__jsn_fields')->where('level=1 AND published=1');
		$db->setQuery($query);
		$results=$db->loadAssocList('id', 'title');
		foreach($results as $k => $v)
		{
			$dataArray[$k]=JText::_($v);
		}
		return
			'<label id="batch-access-lbl" for="batch-access" class="modalTooltip" '
			. 'title="' . JHtml::tooltipText('COM_JSN_BATCH_FIELDGROUP', 'COM_JSN_BATCH_FIELDGROUP_DESC') . '">'
			. JText::_('COM_JSN_BATCH_FIELDGROUP')
			. '</label>'
			. JHtml::_(
				'select.genericlist',$dataArray,
				'batch[fieldgroup_id]',
				array(
					'id' => 'batch-fieldgroup'
				)
			);
	}

	
}
