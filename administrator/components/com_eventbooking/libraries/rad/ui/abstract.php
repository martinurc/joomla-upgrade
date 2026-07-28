<?php
/**
 * @package     MPF
 * @subpackage  UI
 *
 * @copyright   Copyright (C) 2016 - 2018 Ossolution Team, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

abstract class RADUiAbstract implements RADUiInterface
{
	/**
	 * Css class map array
	 *
	 * @var array
	 */
	protected $classMaps;

	/**
	 * Framework own css classes
	 *
	 * @var array
	 */
	protected $frameworkClasses = [];

	/**
	 * Method to add a new class to class mapping
	 *
	 * @param $class
	 * @param $mappedClass
	 *
	 * @return $this
	 */
	public function addClassMapping($class, $mappedClass)
	{
		$class       = trim($class);
		$mappedClass = trim($mappedClass);

		$this->classMaps[$class] = $mappedClass;

		return $this;
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
		$class = trim($class);

		// Direct css class mapping
		if (isset($this->classMaps[$class]))
		{
			return $this->classMaps[$class];
		}

		// Handle multiple css classes, separated by space character
		if (strpos($class, ' ') !== false)
		{
			$classes       = explode(' ', $class);
			$mappedClasses = [];

			foreach ($classes as $cssClass)
			{
				if (isset($this->classMaps[$cssClass]))
				{
					// Special case for btn class in class group such as btn btn-primary
					if ($cssClass == 'btn')
					{
						$mappedClass     = $this->classMaps[$cssClass];
						$mappedClasses[] = explode(' ', $mappedClass)[0];
					}
					else
					{
						$mappedClasses[] = $this->classMaps[$cssClass];
					}
				}
				else
				{
					$mappedClasses[] = $cssClass;
				}
			}

			return implode(' ', $mappedClasses);
		}

		// Use font-awesome class for icons
		if (strpos($class, 'icon-') !== false)
		{
			$icon = substr($class, 5);

			return 'fa fa-' . $icon;
		}

		return $class;
	}

	/**
	 * Get framework own css class
	 *
	 * @param   string  $class
	 * @param   int     $behavior
	 *
	 * @return string
	 */
	public function getFrameworkClass($class, $behavior = 0)
	{
		if (!in_array($class, $this->frameworkClasses))
		{
			return;
		}

		switch ($behavior)
		{
			case 1:
				return ' ' . $class;
			case 2:

				return $class . ' ';
			case 3:
				return ' class="' . $class . '"';
			default:
				return $class;
		}
	}
}
