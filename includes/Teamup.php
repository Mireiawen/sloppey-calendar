<?php
declare(strict_types = 1);

namespace Mireiawen\Sloppey\Calendar;

use Httpful\Exception\ConnectionErrorException;
use Httpful\Http;
use Httpful\Mime;
use Httpful\Request;

/**
 * Teamup API helper
 *
 * @package Mireiawen\Sloppey\Calendar
 */
class Teamup
{
	/**
	 * The method to use for HTTP GET requests
	 *
	 * @var string
	 */
	protected const METHOD_GET = Http::GET;
	
	/**
	 * The header used to set the token for Teamup
	 *
	 * @var string
	 */
	protected const TOKEN_HEADER = 'Teamup-Token';
	
	/**
	 * The date format used by Teamup
	 *
	 * @var string
	 */
	protected const DATE_FORMAT = 'Y-m-d';
	
	/**
	 * The client itself
	 *
	 * @var Request
	 */
	protected $client;
	
	/**
	 * Calendar key
	 *
	 * @var string
	 */
	protected $calendarKey;
	
	/**
	 * The calendar to status mapping
	 *
	 * @var TeamupCalendarMap
	 */
	protected $map;
	
	/**
	 * Teamup constructor.
	 *
	 * @param Request $client
	 *    The Httpful request to use as the client
	 *
	 * @param TeamupCalendarMap $map
	 *    The calendar to status mapping
	 *
	 * @param string $apiKey
	 *    The API key to use, empty by default
	 *
	 * @param string $calendarKey
	 *    The calendar key to read from, empty by default
	 */
	public function __construct(Request $client, TeamupCalendarMap $map, string $apiKey = '', string $calendarKey = '')
	{
		$this->client = $client;
		$this->map = $map;
		$this->SetAPIKey($apiKey);
		$this->SetCalendarKey($calendarKey);
	}
	
	/**
	 * Set the key for the API
	 *
	 * @param string $key
	 */
	public function SetAPIKey(string $key) : void
	{
		$this->client->addHeader(self::TOKEN_HEADER, $key);
	}
	
	/**
	 * Set the key to the calendar
	 *
	 * @param string $key
	 */
	public function SetCalendarKey(string $key) : void
	{
		$this->calendarKey = $key;
	}
	
	/**
	 * Get the current calendar key
	 *
	 * @return string
	 */
	public function GetCalendarKey() : string
	{
		return $this->calendarKey;
	}
	
	/**
	 * Get event status for specific calendar
	 *
	 * @param int $id
	 * 	Calendar ID to look
	 *
	 * @return string
	 * 	Event status
	 */
	public function GetStatusForCalendar(int $id) : string
	{
		return $this->map->GetStatus($id) ?? AbstractEvent::STATUS_EVENT;
	}
	
	/**
	 * Get the events between start and end date
	 *
	 * @param \DateTime $start
	 *    The date from where to start fetching the events
	 *
	 * @param \DateTime $end
	 *    The date to where to end fetching the events
	 *
	 * @return iterable
	 *
	 * @throws ConnectionErrorException
	 * @throws \Exception
	 */
	public function GetEvents(\DateTime $start, \DateTime $end) : iterable
	{
		$params = [
			'startDate' => $start->format(self::DATE_FORMAT),
			'endDate' => $end->format(self::DATE_FORMAT),
		];
		
		$response = \json_decode(
			$this->Request(self::METHOD_GET, 'events', $params),
			TRUE,
			512,
			\JSON_THROW_ON_ERROR
		);
		
		if (!isset($response['events']))
		{
			throw new \Exception(\sprintf(\_('Invalid response from the API: %s'), \_('Events are missing from the response')));
		}
		
		foreach ($response['events'] as $event)
		{
			yield $event;
		}
	}
	
	/**
	 * Read a single event by its ID
	 *
	 * @param string $id
	 *    The event ID
	 *
	 * @return array
	 *
	 * @throws ConnectionErrorException
	 * @throws \Exception
	 */
	public function GetEvent(string $id) : array
	{
		$response = \json_decode(
			$this->Request(self::METHOD_GET, \sprintf('events/%s', $id)),
			TRUE,
			512,
			\JSON_THROW_ON_ERROR
		);
		
		if (!isset($response['event']))
		{
			throw new \Exception(\sprintf(\_('Invalid response from the API: %s'), \_('Event is missing from the response')));
		}
		
		return $response['event'];
	}
	
	/**
	 * Do the actual request to the API
	 *
	 * @param string $method
	 *    HTTP method to use
	 *
	 * @param string $endpoint
	 *    The endpoint in the API
	 *
	 * @param array $params
	 *    Query string parameters for the API call
	 *
	 * @param string $body
	 *    Body data for the request
	 *
	 * @param string $mime
	 *    MIME type for the request, defaults to JSON
	 *
	 * @return string
	 *
	 * @throws ConnectionErrorException
	 * @throws \Exception
	 */
	protected function Request(string $method, string $endpoint, array $params = [], string $body = '', string $mime = Mime::JSON) : string
	{
		if (empty($params))
		{
			$params = '';
		}
		else
		{
			$params = \sprintf('?%s', \http_build_query($params));
		}
		$url = \sprintf('https://api.teamup.com/%s/%s%s', $this->calendarKey, $endpoint, $params);
		
		if (!empty($body))
		{
			$this->client->body($body, $mime);
		}
		
		$this->client->method($method);
		$this->client->uri($url);
		$this->client->autoParse(FALSE);
		$response = $this->client->send();
		
		if ($response->hasErrors())
		{
			$body = $response->raw_body;
			try
			{
				$data = \json_decode($body, TRUE, 512, \JSON_THROW_ON_ERROR);
				throw new \Exception(\sprintf(\_('API error %s: %s'), $data['id'], $data['title']));
			}
				/** @noinspection PhpRedundantCatchClauseInspection */
			catch (\JsonException $exception)
			{
				throw new \Exception(\sprintf(\_('Unable to request the API: %s'), $body));
			}
		}
		
		return $response->raw_body;
	}
}