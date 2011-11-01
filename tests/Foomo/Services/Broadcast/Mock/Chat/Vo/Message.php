<?php

namespace Foomo\Services\Broadcast\Mock\Chat\Vo;
/**
 * a message in a chat
 */
class Message {
	/**
	 * user id
	 * 
	 * @var Foomo\Services\Broadcast\Mock\Chat\Vo\User
	 */
	public $from;
	/**
	 * @var string
	 */
	public $text;
}