<?php
declare(strict_types = 1);

namespace Mireiawen\Sloppey\Calendar;

use ICal\Event;

/**
 * Event class for ICal events
 *
 * @package Mireiawen\Sloppey\Calendar
 */
class IcalEvent extends AbstractEvent
{
	/**
	 * Event constructor.
	 *
	 * @param Event $event
	 *
	 * @throws \Exception
	 */
	public function __construct(Event $event)
	{
		// Generate the start and end dates in UTC
		$tz = $this->GetEventTimezone($event);
		$start = $this->GetEventStart($event, $tz);
		$end = $this->GetEventEnd($event, $tz);
		
		// Save the fetched data
		$this->SetData(
			[
				'Summary' => \trim($matches['summary']),
				'Status' => $event->status,
				'Start' => $start,
				'End' => $end,
				'Duration' => $end->diff($start),
				'Description' => $description,
				'Attendees' => $participants,
				'URL' => $url,
				'UID' => $event->uid,
				'DateStart' => $event->dtstart,
			]
		);
	}
	
	/**
	 * Get the event timezone
	 *
	 * @param Event $event
	 *
	 * @return \DateTimeZone
	 */
	protected function GetEventTimezone(Event $event) : \DateTimeZone
	{
		return new \DateTimeZone('UTC');
	}
	
	/**
	 * Get the event start
	 *
	 * @param Event $event
	 * @param \DateTimeZone $tz
	 *
	 * @return \DateTime
	 * @throws \Exception
	 */
	protected function GetEventStart(Event $event, \DateTimeZone $tz) : \DateTime
	{
		return new \DateTime($event->dtstart, $tz);
	}
	
	/**
	 * Get the event end
	 *
	 * @param Event $event
	 * @param \DateTimeZone $tz
	 *
	 * @return \DateTime
	 * @throws \Exception
	 */
	protected function GetEventEnd(Event $event, \DateTimeZone $tz) : \DateTime
	{
		return new \DateTime($event->dtend, $tz);
	}
}
