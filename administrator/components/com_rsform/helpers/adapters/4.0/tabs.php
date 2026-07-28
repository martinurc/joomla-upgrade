<?php
/**
 * @package    RSForm! Pro
 * @copyright  (c) 2009 - 2020 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

class RSFormProAdapterTabs
{
	protected $id;
	protected $titles   = array();
	protected $contents = array();
	
	public function __construct($id)
	{
		$this->id = preg_replace('/[^A-Z0-9_\. -]/i', '', $id);
	}
	
	public function addTitle($label, $id)
	{
		$this->titles[] = (object) array('label' => $label, 'id' => $id);
	}
	
	public function addContent($content)
	{
		$this->contents[] = $content;
	}
	
	public function render()
	{
		$active = reset($this->titles);

		echo HTMLHelper::_('uitab.startTabSet', $this->id, array('active' => $active->id, 'recall' => true));

		foreach ($this->titles as $i => $title)
		{
			echo HTMLHelper::_('uitab.addTab', $this->id, $title->id, Text::_($title->label));
			echo $this->contents[$i];
			echo HTMLHelper::_('uitab.endTab');
		}

		echo HTMLHelper::_('uitab.endTabSet');
	}
}