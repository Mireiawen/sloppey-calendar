<?php
/** @noinspection SpellCheckingInspection */
declare(strict_types = 1);

namespace Mireiawen\Sloppey\Calendar;

/**
 * Event helper class
 *
 * @package Mireiawen\Sloppey\Calendar
 */
class TeamUp extends AbstractEvent
{
	/**
	 * Event constructor.
	 *
	 * @param \ICal\Event $event
	 *
	 * @throws \Exception
	 */
	public function __construct(\ICal\Event $event)
	{
		\var_dump($event);
		
		$this->SetData(
			[
				'Summary' => \trim($matches['summary']),
				'Status' => $status,
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
}
