<?php

namespace SLC\Calendar;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;

class Calendar extends \Page {

	private static $table_name = 'Calendar';

	private static $allowed_children = array(
		'SLC\Calendar\CalendarEvent',
	);

	private static $icon_class = 'font-icon-calendar';

	private static $timezone = "America/Chicago";
	private static $language = "EN";

	public function getEventList($start, $end, $filter = null, $limit = null, $announcement_filter = null) {
		$children = $this->AllChildren();
		$ids = $children->column('ID');
		$datetimeClass = CalendarDateTime::class;
		$relation = 'EventID';
		$eventClass = 'CalendarEvent';
		// $datetimeClass = $this->getDateTimeClass();
		// $relation = $this->getDateToEventRelation();
		// $eventClass = $this->getEventClass();
		$list = DataList::create($datetimeClass)
			->filter(array(
				$relation => $ids,
			))
			->innerJoin($eventClass, "$relation = \"{$eventClass}\".\"ID\"")
			->innerJoin("SiteTree", "\"SiteTree\".\"ID\" = \"{$eventClass}\".\"ID\"");
		if ($start && $end) {
			$list = $list->where("
							(StartDate <= '$start' AND EndDate >= '$end') OR
							(StartDate BETWEEN '$start' AND '$end') OR
							(EndDate BETWEEN '$start' AND '$end')
							");
		} else if ($start) {
			$list = $list->where("(StartDate >= '$start' OR EndDate > '$start')");
		} else if ($end) {
			$list = $list->where("(EndDate <= '$end' OR StartDate < '$end')");
		}
		if ($filter) {
			$list = $list->where($filter);
		}
		return $list;
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		// $grid = $fields->dataFieldByName('ChildPages');
		// $config = $grid->getConfig();
		// $dataColumns = $config->getComponentByType(GridFieldDataColumns::class);

		// $dataColumns->setDisplayFields([
		// 	'Title' => 'Title',
		// 	'Created' => 'Created',
		// ]);

		// $list = $grid->getList();

		// $sortedList = $list->sort('Created DESC');

		// $grid->setList($sortedList);
		// $grid->setTitle('Events');
		// $grid->setName('Events');

		// $fields->addFieldToTab('Root.Main', $grid);

		// $fields->removeByName("ChildPages");
		// $fields->removeByName("Credit");

		// $contentField = $fields->dataFieldByName('Content');
		// $contentField->setRows(3);
		// $fields->removeByName("Content");

		return $fields;
	}

	// public function getLumberjackPagesForGridfield($excluded = array()){
	//        return CalendarEvent::get()->filter([
	//            'ParentID' => $this->owner->ID,
	//            'ClassName' => $excluded,
	//        ])->sort('Created DESC');

	// }

	public function EventsToday() {
		$calendar = $this;
		$today = sfDate::getInstance()->date();
		$events = $calendar->getEventList($today, $today);
		return $events;
	}

	public function AllEvents() {
		$calendar = $this;
		$events = new ArrayList();
		$events = $calendar->getEventList('1900-01-01', '3000-01-01');

		return $events;
	}

	public function UpcomingEvents() {
		$calendar = $this;
		$events = new ArrayList();
		$start = date('Y-m-d', time());
		$events = $calendar->getEventList($start, '3000-01-01');

		return $events;

	}

	public function AllDates() {
		// $dates = CalendarDateTime::get();
		// $datesArray = $dates->toArray();

		// $datesArrayList = new ArrayList($datesArray);
		// $datesArrayList->removeDuplicates('StartDate');
		$calendar = $this->owner;
		$datesArrayList = new ArrayList();

		$dates = $calendar->getEventList('1900-01-01', '3000-01-01');
		foreach ($dates as $date) {
			$datesArrayList->push($date);
		}
		$datesArrayList->removeDuplicates('StartDate');
		return $datesArrayList;
	}
}
