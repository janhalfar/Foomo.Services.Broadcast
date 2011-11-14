<?php

namespace Foomo\Services\Broadcast;

abstract class AbstractBroadcaster {
	protected $dataFormats = array(\Foomo\Services\Broadcast::FORMAT_AMF0, \Foomo\Services\Broadcast::FORMAT_JSON);
	protected function broadcast($data, array $clients = array())
	{
		// what is the event
		$trace = \debug_backtrace(false);
		$lastStackEntry = $trace[1];
		$class = $lastStackEntry['class'];
		$function = $lastStackEntry['function'];
		$event = str_replace('\\', '_', $class) . '_' . $function;
		return \Foomo\Services\Broadcast::broadcast($event, $data, $this->dataFormats);
	}
}