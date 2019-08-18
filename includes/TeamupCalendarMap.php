<?php
declare(strict_types = 1);

namespace Mireiawen\Sloppey\Calendar;

/**
 * Calendar to status mapping helper
 *
 * @package Mireiawen\Sloppey\Calendar
 */
class TeamupCalendarMap
{
	/**
	 * @var array
	 */
	protected $map;
	
	/**
	 * TeamupCalendarMap constructor
	 */
	public function __construct()
	{
		$this->map = [];
	}
	
	/**
	 * Add a calendar mapping
	 *
	 * @param int $id
	 *    Calendar ID
	 *
	 * @param string $status
	 *    The status string
	 */
	public function Add(int $id, string $status) : void
	{
		$this->map[$id] = $status;
	}
	
	/**
	 * Get the calendar mappings
	 *
	 * @return array
	 */
	public function GetMap() : array
	{
		return $this->map;
	}
	
	/**
	 * Get the status for the calendar
	 *
	 * @param int $id
	 *    The calendar ID to get status for
	 *
	 * @return string|null
	 *    The status, or null if calendar is not found
	 */
	public function GetStatus(int $id) : ?string
	{
		return $this->map[$id] ?? NULL;
	}
}