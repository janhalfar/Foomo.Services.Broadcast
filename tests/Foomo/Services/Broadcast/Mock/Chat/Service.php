<?php

namespace Foomo\Services\Broadcast\Mock\Chat;

/**
 * a simple chat service
 */
class Service {
	/**
	 * @var Broadcaster
	 */
	private $broadCaster;
	public function __construct()
	{
		$this->broadCaster = new Broadcaster;
	}
	/**
	 * join the chat
	 * 
	 * @param string $name 
	 * 
	 * @return boolean
	 */
	public function join($name)
	{
		$this->broadCaster->join($name);
	}
	public function say($message)
	{
		$this->broadCaster->say($this->userName, $message);
	}
	/**
	 * leave the chat
	 * 
	 * @param string $name
	 * 
	 * @return boolean
	 */
	public function leave($name)
	{
		$this->broadCaster->leave($name);
	}
	
}