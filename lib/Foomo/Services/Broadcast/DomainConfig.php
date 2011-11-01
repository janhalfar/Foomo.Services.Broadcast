<?php

namespace Foomo\Services\Broadcast;

class DomainConfig extends \Foomo\Config\AbstractConfig {
	const NAME = 'Foomo.Services.broadcast';
	/**
	 * That is where the server connects - it should not be exposed
	 * 
	 * ip/name:port
	 * 
	 * @var string
	 */
	public $in = '127.0.0.1:8080';
	/**
	 * That is where the clients connect to receive broadcasts
	 * 
	 * ip/name:port
	 * 
	 * @var string
	 */
	public $out = '127.0.0.1:8765';
	public function getInServer()
	{
		return $this->getServer($this->in);
	}
	public function getInPort()
	{
		return $this->getPort($this->in);
	}
	public function getOutServer()
	{
		return $this->getServer($this->out);
	}
	public function getOutPort()
	{
		return $this->getPort($this->out);
	}
	private function getPort($setting)
	{
		$parts = $this->splitSetting($setting);
		return $parts[1];
	}
	private function getServer($setting)
	{
		$parts = $this->splitSetting($setting);
		return $parts[0];
	}
	private function splitSetting($setting)
	{
		return explode(':', $setting);
	}
	
}