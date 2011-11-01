<?php

namespace Foomo\Services\Broadcast\Mock\Chat;

/**
 * pipe it to all of them
 */
class Broadcaster extends \Foomo\Services\Broadcast\AbstractBroadcaster {
	public function __construct()
	{
		$this->dataFormats = array(\Foomo\Services\Broadcast::FORMAT_AMF0);
	}
	/**
	 * message event
	 * 
	 * @param string $from
	 * @param string $message 
	 * 
	 * @Foomo\Services\Broadcast\Event(type='Foomo\Services\Broadcast\Mock\Chat\Vo\Message')
	 */
	public function say($from, $text)
	{
		$message = new Vo\Message;
		$message->from = $from;
		$message->message = $text;
		$this->broadcast($message);
	}
	/**
	 * join event - sbdy joins the chat
	 * 
	 * @param type $name
	 * 
	 * @Foomo\Services\Broadcast\Event(type='Foomo\Services\Broadcast\Mock\Chat\Vo\User')
	 */
	public function join($name)
	{
		$user = new Vo\User;
		$user->name = $name;
		$this->broadcast($user);
	}
	/**
	 * leave event - sbdy joins the chat
	 * 
	 * @param string $name
	 * 
	 * @Foomo\Services\Broadcast\Event(type='Foomo\Services\Broadcast\Mock\Chat\Vo\User')
	 */
	public function leave($name)
	{
		$user = new Vo\User;
		$user->name = $name;
		$this->broadcast($user);
	}
}