<?php

/*
 * This file is part of the foomo Opensource Framework.
 *
 * The foomo Opensource Framework is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public License as
 * published  by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * The foomo Opensource Framework is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with
 * the foomo Opensource Framework. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Foomo\Services;

/**
 * does the broadcasts through a static interface
 * 
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * 
 * @author jan <jan@bestbytes.de>
 */
class Broadcast 
{
	//---------------------------------------------------------------------------------------------
	// ~ Variables
	//---------------------------------------------------------------------------------------------

	/**
	 * tcp socket to the node.js server
	 * 
	 * @var resource
	 */
	private $socket;
	/**
	 *
	 * @var RPC\Serializer\AMF
	 */
	private $amfSerializer;
	
	//---------------------------------------------------------------------------------------------
	// ~ Constructor
	//---------------------------------------------------------------------------------------------

	private function __construct() {
		$this->amfSerializer = new RPC\Serializer\AMF;
	}
	const FORMAT_JSON = 'json';
	const FORMAT_AMF0 = 'AMF0';
	/**
	 * serialize data for supported clients
	 * 
	 * @param string[] $formats
	 * @param mixed $data
	 * 
	 * @return array 
	 */
	private function serializeForClients(array $formats, $data)
	{
		$ret = array();
		if(in_array(self::FORMAT_AMF0, $formats)) {
			// base64 encoded is "lighter" when transported with json
			$ret[self::FORMAT_AMF0] = base64_encode($this->amfSerializer->serialize($data));
		}
		if(in_array(self::FORMAT_JSON, $formats)) {
			$ret[self::FORMAT_JSON] = json_encode($data);
		}
		return $ret;
	}
	/**
	 * right now  there is only one node instance - that might like all 
	 * singletons be a bad idea
	 * 
	 * @return Broadcaster
	 */
	private static function getInstance()
	{
		static $inst;
		if(is_null($inst)) {
			$inst = new self;
		}
		return $inst;
	}
	
	//---------------------------------------------------------------------------------------------
	// ~ Private methods
	//---------------------------------------------------------------------------------------------
	
	/**
	 * talk to node
	 * 
	 * @staticvar int $i a debug counter
	 * 
	 * @param mixed $data
	 * 
	 * @return array not really defined yet - pblx some stats
	 */
	private function sendToNode($data)
	{
		static $i = 0;
		$i ++;
		// serialize data and write them to the socket and terminate with 0x00
		$ser = $this->serialize($data);
		$numBytes = socket_write(
			$this->getConnection(),
			$ser . chr(0)
		);
		// writing completely failed
		if(false === $numBytes) {
			trigger_error('could not send data to broadcast node', E_USER_ERROR);
		}
		// check if all bytes were sent
		if($numBytes != strlen($ser) + 1) {
			\Foomo\Utils::appendToPhpErrorLog($i . '   stats : ' . $numBytes . ' / ' . strlen($ser) . PHP_EOL);
			trigger_error('not all bytes went through ...', E_USER_ERROR);
		}

		// read shit, translate and return
		$answer = socket_read(
			$this->getConnection(), 
			64*1024, // hope this is enough, but it really seems to be ...
			PHP_BINARY_READ
		);
		$ret = $this->unserialize($answer);
		return $ret;
				
	}
	
	/**
	 * socket rpx call
	 * 
	 * @param type $method
	 * @param array $args
	 * @return type 
	 */
	private function nodeCommand($method, array $args)
	{
		return $this->sendToNode(array('method' => $method, 'args' => $args));
	}
	private function serialize($data)
	{
		return json_encode($data);
	}
	private function unserialize($json)
	{
		return json_decode($json, true);
	}
	/**
	 * use this to get a socket, that is lazy and is being reused ...
	 * 
	 * @return resource
	 */
	private function getConnection()
	{
		if(!isset($this->socket) || !is_resource($this->socket)) {
			$domainConfig = Broadcast\Module::getDomainConfig();
			$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			socket_set_block($this->socket);
			if(!socket_connect($this->socket, $domainConfig->getOutServer(), $domainConfig->getOutPort())) {
				trigger_error('could not connect to node broadcast server', E_USER_ERROR);
			}
		}
		return $this->socket;
	}

	//---------------------------------------------------------------------------------------------
	// ~ Public methods
	//---------------------------------------------------------------------------------------------
	/**
	 * get client info from node
	 * 
	 * @todo eveolve proper client infos
	 * 
	 * @return array
	 */
	public static function getClients()
	{
		$me = self::getInstance();
		return $me->nodeCommand('getClients', array('fooo'));
	}
	/**
	 * broadcast - do not call this, the AbstractBroadcaster does it for, when 
	 * you inherit from it
	 * 
	 * @param string $event
	 * @param miyed $data
	 * @param array $formats json | AMF0
	 * 
	 * @return array what came back from node
	 * 
	 * @internal
	 */
	public static function broadcast($event, $data, array $formats = array(self::FORMAT_AMF0, self::FORMAT_JSON))
	{
		$me = self::getInstance();
		return $me->nodeCommand('broadcast', array($me->serializeForClients($formats, array('event' => $event, 'data' => $data))));
	}
}