<?php
declare(strict_types = 1);

namespace Mireiawen\Sloppey\Calendar;

use AG\DiscordMsg;
use Httpful\Request;
use ICal\ICal;
use League\HTMLToMarkdown\HtmlConverter;
use Smarty;

/**
 * Main application class
 *
 * @package Mireiawen\Sloppey\Calendar
 */
class Application
{
	/**
	 * Initial spam sent
	 *
	 * @var string
	 */
	protected const SENT_INITIAL = 'INITIAL MESSAGE SENT';
	
	/**
	 * Spam for tomorrow sent
	 *
	 * @var string
	 */
	protected const SENT_TOMORROW = 'TOMORROW MESSAGE SENT';
	
	/**
	 * Spam for 2 hours sent
	 *
	 * @var string
	 */
	protected const SENT_TWOHOURS = 'TWO HOURS MESSAGE SENT';
	
	/**
	 * One day in seconds
	 *
	 * @var int
	 */
	protected const TIME_DAY = 24 * 60 * 60;
	
	/**
	 * Two hours in seconds
	 *
	 * @var int
	 */
	protected const TIME_TWOHOURS = 2 * 60 * 60;
	
	/**
	 * Instance of the cache backend
	 *
	 * @var Redis
	 */
	protected $cache;
	
	/**
	 * Instance of the configuration instance
	 *
	 * @var Config
	 */
	protected $config;
	
	/**
	 * Application constructor.
	 *
	 * @param Config $cfg
	 * @param Redis $cache
	 */
	public function __construct(Config $cfg, Redis $cache)
	{
		$this->config = $cfg;
		$this->cache = $cache;
	}
	
	/**
	 * Run the actual application
	 *
	 * @throws \Exception
	 */
	public static function Run() : void
	{
		$config = new Config('config.json');
		$cache = Redis::CreateConnection($config->Get('Cache Hostname', 'localhost'));
		$app = new Application($config, $cache);
		
		// Teamup API
		$events = $app->ReadTeamupEvents();
		$events = $app->FilterEvents($events);
		$templates = $app->GenerateTemplates($events);
		$app->SendMessages($templates);
		
		// Doodle feed
		$events = $app->ReadDoodleEvents();
		$events = $app->FilterEvents($events);
		$templates = $app->GenerateTemplates($events);
		$app->SendMessages($templates);
	}
	
	/**
	 * Read the events from the Teamup API
	 *
	 * @return array|AbstractEvent
	 *
	 * @throws \Exception
	 */
	public function ReadTeamupEvents() : array
	{
		if ($this->config->Get('Debug', FALSE))
		{
			$this->cache->Flush('teamup_events');
		}
		
		if ($this->cache->Exists('teamup_events'))
		{
			return unserialize($this->cache->Fetch('teamup_events'));
		}
		
		$map = new TeamupCalendarMap();
		
		$map->Add($this->config->Get('Teamup Calendar Events'), AbstractEvent::STATUS_EVENT);
		$map->Add($this->config->Get('Teamup Calendar Clears'), AbstractEvent::STATUS_RAID_CLEAR);
		$map->Add($this->config->Get('Teamup Calendar Progress'), AbstractEvent::STATUS_RAID_PROGRESS);
		
		$request = Request::init();
		$teamup = new Teamup($request, $map);
		$teamup->SetAPIKey($this->config->Get('Teamup API Key'));
		$teamup->SetCalendarKey($this->config->Get('Teamup Calendar Key'));
		$reader = new TeamupReader($teamup);
		$events = iterator_to_array($this->ReadCombinedEvents($reader));
		$this->cache->Store('teamup_events', serialize($events), $this->config->Get('Cache Timeout', 3600));
		return $events;
	}
	
	/**
	 * Read the events from the Doodle feed
	 *
	 * @return array|AbstractEvent
	 *
	 * @throws \Exception
	 */
	public function ReadDoodleEvents() : array
	{
		if ($this->config->Get('Debug', FALSE))
		{
			$this->cache->Flush('doodle_events');
		}
		
		if ($this->cache->Exists('doodle_events'))
		{
			return unserialize($this->cache->Fetch('doodle_events'));
		}
		
		$ical = new ICal();
		$ical->initUrl($this->config->Get('Doodle Feed'));
		$reader = new IcalReader($ical, DoodleEvent::class);
		$events = iterator_to_array($this->ReadCombinedEvents($reader));
		$this->cache->Store('doodle_events', serialize($events), $this->config->Get('CacheTimeout', 3600));
		return $events;
	}
	
	/**
	 * @param AbstractEvent[] $events
	 *
	 * @return \Generator
	 *
	 * @throws \Exception
	 */
	public function FilterEvents(array $events) : \Generator
	{
		foreach ($events as $event)
		{
			$msgid = $event->GetMessageID();
			$start = $event->GetStart();
			
			if ($this->ShouldSendMessage($msgid, $start))
			{
				yield $event;
			}
		}
	}
	
	/**
	 * @param AbstractEvent[] $events
	 *
	 * @return iterable
	 *
	 * @throws \SmartyException
	 * @throws \Exception
	 */
	public function GenerateTemplates(iterable $events) : iterable
	{
		$smarty = new Smarty();
		
		foreach ($events as $event)
		{
			switch ($event->GetStatus())
			{
			case AbstractEvent::STATUS_EVENT:
				$user = $this->config->Get('Discord User Event');
				$avatar = $this->config->Get('Discord Avatar Event');
				break;
			
			case AbstractEvent::STATUS_RAID_PROGRESS:
				$user = $this->config->Get('Discord User Progress');
				$avatar = $this->config->Get('Discord Avatar Progress');
				break;
				
			case AbstractEvent::STATUS_RAID_CLEAR:
				$user = $this->config->Get('Discord User Clear');
				$avatar = $this->config->Get('Discord Avatar Clear');
				break;
				
			case AbstractEvent::STATUS_IN_VOTING:
				continue 2;
				
			default:
				$user = $this->config->Get('Discord User');
				$avatar = $this->config->Get('Discord Avatar');
			}
			
			$start = $this->GetStartDay($event);
			$duration = $this->GetDurationMessage($event->GetDuration());
			
			$tpl = $smarty->createTemplate('templates/event.tpl.txt');
			$tpl->assign('event', $event);
			$tpl->assign('time', $start);
			$tpl->assign('duration', $duration);
			$tpl->assign('user', $user);
			$tpl->assign('avatar', $avatar);
			
			yield $tpl;
		}
	}
	
	/**
	 * @param \Smarty_Internal_Template[] $templates
	 *
	 * @throws \Exception
	 */
	public function SendMessages(iterable $templates) : void
	{
		foreach ($templates as $tpl)
		{
			$message = $tpl->fetch();
			$user = $tpl->getTemplateVars('user');
			$avatar = $tpl->getTemplateVars('avatar');
			echo '------------------------------------------------------------------------' , "\n";
			echo 'User: ' , $user , "\n";
			echo 'Avatar: ' , $avatar , "\n";
			echo $message, "\n";
			echo '------------------------------------------------------------------------' , "\n";
			
			$msg = new DiscordMsg(
				$message,
				$this->config->Get('Discord Hook'),
				$user,
				$avatar
			);
			if (!$this->config->Get('Debug', FALSE))
			{
				$msg->send();
			}
		}
	}
	
	/**
	 * Combine multiple events next to each other into one large event
	 *
	 * @param AbstractReader $reader
	 *
	 * @return \Generator|AbstractEvent
	 * @throws \Exception
	 */
	protected function ReadCombinedEvents(AbstractReader $reader) : iterable
	{
		/** @var AbstractEvent|NULL $prev */
		$prev = NULL;
		
		/** @var AbstractEvent $event */
		$event = NULL;
		
		$now = new \DateTime('now');
		
		foreach ($reader->GetEvents($this->config->Get('Days to Fetch', 4)) as $event)
		{
			if ($event->GetStatus() === AbstractEvent::STATUS_IN_VOTING)
			{
				continue;
			}
			
			if ($event->GetStart() <= $now)
			{
				continue;
			}
			
			if ($prev !== NULL)
			{
				if ($prev->GetEnd() == $event->GetStart())
				{
					$prev->Merge($event);
					continue;
				}
			}
			
			if ($prev !== NULL)
			{
				yield $prev;
			}
			$prev = $event;
		}
		if ($prev !== NULL)
		{
			yield $prev;
		}
	}
	
	/**
	 * Check if the message should be sent
	 *
	 * @param string $msgid
	 *    Unique identifier for the message
	 *
	 * @param \DateTime $start
	 *    The event start time
	 *
	 * @return bool
	 *
	 * @throws \Exception
	 */
	protected function ShouldSendMessage(string $msgid, \DateTime $start) : bool
	{
		// Initial message
		if (!$this->cache->Exists($msgid))
		{
			$this->cache->Store($msgid, self::SENT_INITIAL, Redis::PERSISTENT);
			return TRUE;
		}
		
		// Get the status of the message
		$sent_status = $this->cache->Fetch($msgid);
		
		// Calculate the difference from current tome to start of the event
		$now = time();
		$timestamp = $start->getTimestamp();
		$diff = $timestamp-$now;
		
		// Check for tomorrow
		if ($sent_status === self::SENT_INITIAL)
		{
			if ($diff < self::TIME_DAY)
			{
				$this->cache->Store($msgid, self::SENT_TOMORROW, Redis::PERSISTENT);
				return TRUE;
			}
			
			return FALSE;
		}
		
		// Check for the 2 hour notification
		if ($sent_status === self::SENT_TOMORROW)
		{
			if ($diff < self::TIME_TWOHOURS)
			{
				$this->cache->Store($msgid, self::SENT_TWOHOURS, Redis::PERSISTENT);
				return TRUE;
			}
			
			return FALSE;
		}
		
		return FALSE;
	}
	
	/**
	 * @param AbstractEvent $event
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function GetStartDay(AbstractEvent $event) : string
	{
		$tz = new \DateTimeZone('UTC');
		$now = new \DateTime('now', $tz);
		$start = $event->GetStart()->diff($now);
		$tomorrow = new \DateTime($now->format('Y-m-d'), $tz);
		$tomorrow->modify('+1 day');
		
		// Check for today
		if ($now->format('Y-m-d') === $event->GetStart()->format('Y-m-d'))
		{
			return $start->format('in %h hours and %i minutes');
		}
		
		// Check for tomorrow
		if ($tomorrow->format('Y-m-d') === $event->GetStart()->format('Y-m-d'))
		{
			return \_('tomorrow');
		}
		
		// Other days
		return \sprintf(\_('on %s'), $event->GetStart()->format('l'));
	}
	
	/**
	 * @param \DateInterval $duration
	 *
	 * @return string
	 */
	protected function GetDurationMessage(\DateInterval $duration) : string
	{
		if ($duration->m === 0 && $duration->h === 0)
		{
			return '';
		}
		
		if ($duration->m < 10)
		{
			return $duration->format('%h hours');
		}
		
		if ($duration->m < 20)
		{
			return $duration->format('%h hours 15 minutes');
		}
		
		if ($duration->m < 40)
		{
			return $duration->format('%h hours 30 minutes');
		}
		
		if ($duration->m < 50)
		{
			return $duration->format('%h hours 45 minutes');
		}
		
		return sprintf('%d hours', $duration->h + 1);
	}
}
