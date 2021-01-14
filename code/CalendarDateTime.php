<?php

namespace SLC\Calendar;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SLC\Calendar\CalendarUtil;

class CalendarDateTime extends DataObject {
	private static $table_name = 'CalendarDateTime';
	private static $db = array(
		'StartDate' => 'Date',
		'StartTime' => 'Time',
		'EndDate' => 'Date',
		'EndTime' => 'Time',
		'AllDay' => 'Boolean',
	);

	private static $casting = array(
		'DateRange' => 'HTMLText',
		'TimeRange' => 'HTMLText',
	);

	private static $has_one = array(
		'Event' => CalendarEvent::class,
	);

	private static $summary_fields = array(
		'StartDate.Nice',
		'StartTime',
		'EndDate',
		'EndTime',
		'AllDay',
	);

	private static $default_sort = "StartDate ASC, StartTime ASC";

	public function getTitle() {
		return $this->Event()->Title;
	}

	public function getDateRange() {
		list($strStartDate, $strEndDate) = CalendarUtil::get_date_string($this->StartDate, $this->EndDate);

		$html = "<span class='dtstart' title='" . $this->MicroformatStart() . "'>" . $strStartDate . "</span>";
		$html .= ($strEndDate != "") ? "-" : "";
		$html .= "<span class='dtend' title='" . $this->MicroformatEnd() . "'>";
		$html .= ($strEndDate != "") ? $strEndDate : "";
		$html .= "</span>";

		return $html;
	}

	public function getTimeRange() {
		$ret = $this->obj('StartTime')->Format("h:mm a");
		$ret .= $this->EndTime ? " &mdash; " . $this->obj('EndTime')->Format("h:mm a") : "";
		return $ret;
	}

	public function MicroformatStart($offset = true) {
		if (!$this->StartDate) {
			return "";
		}

		$date = $this->StartDate;

		if ($this->AllDay) {
			$time = "00:00:00";
		} else {
			$time = $this->StartTime ? $this->StartTime : "00:00:00";
		}

		return CalendarUtil::microformat($date, $time, self::config()->offset);
	}

	public function MicroformatEnd($offset = true) {
		if ($this->AllDay && $this->StartDate) {
			$time = "00:00:00";
			$end = sfDate::getInstance($this->StartDate);
			$date = $end->tomorrow()->date();
			unset($end);
		} else {
			$date = $this->EndDate ? $this->EndDate : $this->StartDate;
			$time = $this->EndTime && $this->StartTime ? $this->EndTime : (!$this->EndTime && $this->StartTime ? $this->StartTime : "00:00:00");
		}

		return CalendarUtil::microformat($date, $time, self::config()->offset);
	}
	public function ICSLink() {
		$ics_start = $this->obj('StartDate')->Format('YMMdd') . "T" . $this->obj('StartTime')->Format('HHmmss');
		if ($this->EndDate) {
			$ics_end = $this->obj('EndDate')->Format('YMMdd') . "T" . $this->obj('EndTime')->Format('HHmmss');
		} else {
			$ics_end = $ics_start;
		}
		if ($this->Feed) {
			return Controller::join_links(
				$this->Calendar()->Link(),
				"ics",
				$this->ID,
				$ics_start . "-" . $ics_end,
				"?title=" . urlencode($this->Title)
			);
		}

		return Controller::join_links(
			$this->Event()->Parent()->Link(),
			"ics",
			$this->Event()->ID,
			$ics_start . "-" . $ics_end
		);
	}
	public function Link() {
		return Controller::join_links($this->Event()->Link(), "?date=" . $this->StartDate);
	}
}
