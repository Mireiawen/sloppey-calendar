<?php
/** @noinspection SpellCheckingInspection */
declare(strict_types = 1);

namespace Mireiawen\Sloppey\Calendar;

/**
 * Event helper class
 *
 * @package Mireiawen\Sloppey\Calendar
 */
class TeamUpEvent extends AbstractEvent
{
	/**
	 * Event constructor.
	 *
	 * @param array $event
	 * @param Teamup $teamup
	 *
	 * @throws \Exception
	 */
	public function __construct(array $event, Teamup $teamup)
	{
		\var_dump($event);
		
		// Basic data
		$summary = $this->GetEventValue($event, 'title', NULL);
		$description = $this->GetEventValue($event, 'notes', '');
		
		// Attendance data not available
		$participants = [];
		
		// Generate the start and end dates
		$tz = $this->GetEventTimezone($this->GetEventValue($event, 'tz', 'UTC'));
		$start = $this->GetEventStart($this->GetEventValue($event, 'start_dt', NULL), $tz);
		$end = $this->GetEventEnd($this->GetEventValue($event, 'end_dt', NULL), $tz);
		
		// TeamUp doesn't seem to be adding event URL,
		// let us build one for the day of the event
		$url = \sprintf('https://www.teamup.com/%s/event/%s', $teamup->GetCalendarKey(), $this->GetEventValue($event, 'id', NULL));
		
		$this->SetData(
			[
				'Summary' => $summary,
				'Status' => self::STATUS_EVENT,
				'Start' => $start,
				'End' => $end,
				'Duration' => $end->diff($start),
				'Description' => $description,
				'Attendees' => $participants,
				'URL' => $url,
				'UID' => $this->GetEventValue($event, 'who', ''),
				'DateStart' => $start->format('Ymd\THis'),
			]
		);
	}
	
	/**
	 * @param array $event
	 * @param string $key
	 * @param string|null $default
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function GetEventValue(array $event, string $key, ?string $default) : string
	{
		if ((!isset($event[$key])) || ($event[$key] === NULL))
		{
			if ($default === NULL)
			{
				throw new \Exception(\sprintf(\_('Missing the required event value for "%s"'), $key));
			}
			
			return $default;
		}
		
		return $event[$key];
	}
	
	/**
	 * Get the event timezone
	 *
	 * @param string $tz
	 *
	 * @return \DateTimeZone
	 */
	protected function GetEventTimezone(string $tz) : \DateTimeZone
	{
		return new \DateTimeZone($tz);
	}
	
	/**
	 * Get the event start
	 *
	 * @param string $start
	 * @param \DateTimeZone $tz
	 *
	 * @return \DateTime
	 * @throws \Exception
	 */
	protected function GetEventStart(string $start, \DateTimeZone $tz) : \DateTime
	{
		$utc = new \DateTimeZone('UTC');
		$datetime = new \DateTime($start, $tz);
		$datetime->setTimezone($utc);
		return $datetime;
	}
	
	/**
	 * Get the event end
	 *
	 * @param string $end
	 * @param \DateTimeZone $tz
	 *
	 * @return \DateTime
	 * @throws \Exception
	 */
	protected function GetEventEnd(string $end, \DateTimeZone $tz) : \DateTime
	{
		$utc = new \DateTimeZone('UTC');
		$datetime = new \DateTime($end, $tz);
		$datetime->setTimezone($utc);
		return $datetime;
	}
}
