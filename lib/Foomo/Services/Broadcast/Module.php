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

namespace Foomo\Services\Broadcast;

/**
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 */
class Module extends \Foomo\Modules\ModuleBase
{
	//---------------------------------------------------------------------------------------------
	// ~ Constants
	//---------------------------------------------------------------------------------------------

	/**
	 * the name of this module
	 *
	 */
	const NAME = 'Foomo.Services.Broadcast';

	//---------------------------------------------------------------------------------------------
	// ~ Overriden static methods
	//---------------------------------------------------------------------------------------------
	/**
	 * initialize you module here may add some auto loading, will also be called, when switching between modes with Foomo\Config::setMode($newMode)
	 */
	public static function initializeModule()
	{
		if (!self::confExists(\Foomo\Flash\Vendor\Config::NAME)) {
			self::setConfig(\Foomo\Flash\Vendor\Config::create(array(
				self::NAME . '/vendor/org.foomo.rpc',
			)));
		}
	}
	/**
	 * Get a plain text description of what this module does
	 *
	 * @return string
	 */
	public static function getDescription()
	{
		return 'event based broadcasting of data to client connected over sockets in combination with node.js';
	}
	/**
	 *
	 * @staticvar type $domainConfig
	 * 
	 * @return DomainConfig
	 */
	public static function getDomainConfig()
	{
		static $domainConfig;
		if(is_null($domainConfig)) {
			$domainConfig = self::getConfig(DomainConfig::NAME);
			if(!$domainConfig) {
				trigger_error('Hey dude, you might want to configure me ...', E_USER_ERROR);
			}
		}
		return $domainConfig;
	}
	/**
	 * get all the module resources
	 *
	 * @return Foomo\Modules\Resource[]
	 */
	public static function getResources()
	{
		return array(
			\Foomo\Modules\Resource\Module::getResource('Foomo.Services', self::VERSION),
			// get a run mode independent folder var/<runMode>/test
			// \Foomo\Modules\Resource\Fs::getVarResource(\Foomo\Modules\Resource\Fs::TYPE_FOLDER, 'test'),
			// and a file in it
			// \Foomo\Modules\Resource\Fs::getVarResource(\Foomo\Modules\Resource\Fs::TYPE_File, 'test' . DIRECTORY_SEPARATOR . 'someFile'),
			// request a cache resource
			// \Foomo\Modules\Resource\Fs::getCacheResource(\Foomo\Modules\Resource\Fs::TYPE_FOLDER, 'navigationLeaves'),
			// a database configuration
			\Foomo\Modules\Resource\Config::getResource(self::NAME, 'Foomo.Services.broadcast'),
			\Foomo\Modules\Resource\Config::getResource(self::NAME, 'Foomo.Flash.vendorConfig')
		);
	}
	public static function getBrodcasterSrcDir($generatorClassname, $broadcasterClassname)
	{
		$pathname = self::getTempDir('broadcasterSrcDir' . DIRECTORY_SEPARATOR . self::getClassBasename($generatorClassname) . DIRECTORY_SEPARATOR . str_replace('\\', '-', $broadcasterClassname));
		return $pathname;
	}
	private static function getClassBasename($classname)
	{
		return basename(str_replace('\\', DIRECTORY_SEPARATOR, $classname));
	}
}