<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class RsformViewMappings extends HtmlView
{
	public function display( $tpl = null )
	{
        if (!Factory::getUser()->authorise('forms.manage', 'com_rsform'))
        {
            throw new Exception(Text::_('COM_RSFORM_NOT_AUTHORISED_TO_USE_THIS_SECTION'));
        }

        $this->form     = $this->get('Form');
		$this->formId   = Factory::getApplication()->input->getInt('formId');
		$this->fields   = $this->get('quickFields');
		$this->mapping 	= $this->get('mapping');
		$this->config 	= array(
			'connection' => $this->mapping->connection,
			'host' 		 => $this->mapping->host,
			'driver' 	 => !empty($this->mapping->driver) ? $this->mapping->driver : Factory::getApplication()->get('dbtype'),
			'port' 		 => $this->mapping->port,
			'username'   => $this->mapping->username,
			'password' 	 => $this->mapping->password,
			'database'   => $this->mapping->database,
			'table' 	 => $this->mapping->table
		);

		$displayPlaceholders = RSFormProHelper::generateQuickAddGlobal('display', true);
		foreach ($this->fields as $fields)
		{
			$displayPlaceholders = array_merge($displayPlaceholders, $fields['display']);
		}
		$displayPlaceholders[] = '{empty}';
		$displayPlaceholders[] = '{last_insert_id}';
		$count = $this->get('mappingsCount');
		if ($count > 0)
		{
			for ($i = 0; $i < $count; $i++)
			{
				$displayPlaceholders[] = '{last_insert_id_' . ($i + 1) . '}';
			}
		}


		$this->document->addScriptDeclaration('RSFormPro.Placeholders = ' . json_encode(array_values($displayPlaceholders)) . ';');
		
		parent::display($tpl);
	}
}