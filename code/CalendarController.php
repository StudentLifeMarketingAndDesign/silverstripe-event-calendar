<?php

namespace SLC\Calendar;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DataObject;
use SLC\Calendar\CalendarUtil;

class CalendarController extends \PageController {

	private static $allowed_actions = array(
		// 'year'
		'show',
		'ics',
	);

	private static $url_handlers = array(
		'show',
		// // 'year//$Year' => 'year',
		// 'ics' => 'ics'
	);

	public function show(HTTPRequest $r) {
		$this->parseURL($r);
		return $this->respond();
	}
	public function respond() {
		if (Director::is_ajax()) {
			return $this->renderWith('EventList');
		}
		return array();
	}
	public function DateHeader() {
		switch ($this->view) {
		case "day":
			return CalendarUtil::localize($this->startDate->get(), null, CalendarUtil::ONE_DAY_HEADER);
			break;

		case "month":
			return CalendarUtil::localize($this->startDate->get(), null, CalendarUtil::MONTH_HEADER);
			break;

		case "year":
			return CalendarUtil::localize($this->startDate->get(), null, CalendarUtil::YEAR_HEADER);
			break;

		case "range":
		case "week":
		case "weekend":
			list($strStartDate, $strEndDate) = CalendarUtil::get_date_string($this->startDate->date(), $this->endDate->date());
			return $strStartDate . $strEndDate;
			break;

		default:
			return $this->DefaultDateHeader;
			break;
		}
	}
	public function parseURL(HTTPRequest $r) {
		if (!$r->param('ID')) {
			return;
		}

		$this->startDate = sfDate::getInstance(CalendarUtil::get_date_from_string($r->param('ID')));

		if ($r->param('OtherID')) {
			$this->view = "range";
			$this->endDate = sfDate::getInstance(CalendarUtil::get_date_from_string($r->param('OtherID')));
		} else {
			$d = clone $this->startDate;
			switch (strlen(str_replace("-", "", $r->param('ID')))) {
			case 8:

				$this->view = "day";
				$this->endDate = sfDate::getInstance($d->get() + 1);
				//print_r($this->endDate);
				break;

			case 6:
				$this->view = "month";
				$this->endDate = sfDate::getInstance($d->finalDayOfMonth()->date());
				break;

			case 4:
				$this->view = "year";
				$this->endDate = sfDate::getInstance($d->finalDayOfYear()->date());
				break;

			default:
				$this->view = "default";
				$this->endDate = sfDate::getInstance($d->addMonth($this->DefaultFutureMonths)->date());
				break;
			}
		}
	}

	public function Events() {
		$event_filter = null;
		$announcement_filter = null;
		$endDate = $this->endDate;

		$all = $this->data()->getEventList(
			$this->startDate ? $this->startDate->date() : null,
			$endDate ? $endDate->date() : null
		);

		$all_events_count = $all->count();

		return $all;
	}

	public function ics() {
		//echo 'hello';
		$params = $this->getRequest()->params();

		// 	$year = $params['Year'];
		$feed = false;
		$announcement = false;
		$id = $params['ID'];
		$oid = $params['OtherID'];
		if (stristr($id, "ICS_") !== false) {
			$id = str_replace("ICS_", "", $id);
			$feed = true;
		}
		if (is_numeric($id) && $oid) {
			if (!$feed) {
				$event = DataObject::get_by_id('CalendarEvent', $id);

				// return if not found
				if (!$event) {
					echo 'event not found?';
					//return $this->httpError(404);
				}
				$FILENAME = $announcement ? preg_replace("/[^a-zA-Z0-9s]/", "", $event->Title) : $event->URLSegment;
			} else {
				$FILENAME = preg_replace("/[^a-zA-Z0-9s]/", "", urldecode($_REQUEST['title']));
			}

			$FILENAME .= ".ics";
			$HOST = $_SERVER['HTTP_HOST'];
			$TIMEZONE = Calendar::config()->timezone;
			$LANGUAGE = Calendar::config()->language;
			$CALSCALE = "GREGORIAN";
			$parts = explode('-', $oid);
			$START_TIMESTAMP = $parts[0];
			$END_TIMESTAMP = $parts[1];
			if (!$feed) {
				$URL = $announcement ? $event->Calendar()->AbsoluteLink() : $event->AbsoluteLink();
			} else {
				$URL = "";
			}
			$TITLE = $feed ? $_REQUEST['title'] : $event->Title;
			$CONTENT = $feed ? $_REQUEST['content'] : $event->obj('Content')->Summary();
			$LOCATION = $feed ? $_REQUEST['location'] : $event->Location;

			// $this->setResponse(new HTTPResponse());
			$this->getResponse()->addHeader('Cache-Control', 'private');
			$this->getResponse()->addHeader('Content-Description', 'File Transfer');
			$this->getResponse()->addHeader('Content-Type', 'text/calendar');
			$this->getResponse()->addHeader('Content-Transfer-Encoding', 'binary');
			if (stristr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
				$this->getResponse()->addHeader("Content-disposition", "filename=" . $FILENAME . "; attachment;");
			} else {
				$this->getResponse()->addHeader("Content-disposition", "attachment; filename=" . $FILENAME);
			}
			$result = trim(strip_tags($this->customise(array(
				'HOST' => $HOST,
				'LANGUAGE' => $LANGUAGE,
				'TIMEZONE' => $TIMEZONE,
				'CALSCALE' => $CALSCALE,
				'START_TIMESTAMP' => $START_TIMESTAMP,
				'END_TIMESTAMP' => $END_TIMESTAMP,
				'URL' => $URL,
				'TITLE' => $TITLE,
				'CONTENT' => $CONTENT,
				'LOCATION' => $LOCATION,
			))->renderWith(array('ics'))));

			$this->getResponse()->setBody($result);
			//print_r($this->getResponse());
			return $this->getResponse();

		} else {

			$this->redirectBack();
		}

	}
}
