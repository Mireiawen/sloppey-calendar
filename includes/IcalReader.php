<?php
declare(strict_types = 1);

namespace Mireiawen\Sloppey\Calendar;

use ICal\ICal;

/**
 * Reader for ICal events
 *
 * @package Mireiawen\Sloppey\Calendar
 */
class IcalReader extends AbstractReader
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
	 * Construct the reader
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
	 * @param int $days
	 * 	The number of days to the future to get the events from
	 *
	 * @return iterable|AbstractEvent
	 * @throws \Exception
	 */
	public function GetEvents(int $days) : iterable
	{
		$events = $this->ical->eventsFromInterval(\sprintf('%d days', $days));
		
		// Turn the data into Event objects
		foreach ($events as $event)
		{
			yield new $this->eventType($event);
		}
	}
}
