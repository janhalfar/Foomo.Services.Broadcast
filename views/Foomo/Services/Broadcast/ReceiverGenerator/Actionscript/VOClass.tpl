<?php

use Foomo\Flash\ActionScript\PHPUtils;
ini_set('html_errors', 'Off');

/* @var $model Foomo\Services\Reflection\ServiceObjectType */
/* @var $prop Foomo\Services\Reflection\ServiceObjectType */

?>
package <?= $model->getRemotePackage() ?>

{
<? 
$imports = array();
foreach($model->props as $propName => $prop) {
	if(PHPUtils::isASStandardType($prop->type) || $prop->type == $model->type) {
		continue;
	}
	$imports[] = $prop->getRemotePackage() . '.' . PHPUtils::getASType($prop->type);
}
$imports = array_unique($imports);
foreach($imports as $import):
?>
	import <?= $import ?>;
<? endforeach; ?>
<? 
$alias = str_replace('\\', '.', $model->type);
if ('' != $remoteClass = $model->getRemoteClass()):?>
	// this class is "abstract" - use  <?= $remoteClass ?>

	// and copy this to <?= $remoteClass ?> [RemoteClass(alias="<?= $alias ?>")]
<? else: ?>
	[RemoteClass(alias='<?= $alias ?>')]
<? endif; ?>

	[Bindable]
	public class <?= PHPUtils::getASType($model->type) ?> {
	
<? 
	foreach($model->constants as $constName => $const): 
?>
		public static const <?= $constName ?>:String = '<?= $const ?>';
<? endforeach; ?>

<? 
	foreach($model->props as $propName => $prop): 
?>
<? if($prop->isArrayOf): ?>
		public var <?= $propName ?>:Array;
<? else: ?>
		public var <?= $propName ?>:<?= PHPUtils::getASType($prop->type) ?>;
<? endif ?>
<? endforeach; ?>

	}
}
