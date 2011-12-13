<?php

namespace Foomo\Services\Broadcast\ReceiverGenerator;

use Foomo\Services\Broadcast\Utils;
use Foomo\Services\Broadcast\Module;
use Foomo\Flash\ActionScript\PHPUtils;

class Actionscript extends AbstractGenerator {
	public $events = array();
	public function generateReceiver()
	{
		$this->events = Utils::getBroadcasterEvents($this->broadcasterClassname);
		// the receiver
		$broadcatserObj = new \Foomo\Services\Reflection\ServiceObjectType($this->broadcasterClassname);
		$this->writeSource(
			$broadcatserObj->getRemotePackagePath() . DIRECTORY_SEPARATOR . 'Receiver.as', 
			self::getView(
				'ReceiverClass', 
				array('broadcaster' => $broadcatserObj, 'events' => $this->events)
			)->render()
		);
		/* @var $event \Foomo\Services\Broadcast\Event */
		foreach($this->events as $event) {
			$this->writeSource($broadcatserObj->getRemotePackagePath() . DIRECTORY_SEPARATOR . 'events' . DIRECTORY_SEPARATOR .  ucfirst($event->name) . 'Event' . '.as', self::getView('EventClass', array('event' => $event, 'broadcaster' => $broadcatserObj))->render());
		}
		// write value objects
		foreach(Utils::getBroadcasterVOs($this->broadcasterClassname) as $vo) {
			$obj = new \Foomo\Services\Reflection\ServiceObjectType($vo);
			if(!PHPUtils::isASStandardType($vo)) {
				$this->writeSource($obj->getRemotePackagePath() . DIRECTORY_SEPARATOR . \Foomo\Flash\ActionScript\PHPUtils::getASType($vo) . '.as', self::getView('VOClass', $obj)->render());
			}
		}
	}
	private function getView($template, $model)
	{
		return Module::getView(__CLASS__ . '\\isAHackAndWillbecut', $template, $model);
	}
}
