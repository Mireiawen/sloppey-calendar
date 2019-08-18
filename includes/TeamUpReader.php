<?php
declare(strict_types=1);

namespace Mireiawen\Sloppey\Calendar;

/**
 * Class TeamUpReader
 *
 * @package Mireiawen\Sloppey\Calendar
 */
class TeamUpReader extends AbstractReader
{
	/**
	 * The Teamup client
	 *
	 * @var Teamup
	 */
	protected $client;
	
	/**
	 * Construct the reader
	 *
	 * @param
	 *    The calendar feed itself
	 * @param string $eventType
	 *    The type of the event to create from
	 */
	public function __construct(Teamup $teamup)
	{
		$this->client = $teamup;
	}
	
	/**
	 * Get the events from the feed
	 *
	 * @param string|null $interval
	 *    The interval from where to get the events, NULL for all future events
	 *
	 * @return iterable|AbstractEvent
	 *
	 * @throws \Exception
	 */
	public function GetEvents(?string $interval = NULL) : iterable
	{
		$start = new \DateTime();
		$end = new \DateTime();
		
		foreach ($this->client->GetEvents($start, $end) as $event)
		{
			yield new TeamUpEvent($event);
		}
	}
}