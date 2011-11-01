<?php

use Foomo\Flash\ActionScript\PHPUtils;

// ini_set('html_errors', 'Off');
// var_dump($model);

$typeObj = new Foomo\Services\Reflection\ServiceObjectType($model['event']->type);


?>
package <?= $model['broadcaster']->getRemotePackage()  ?>.events

{

	import flash.events.Event;
	import <?= $typeObj->getRemotePackage() . '.' . PHPUtils::getASType($model['event']->type) ?>;
	
	public class <?= ucfirst($model['event']->name) ?>Event extends Event
	{
		public static const <?= strtoupper($model['event']->name) ?>:String = "<?= str_replace('\\', '_', $model['broadcaster']->type) . '_' . $model['event']->name ?>";
		public var data:<?= PHPUtils::getASType($model['event']->type) ?>;
		public function <?= ucfirst($model['event']->name) ?>Event(data:<?= PHPUtils::getASType($model['event']->type) ?>)
		{
			super(<?= strtoupper($model['event']->name) ?>);
			this.data = data;
		}
		
		override public function clone():Event
		{
			return new <?= ucfirst($model['event']->name) ?>Event(this.data);
		}
	}
}
