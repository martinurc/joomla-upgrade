<?php
/**
* @copyright	Copyright (C) 2013 Jsn Project company. All rights reserved.
* @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
* @package		Easy Profile
* website		www.easy-profile.com
* Technical Support : Forum -	http://www.easy-profile.com/support.html
*/

defined('_JEXEC') or die;


abstract class JHtmlField
{

	public static function required($value, $i, $enabled = true, $checkbox = 'cb')
	{
		$states	= array(
			2	=> array(
				'required_unpublish',
				'',
				'',
				'',
				false,
				'publish',
				'publish'
			),
			1	=> array(
				'required_unpublish',
				'',
				'',
				'',
				false,
				'publish icon-star',
				'publish'
			),
			0	=> array(
				'required_publish',
				'',
				'',
				'',
				false,
				'unpublish',
				'unpublish'
			),
		);

		return JHtml::_('jgrid.state', $states, $value, $i, 'fields.', $enabled, true, $checkbox);
	}
	
	public static function core($value, $i, $enabled = false, $checkbox = 'cb')
	{
		$states	= array(
			1	=> array(
				'',
				'',
				'',
				'',
				false,
				'publish',
				'publish'
			),
			0	=> array(
				'',
				'',
				'',
				'',
				false,
				'unpublish',
				'unpublish'
			),
		);

		return JHtml::_('jgrid.state', $states, $value, $i, 'fields.', $enabled, true, $checkbox);
	}
	
	public static function profile($value, $i, $enabled = true, $checkbox = 'cb')
	{
		$states	= array(
			1	=> array(
				'profile_unpublish',
				'',
				'',
				'',
				false,
				'publish',
				'publish'
			),
			0	=> array(
				'profile_publish',
				'',
				'',
				'',
				false,
				'unpublish',
				'unpublish'
			),
		);

		return JHtml::_('jgrid.state', $states, $value, $i, 'fields.', $enabled, true, $checkbox);
	}
	
	public static function edit($value, $i, $enabled = true, $checkbox = 'cb')
	{
		$states	= array(
			1	=> array(
				'edit_unpublish',
				'',
				'',
				'',
				false,
				'publish',
				'publish'
			),
			0	=> array(
				'edit_publish',
				'',
				'',
				'',
				false,
				'unpublish',
				'unpublish'
			),
		);

		return JHtml::_('jgrid.state', $states, $value, $i, 'fields.', $enabled, true, $checkbox);
	}

	public static function editbackend($value, $i, $enabled = true, $checkbox = 'cb')
	{
		$states	= array(
			1	=> array(
				'editbackend_unpublish',
				'',
				'',
				'',
				false,
				'publish',
				'publish'
			),
			0	=> array(
				'editbackend_publish',
				'',
				'',
				'',
				false,
				'unpublish',
				'unpublish'
			),
		);

		return JHtml::_('jgrid.state', $states, $value, $i, 'fields.', $enabled, true, $checkbox);
	}
	
	public static function register($value, $i, $enabled = true, $checkbox = 'cb')
	{
		$states	= array(
			1	=> array(
				'register_unpublish',
				'',
				'',
				'',
				false,
				'publish',
				'publish'
			),
			0	=> array(
				'register_publish',
				'',
				'',
				'',
				false,
				'unpublish',
				'unpublish'
			),
		);

		return JHtml::_('jgrid.state', $states, $value, $i, 'fields.', $enabled, true, $checkbox);
	}
	
	public static function search($value, $i, $enabled = true, $checkbox = 'cb')
	{
		$states	= array(
			1	=> array(
				'search_unpublish',
				'',
				'',
				'',
				false,
				'publish',
				'publish'
			),
			0	=> array(
				'search_publish',
				'',
				'',
				'',
				false,
				'unpublish',
				'unpublish'
			),
		);

		return JHtml::_('jgrid.state', $states, $value, $i, 'fields.', $enabled, true, $checkbox);
	}

}
