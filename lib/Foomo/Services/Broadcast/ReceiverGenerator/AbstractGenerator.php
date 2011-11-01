<?php

namespace Foomo\Services\Broadcast\ReceiverGenerator;

abstract class AbstractGenerator {
	private $srcDir;
	public $broadcasterClassname;
	public function __construct($broadcasterClassname)
	{
		$this->broadcasterClassname = $broadcasterClassname;
		$this->srcDir = \Foomo\Services\Broadcast\Module::getBrodcasterSrcDir(get_called_class(), $this->broadcasterClassname);
	}
	protected function writeSource($relativePathname, $code)
	{
		$fullPathname = $this->srcDir . DIRECTORY_SEPARATOR . $relativePathname;
		$fileResource = \Foomo\Modules\Resource\Fs::getAbsoluteResource(\Foomo\Modules\Resource\Fs::TYPE_FILE, $fullPathname);
		$fileResource->tryCreate();
		file_put_contents($fullPathname, $code);
	}
	abstract public function generateReceiver();
}