<?php
/**
* @package RSForm! Pro
* @copyright (C) 2007-2019 www.rsjoomla.com
* @license GPL, http://www.gnu.org/copyleft/gpl.html
*/

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Filter\OutputFilter;

class RsformViewDirectory extends HtmlView
{
	public function display($tpl = null)
	{
		$this->app			= Factory::getApplication();
		$this->doc			= Factory::getDocument();
		$this->params		= $this->app->getParams('com_rsform');
		$this->layout		= $this->getLayout();
		$this->directory	= $this->get('Directory');
		$this->tooltipClass = RSFormProHelper::getTooltipClass();
		$this->url          = Uri::getInstance();
		$this->formId       = $this->params->get('formId');

        HTMLHelper::_('script', 'com_rsform/directory.js', array('relative' => true, 'version' => 'auto'));

		$nonce = '';
		if (PluginHelper::isEnabled('system', 'httpheaders'))
		{
			$app    = Factory::getApplication();
			$plugin = PluginHelper::getPlugin('system', 'httpheaders');
			$params = new Registry();
			$params->loadString($plugin->params);

			$cspEnabled          = (int) $params->get('contentsecuritypolicy', 0);
			$cspClient           = (string) $params->get('contentsecuritypolicy_client', 'site');
			$nonceEnabled        = (int) $params->get('nonce_enabled', 0);

			if ($cspEnabled && ($app->isClient($cspClient) || $cspClient === 'both'))
			{
				if ($nonceEnabled)
				{
					$nonce = Factory::getApplication()->get('csp_nonce');
				}
			}
		}
		
		if ($this->layout == 'view')
		{
            HTMLHelper::_('stylesheet', 'com_rsform/directory.css', array('relative' => true, 'version' => 'auto'));
			
			$this->template  = $this->get('template');
            $this->id		 = $this->app->input->getInt('id',0);
			$this->canEdit	 = RSFormProHelper::canEdit($this->formId, $this->id);
            $this->canDelete = RSFormProHelper::canDelete($this->formId, $this->id);
			
			// Add custom CSS and JS
			if ($this->directory->JS)
			{
				if (strpos($this->directory->JS, '{nonce}') !== false)
				{
					$this->directory->JS = str_replace('{nonce}', $nonce, $this->directory->JS);
				}
				$this->doc->addCustomTag($this->directory->JS);
			}

			if ($this->directory->CSS)
			{
				if (strpos($this->directory->CSS, '{nonce}') !== false)
				{
					$this->directory->CSS = str_replace('{nonce}', $nonce, $this->directory->CSS);
				}
				$this->doc->addCustomTag($this->directory->CSS);
			}
			
			// Add pathway
			$this->app->getPathway()->addItem(Text::_('RSFP_SUBM_DIR_VIEW'), '');
		}
		elseif ($this->layout == 'edit')
		{
			if (RSFormProHelper::canEdit($this->formId, $this->app->input->getInt('id',0)))
			{
                HTMLHelper::_('stylesheet', 'com_rsform/directory.css', array('relative' => true, 'version' => 'auto'));
				$this->fields		= $this->get('EditFields');
			}
			else
			{
				$this->app->enqueueMessage(Text::_('COM_RSFORM_SUBMISSIONS_DIRECTORY_CANNOT_EDIT'), 'error');
				$this->app->redirect(Route::_('index.php?option=com_rsform&view=directory', false));
			}
			
			// Add pathway
			$this->app->getPathway()->addItem(Text::_('RSFP_SUBM_DIR_EDIT'), '');
		}
		else
		{
			$this->search              = $this->get('Search');
			$this->items               = $this->get('Items');
			$this->uploadFields        = $this->get('uploadFields');
			$this->multipleFields      = $this->get('multipleFields');
			$this->additionalUnescaped = $this->get('additionalUnescaped');
			$this->unescapedFields     = array_merge($this->uploadFields, $this->multipleFields, $this->additionalUnescaped);
			$this->fields              = $this->get('Fields');
			$this->headers             = RSFormProHelper::getDirectoryStaticHeaders();
			$this->hasDetailFields     = $this->hasDetailFields();
			$this->hasSearchFields     = $this->hasSearchFields();
			$this->viewableFields      = $this->getViewableFields();
			$this->dynamicFilters      = $this->params->get('dynamic_filter_values', array());
			$this->dynamicSearch       = $this->get('dynamicFilters');
			$this->pagination          = $this->get('Pagination');

			$this->filter_search    = $this->get('Search');
			$this->filter_order     = $this->get('ListOrder');
			$this->filter_order_Dir = $this->get('ListDirn');

			if ($this->directory->AllowCSVFullDownload)
			{
				$this->limit = RSFormProHelper::getConfig('export.limit');
				$this->total = $this->get('Total');
			}

			$this->url->delVar('start');
			
			// Add custom CSS and JS
			if ($this->directory->JS)
			{
				if (strpos($this->directory->JS, '{nonce}') !== false)
				{
					$this->directory->JS = str_replace('{nonce}', $nonce, $this->directory->JS);
				}
				$this->doc->addCustomTag($this->directory->JS);
			}

			if ($this->directory->CSS)
			{
				if (strpos($this->directory->CSS, '{nonce}') !== false)
				{
					$this->directory->CSS = str_replace('{nonce}', $nonce, $this->directory->CSS);
				}
				$this->doc->addCustomTag($this->directory->CSS);
			}
		}
		
		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
		
		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}
		
		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}
		
		$title = $this->params->get('page_title', '');
		if (empty($title))
		{
			$title = Factory::getApplication()->get('sitename');
		}
		elseif (Factory::getApplication()->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', Factory::getApplication()->get('sitename'), $title);
		}
		elseif (Factory::getApplication()->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, Factory::getApplication()->get('sitename'));
		}
		
		$this->document->setTitle($title);
		
		parent::display($tpl);
	}

	protected function hasDetailFields()
	{
		foreach ($this->fields as $field)
		{
			if ($field->indetails)
			{
				return true;
			}
		}

		return false;
	}
	
	protected function hasSearchFields()
	{
		foreach ($this->fields as $field)
		{
			if ($field->searchable)
			{
				return true;
			}
		}

		return false;
	}
	
	protected function getViewableFields()
	{
		$return = array();
		
		foreach ($this->fields as $field)
		{
			if ($field->viewable)
			{
				$return[] = $field;
			}
		}
		
		return $return;
	}
	
	protected function getFilteredName($name)
	{
		return ucfirst(OutputFilter::stringURLSafe($name));
	}
	
	protected function getValue($item, $field)
	{
		if (in_array($field->FieldName, $this->unescapedFields))
		{
			return $item->{$field->FieldName};
		}
		else
		{
			// Static header?
			if ($field->componentId < 0 && isset($this->headers[$field->componentId]))
			{
				$header = $this->headers[$field->componentId];
				if (in_array($header, array('DateSubmitted', 'ConfirmedDate')))
				{
					$value = RSFormProHelper::getDate($item->{$header});
				}
				else
				{
					$value = $item->{$header};
				}
			}
			else
			{
				// Dynamic header.
				$value = $item->{$field->FieldName};
			}

			return $this->escape($value);
		}
	}
	
	public function pdfLink($id)
	{
		return Route::_('index.php?option=com_rsform&view=directory&layout=view&id=' . $id . '&format=pdf');
	}

	protected function getFieldComponentId($fieldName, $formId)
	{
		$headers = RSFormProHelper::getDirectoryStaticHeaders();

		$position = array_search($fieldName, $headers);
		if ($position !== false)
		{
			return $position;
		}

		return RSFormProHelper::getComponentId($fieldName, $formId);
	}
}