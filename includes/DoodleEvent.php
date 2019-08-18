<?php
/** @noinspection SpellCheckingInspection */
declare(strict_types = 1);

namespace Mireiawen\Sloppey\Calendar;

use ICal\Event;

/**
 * Event class for Doodle events
 *
 * @package Mireiawen\Sloppey\Calendar
 */
class DoodleEvent extends AbstractEvent
{
	/**
	 * Text for the voted Doodle items
	 *
	 * @var string
	 */
	public const STATUS_TEXT_EVENT = 'Doodle';
	
	/**
	 * Text for the Doodle item in voting
	 *
	 * @note: It is translated by the Doodle
	 *
	 * @var string
	 */
	public const STATUS_TEXT_VOTING = 'Doodle-kesken';
	
	/**
	 * Event constructor.
	 *
	 * @param Event $event
	 *
	 * @throws \Exception
	 */
	public function __construct(Event $event)
	{
		// Cut the actual text and the Doodle status from Doodle summary
		if (\preg_match('/^(?P<summary>.*)\s*\[(?P<status>.*?)]$/', $event->summary, $matches) === FALSE)
		{
			throw new \Exception(\sprintf(\_('Unable to parse the Doodle Calendar subject line "%s"'), $event->summary));
		}
		
		if (empty($matches))
		{
			throw new \Exception(\sprintf(\_('Got invalid data from the Doodle Calendar')));
		}
		
		// Change the status text that can be translated into custom non-translated text
		switch ($matches['status'])
		{
		case self::STATUS_TEXT_EVENT:
			$status = self::STATUS_RAID_PROGRESS;
			break;
		
		case self::STATUS_TEXT_VOTING:
			$status = self::STATUS_IN_VOTING;
			break;
		
		default:
			throw new \Exception(\sprintf(\_('Invalid status %s'), $matches['status']));
		}
		
		// Cut the description, participants and URL from Doodle message
		// @note: This does require paid Doodle account for it to work and it is translated by the Doodle
		if (\preg_match("/^Aloitteesta\s+.+?\n(?P<description>.*)\sOsallistujat:\s(?P<participants>.*)(?P<url>https:\/\/.+?)$/ms", $event->description, $descriptions) === FALSE)
		{
			throw new \Exception(\sprintf(\_('Unable to parse the Doodle Calendar message')));
		}
		
		if (empty($descriptions))
		{
			throw new \Exception(\sprintf(\_('Invalid message format, possibly free Doodle account')));
		}
		
		$summary = \trim($matches['summary']);
		$description = \trim($descriptions['description']);
		$url = \trim($descriptions['url']);
		
		// Trim the participants out of the text blob
		$participants = [];
		foreach (\explode("\n", $descriptions['participants']) as $participant)
		{
			$pos = \strpos($participant, '-');
			if ($pos !== FALSE)
			{
				$participant = \trim(\substr($participant, $pos + 1));
			}
			else
			{
				$participant = \trim($participant);
			}
			
			if (empty($participant))
			{
				continue;
			}
			
			$participants[] = $participant;
		}
		sort($participants);
		
		// Generate the start and end dates in UTC
		$tz = $this->GetEventTimezone();
		$start = $this->GetEventStart($event, $tz);
		$end = $this->GetEventEnd($event, $tz);
		$msgid = \sprintf('doodle_%s_%s', $event->uid, $event->dtstart);
		
		// Save the fetched data
		$this->SetData(
			[
				'MessageID' => $msgid,
				'Summary' => $summary,
				'Status' => $status,
				'Start' => $start,
				'End' => $end,
				'Duration' => $end->diff($start),
				'Description' => $description,
				'Signup' => TRUE,
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
	 * @return \DateTimeZone
	 */
	protected function GetEventTimezone() : \DateTimeZone
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
