<?php
/** @noinspection SpellCheckingInspection */
declare(strict_types = 1);

namespace Mireiawen\Sloppey\Calendar;

/**
 * Event base class
 *
 * @package Mireiawen\Sloppey\Calendar
 */
abstract class AbstractEvent
{
	/**
	 * Status for raids in voting
	 *
	 * @var string
	 */
	public const STATUS_IN_VOTING = 'In voting';
	
	/**
	 * Status for progress raids
	 *
	 * @var string
	 */
	public const STATUS_RAID_PROGRESS = 'Raid progress';
	
	/**
	 * Status for clear raids
	 *
	 * @var string
	 */
	public const STATUS_RAID_CLEAR = 'Raid clear';
	
	/**
	 * Status for generic events
	 *
	 * @var string
	 */
	public const STATUS_EVENT = 'Event';
	
	/**
	 * Actual event data
	 *
	 * @var array
	 */
	private $data;
	
	/**
	 * Get the unique message event
	 *
	 * @return string
	 */
	public function GetMessageID() : string
	{
		return $this->data['MessageID'];
	}
	
	/**
	 * Get the event summary text
	 *
	 * @return string
	 */
	public function GetSummary() : string
	{
		return $this->data['Summary'];
	}
	
	/**
	 * Get the event status text
	 *
	 * @return string
	 */
	public function GetStatus() : string
	{
		return $this->data['Status'];
	}
	
	/**
	 * Get the event start time
	 *
	 * @return \DateTime
	 */
	public function GetStart() : \DateTime
	{
		return $this->data['Start'];
	}
	
	/**
	 * Get the event start time
	 *
	 * @return \DateTime
	 */
	public function GetEnd() : \DateTime
	{
		return $this->data['End'];
	}
	
	/**
	 * Get the event duration
	 *
	 * @return \DateInterval
	 */
	public function GetDuration() : \DateInterval
	{
		return $this->data['Duration'];
	}
	
	/**
	 * Get the event description
	 *
	 * @return string
	 */
	public function GetDescription() : string
	{
		return $this->data['Description'];
	}
	
	/**
	 * Check if signup is enabled
	 *
	 * @return bool
	 */
	public function HasSignup() : bool
	{
		return $this->data['Signup'];
	}
	
	/**
	 * Get the event attendees
	 *
	 * @return array
	 */
	public function GetAttendees() : array
	{
		return $this->data['Attendees'];
	}
	
	/**
	 * Get the URL for the event
	 *
	 * @return string
	 */
	public function GetURL() : string
	{
		return $this->data['URL'];
	}
	
	/**
	 * Get the event UID
	 *
	 * @return string
	 */
	public function GetUID() : string
	{
		return $this->data['UID'];
	}
	
	/**
	 * Get the event date start
	 *
	 * @return string
	 */
	public function GetDateStart() : string
	{
		return $this->data['DateStart'];
	}
	
	/**
	 * Combine 2 events into one
	 *
	 * @param AbstractEvent $event
	 */
	public function Merge(AbstractEvent $event) : void
	{
		$this->data['End'] = $event->GetEnd();
		$this->data['Duration'] = $this->GetEnd()->diff($this->GetStart());
	}
	
	/**
	 * Set the data array and check its validity
	 *
	 * @param array $data
	 *
	 * @throws \Exception
	 */
	protected function SetData(array $data) : void
	{
		$keys = [
			'MessageID' => 'string',
			'Summary' => 'string',
			'Status' => 'string',
			'Start' => 'object',
			'End' => 'object',
			'Duration' => 'object',
			'Description' => 'string',
			'Signup' => 'boolean',
			'Attendees' => 'array',
			'URL' => 'string',
			'UID' => 'string',
			'DateStart' => 'string',
		];
		
		foreach ($keys as $key => $type)
		{
			if (!isset($data[$key]))
			{
				throw new \Exception(\sprintf(\_('Data array is missing the key "%s"'), $key));
			}
			
			if (\gettype($data[$key]) !== $type)
			{
				throw new \Exception(\sprintf(\_('Data array has invalid type for key "%s", expected %s, got %s'), $key, $type, \gettype($data[$key])));
			}
		}
		
		if (!$data['Start'] instanceof \DateTime)
		{
			throw new \Exception(\sprintf(\_('Start time is not a DateTime object')));
		}
		
		if (!$data['End'] instanceof \DateTime)
		{
			throw new \Exception(\sprintf(\_('End time is not a DateTime object')));
		}
		
		if (!$data['Duration'] instanceof \DateInterval)
		{
			throw new \Exception(\sprintf(\_('Duration is not a DateInterval object')));
		}
		
		$this->data = $data;
	}
}
