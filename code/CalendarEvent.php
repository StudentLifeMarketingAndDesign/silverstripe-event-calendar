<?php

namespace SLC\Calendar;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataList;

class CalendarEvent extends \Page {
	private static $table_name = 'CalendarEvent';
	private static $db = array(
		'Location' => 'Text',
		'isOnline' => 'Boolean',
		'OnlineLocationUrl' => 'Text',
	);
	private static $icon_class = 'font-icon-p-event-alt';

	private static $has_many = array(
		'DateTimes' => CalendarDateTime::class,
	);

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', new TextField('Location', 'Location'), 'Content');
		$fields->addFieldToTab('Root.Main', new CheckboxField('isOnline', 'Is Virtual Event?'), 'Content');
		$fields->addFieldToTab('Root.Main', new TextField('OnlineLocationUrl', 'Virtual Event Link'), 'Content');
		$dateTimesConf = GridFieldConfig_RelationEditor::create();
		$dateTimesField = new GridField('DateTimes', 'Dates and Times', $this->DateTimes(), $dateTimesConf);

		$fields->addFieldToTab('Root.DatesAndTimes', $dateTimesField);
		return $fields;
	}

	public function getFirstStartDate() {
		$dateTime = $this->DateTimes()->sort('StartDate')->First();
		return $dateTime->StartDate;
	}
	public function getFirstEndDate() {
		$dateTime = $this->DateTimes()->sort('StartDate')->First();
		return $dateTime->EndDate;
	}
	public function CurrentDate() {
		$allDates = DataList::create(CalendarDateTime::class)
			->filter("EventID", $this->ID)
			->sort("\"StartDate\" ASC");
		if (!isset($_REQUEST['date'])) {
			// If no date filter specified, return the first one
			return $allDates->first();
		} elseif (strtotime($_REQUEST['date']) > 0) {
			$date = date('Y-m-d', strtotime($_REQUEST['date']));
			if ($this->Recursion) {
				$datetime = $allDates->first();
				if ($datetime) {
					$datetime->StartDate = $date;
					$datetime->EndDate = $date;
					return $datetime;
				}
			}
			return $allDates
				->filter("StartDate", $date)
				->first();
		}
	}
}
