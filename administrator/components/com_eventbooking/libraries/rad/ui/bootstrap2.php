<?php
/**
 * @package     MPF
 * @subpackage  UI
 *
 * @copyright   Copyright (C) 2016 - 2018 Ossolution Team, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/**
 * Base class for a Joomla Administrator Controller. It handles add, edit, delete, publish, unpublish records....
 *
 * @package       MPF
 * @subpackage    UI
 * @since         2.0
 */
class RADUiBootstrap2 extends RADUiAbstract implements RADUiInterface
{
	/**
	 * Constructor
	 *
	 * @param   array  $classMaps
	 */
	public function __construct($classMaps = [])
	{
		if (empty($classMaps))
		{
			$classMaps = [
				// Grid
				'row-fluid'       => 'row-fluid',
				'span1'           => 'span1',
				'span2'           => 'span2',
				'span3'           => 'span3',
				'span4'           => 'span4',
				'span5'           => 'span5',
				'span6'           => 'span6',
				'span7'           => 'span7',
				'span8'           => 'span8',
				'span9'           => 'span9',
				'span10'          => 'span10',
				'span11'          => 'span11',
				'span12'          => 'span12',
				// Form classes
				'form'            => 'form',
				'form-horizontal' => 'form-horizontal',
				'control-group'   => 'control-group',
				'control-label'   => 'control-label',
				'controls'        => 'controls eb-form-control',
				// Button classes
				'btn'             => 'btn',
				'btn-primary'     => 'btn-primary',
				'btn-info'        => 'btn-info',
				'btn-success'     => 'btn-success',
				'btn-warning'     => 'btn-warning',
				'btn-danger'      => 'btn-danger',
				'btn-inverse'     => 'btn-inverse',
				'btn-link'        => 'btn-link',
				// Image classes
				'thumbnail'       => 'thumbnail',
				'img-polaroid'    => 'img-polaroid',
				'img-rounded'     => 'img-rounded',
				'img-circle'      => 'img-circle',
				'img-responsive'  => 'img-responsive-bs2',
				// Table classes
				'table'           => 'table',
				'table-striped'   => 'table-striped',
				'table-bordered'  => 'table-bordered',
				'table-condensed' => 'table-condensed',
				'table-hover'     => 'table-hover',
				// Badge classes
				'badge'           => 'badge',
				'badge-success'   => 'badge-success',
				'badge-warning'   => 'badge-warning',
				'badge-info'      => 'badge-info',
				'badge-danger'    => 'badge-important',
				// Text classes
				'text-muted'      => 'muted',
				'text-warning'    => 'text-warning',
				'text-error'      => 'text-error',
				'text-info'       => 'text-info',
				'text-success'    => 'text-success',
				// Text Alignment
				'text-left'       => 'text-left',
				'text-center'     => 'text-center',
				'text-right'      => 'text-right',
				// Form input sizes
				'input-mini'      => 'input-mini',
				'input-small'     => 'input-small',
				'input-medium'    => 'input-medium',
				'input-large'     => 'input-large',
				'input-xlarge'    => 'input-xlarge',
				'input-xxlarge'   => 'input-xxlarge',
				// Button sizes
				'btn-mini'        => 'btn-mini',
				'btn-small'       => 'btn-small',
				'btn-large'       => 'btn-large',
				// Responsive utilities
				'visible-phone'   => 'visible-phone',
				'visible-tablet'  => 'visible-tablet',
				'visible-desktop' => 'visible-desktop',
				'hidden-phone'    => 'hidden-phone',
				'hidden-tablet'   => 'hidden-tablet',
				'hidden-desktop'  => 'hidden-desktop',
				// Utility classes
				'pull-left'       => 'pull-left',
				'pull-right'      => 'pull-right',
				'clearfix'        => 'clearfix',
				'input-prepend'   => 'input-prepend',
				'input-append'    => 'input-append',
				'add-on'          => 'add-on',
				'nav'             => 'nav',
				'nav-pills'       => 'nav-pills',
				'nav-stacked'     => 'nav-stacked',
				'nav-tabs'        => 'nav-tabs',
				// Background Color classes
				'bg-primary'      => 'btn-primary',
			];
		}

		$this->classMaps = $classMaps;
	}

	/**
	 * Get the mapping of a given class
	 *
	 * @param   string  $class  The input class
	 *
	 * @return string The mapped class
	 */
	public function getClassMapping($class)
	{
		if (Factory::getApplication()->isClient('administrator') && strpos($class, 'icon-') !== false)
		{
			return $class;
		}

		return parent::getClassMapping($class);
	}

	/**
	 * Method to render input with prepend add-on
	 *
	 * @param   string  $input
	 * @param   string  $addOn
	 *
	 * @return mixed
	 */
	public function getPrependAddon($input, $addOn)
	{
		$html   = [];
		$html[] = '<div class="input-prepend">';
		$html[] = '<span class="add-on">' . $addOn . '</span>';
		$html[] = $input;
		$html[] = '</div>';

		return implode("\n", $html);
	}

	/**
	 * Method to render input with append add-on
	 *
	 * @param   string  $input
	 * @param   string  $addOn
	 *
	 * @return string
	 */
	public function getAppendAddon($input, $addOn)
	{
		$html   = [];
		$html[] = '<div class="input-append">';
		$html[] = $input;
		$html[] = '<span class="add-on">' . $addOn . '</span>';
		$html[] = '</div>';

		return implode("\n", $html);
	}
}
