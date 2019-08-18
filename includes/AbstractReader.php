<?php
declare(strict_types=1);

namespace Mireiawen\Sloppey\Calendar;

/**
 * Reader base class
 *
 * @package Mireiawen\Sloppey\Calendar
 */
abstract class AbstractReader
{
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
	abstract public function GetEvents(int $days) : iterable;
	
}