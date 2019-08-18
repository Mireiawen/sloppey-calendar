<?php
declare(strict_types = 1);

namespace Mireiawen\Sloppey\Calendar;

use ICal\ICal;

/**
 * Class Reader
 *
 * @package Mireiawen\Sloppey\Calendar
 */
class Reader
{
	/**
	 * The ICal event feed
	 *
	 * @var ICal
	 */
	protected $ical;
	
	/**
	 * The type of the events to return
	 *
	 * @var string
	 */
	protected $eventType;
	
	/**
	 * Construct the reader, this just sets the ICal feed
	 *
	 * @param ICal $feed
	 *    The calendar feed itself
	 * @param string $eventType
	 *    The type of the event to create from
	 */
	public function __construct(ICal $feed, string $eventType)
	{
		$this->ical = $feed;
		$this->eventType = $eventType;
	}
	
	/**
	 * Get the events from the feed
	 *
	 * @param string|null $interval
	 *    The interval from where to get the events, NULL for all future events
	 *
	 * @return iterable|AbstractEvent
	 * @throws \Exception
	 */
	public function GetEvents(?string $interval = NULL) : iterable
	{
		// Get all events since interval is not set
		if ($interval === NULL)
		{
			$events = $this->ical->events();
		}
		
		// Get events only for the interval
		else
		{
			$events = $this->ical->eventsFromInterval($interval);
		}
		
		// Turn the data into Event objects
		foreach ($events as $event)
		{
			yield new $this->eventType($event);
		}
	}
}
