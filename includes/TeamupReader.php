<?php
declare(strict_types = 1);

namespace Mireiawen\Sloppey\Calendar;

/**
 * Class to read the Teamup events from the API into events
 *
 * @package Mireiawen\Sloppey\Calendar
 */
class TeamupReader extends AbstractReader
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
	 * @param Teamup $teamup
	 */
	public function __construct(Teamup $teamup)
	{
		$this->client = $teamup;
	}
	
	/**
	 * Get the events from the feed
	 *
	 * @param int $days
	 * 	The number of days to the future to get the events from
	 *
	 * @return iterable|AbstractEvent
	 *
	 * @throws \Exception
	 */
	public function GetEvents(int $days) : iterable
	{
		$start = new \DateTime();
		$end = new \DateTime(\sprintf('+%d days', $days));
		
		foreach ($this->client->GetEvents($start, $end) as $event)
		{
			yield new TeamupEvent($event, $this->client);
		}
	}
}