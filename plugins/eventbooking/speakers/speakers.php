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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Utilities\ArrayHelper;

class plgEventBookingSpeakers extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 */
	protected $app;

	/**
	 * Database object.
	 *
	 * @var    JDatabaseDriver
	 */
	protected $db;

	/**
	 * Render setting form
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return array
	 */
	public function onEditEvent($row)
	{
		if (!$this->canRun($row))
		{
			return;
		}

		return [
			'title' => Text::_('EB_SPEAKERS'),
			'form'  => $this->drawSettingForm($row),
		];
	}

	/**
	 * Store setting into database, in this case, use params field of plans table
	 *
	 * @param   EventbookingTableEvent  $row
	 * @param   bool                    $isNew  true if create new plan, false if edit
	 */
	public function onAfterSaveEvent($row, $data, $isNew)
	{
		if (!$this->canRun($row))
		{
			return;
		}

		$db    = $this->db;
		$query = $db->getQuery(true);

		$speakers = isset($data['speakers']) && is_array($data['speakers']) ? $data['speakers'] : [];

		$speakerIds = [];
		$ordering   = 1;

		foreach ($speakers as $speaker)
		{
			$rowSpeaker = new EventbookingTableSpeaker($this->db);
			$rowSpeaker->bind($speaker);

			// Prevent speaker data being moved to new event on saveAsCopy
			if ($isNew)
			{
				$rowSpeaker->id = 0;
			}

			$rowSpeaker->event_id = $row->id;
			$rowSpeaker->ordering = $ordering++;
			$rowSpeaker->store();
			$speakerIds[] = $rowSpeaker->id;
		}

		if (!$isNew)
		{
			$query->delete('#__eb_speakers')
				->where('event_id = ' . $row->id);

			if (count($speakerIds))
			{
				$query->where('id NOT IN (' . implode(',', $speakerIds) . ')');
			}

			$db->setQuery($query)
				->execute();

			$query->clear()
				->delete('#__eb_event_speakers')
				->where('event_id = ' . $row->id);
			$db->setQuery($query);
			$db->execute();
		}

		if (!empty($data['existing_speaker_ids']))
		{
			$speakerIds = array_filter(ArrayHelper::toInteger($data['existing_speaker_ids']));

			if (count($speakerIds))
			{
				$query->clear()
					->insert('#__eb_event_speakers')
					->columns($db->quoteName(['event_id', 'speaker_id']));

				foreach ($speakerIds as $speakerId)
				{
					$query->values(implode(',', [$row->id, $speakerId]));
				}

				$db->setQuery($query)
					->execute();
			}
		}

		// Insert event speakers into #__eb_event_speakers table
		$sql = 'INSERT INTO #__eb_event_speakers(event_id, speaker_id) SELECT event_id, id FROM #__eb_speakers WHERE event_id = ' . $row->id . ' ORDER BY ordering';
		$db->setQuery($sql)
			->execute();
	}

	/**
	 * Display form allows users to change settings on subscription plan add/edit screen
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return string
	 */
	private function drawSettingForm($row)
	{
		$xml = simplexml_load_file(JPATH_ROOT . '/plugins/eventbooking/speakers/form/speaker.xml');

		if ($this->params->get('use_editor_for_description', 0))
		{
			foreach ($xml->field->form->children() as $field)
			{
				if ($field->attributes()->name == 'description')
				{
					$field->attributes()->type = 'editor';
				}
			}
		}

		$xml->field->attributes()->layout = $this->params->get('subform_layout', 'joomla.form.field.subform.repeatable-table');

		$form                 = Form::getInstance('speakers', $xml->asXML());
		$formData['speakers'] = [];
		$selectedSpeakerIds   = [];

		$db    = $this->db;
		$query = $db->getQuery(true);

		// Load existing speakers for this event
		if ($row->id)
		{
			$query->select('*')
				->from('#__eb_speakers')
				->where('event_id = ' . $row->id)
				->order('ordering');
			$db->setQuery($query);

			foreach ($db->loadObjectList() as $speaker)
			{
				$formData['speakers'][] = [
					'id'          => $speaker->id,
					'name'        => $speaker->name,
					'title'       => $speaker->title,
					'avatar'      => $speaker->avatar,
					'description' => $speaker->description,
					'url'         => $speaker->url,
				];
			}

			$query->clear()
				->select('speaker_id')
				->from('#__eb_event_speakers')
				->where('event_id = ' . (int) $row->id);
			$db->setQuery($query);
			$selectedSpeakerIds = $db->loadColumn();
		}

		// Get existing speakers for selection
		$query->clear()
			->select('id, name')
			->from('#__eb_speakers')
			->order('ordering');

		if ($row->id)
		{
			$query->where('event_id != ' . $row->id);
		}

		$db->setQuery($query);
		$existingSpeakers = $db->loadObjectList();

		// Trigger content plugin
		PluginHelper::importPlugin('content');
		Factory::getApplication()->triggerEvent('onContentPrepareForm', [$form, $formData]);

		$form->bind($formData);

		$layoutData = [
			'existingSpeakers'   => $existingSpeakers,
			'selectedSpeakerIds' => $selectedSpeakerIds,
			'form'               => $form,
		];

		return EventbookingHelperHtml::loadCommonLayout('plugins/speakers_form.php', $layoutData);
	}

	/**
	 * Display event speakers
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return array|void
	 */
	public function onEventDisplay($row)
	{
		if ($this->params->get('enable_setup_speakers_for_child_event'))
		{
			$eventId = $row->id;
		}
		else
		{
			$eventId = $row->parent_id ?: $row->id;
		}

		$db      = $this->db;
		$query   = $db->getQuery(true)
			->select('a.*')
			->from('#__eb_speakers AS a')
			->innerJoin('#__eb_event_speakers AS b ON a.id = b.speaker_id')
			->where('b.event_id = ' . $eventId);

		if ($fieldSuffix = EventbookingHelper::getFieldSuffix())
		{
			EventbookingHelperDatabase::getMultilingualFieldsUseDefaultLanguageData(
				$query,
				['a.name', 'a.title', 'a.url', 'a.description'],
				$fieldSuffix
			);
		}

		if ($this->params->get('order_speakers_by_name'))
		{
			$query->order('a.name');
		}
		else
		{
			$query->order('b.id');
		}

		$db->setQuery($query);
		$speakers = $db->loadObjectList();

		if (empty($speakers))
		{
			return;
		}

		$layoutFile = $this->params->get('layout', 'speakers') . '.php';

		return [
			'title'    => Text::_('EB_EVENT_SPEAKERS'),
			'form'     => EventbookingHelperHtml::loadCommonLayout('plugins/' . $layoutFile, ['speakers' => $speakers, 'params'   => $this->params]),
			'position' => $this->params->get('output_position', 'before_register_buttons'),
			'name'     => $this->_name,
		];
	}

	/**
	 * Method to check to see whether the plugin should run
	 *
	 * @param   EventbookingTableEvent  $row
	 *
	 * @return bool
	 */
	private function canRun($row)
	{
		if ($this->app->isClient('site') && !$this->params->get('show_on_frontend'))
		{
			return false;
		}

		if ($row->parent_id > 0 && !$this->params->get('enable_setup_speakers_for_child_event'))
		{
			return false;
		}

		return true;
	}
}
