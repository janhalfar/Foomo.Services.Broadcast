<?php

namespace Foomo\Services\Broadcast;

class Utils {
	public static function getReceiverGenerators()
	{
		$ret = array();
		foreach(\Foomo\AutoLoader::getClassesBySuperClass('Foomo\\Services\\Broadcast\\ReceiverGenerator\\AbstractGenerator') as $broadcasterClass) {
			$ret[] = $broadcasterClass;
		}
		return $ret;
	}
	/**
	 * get all the broadcasters
	 * 
	 * @return array
	 */
	public static function getBroadcasters()
	{
		$ret = array();
		foreach(\Foomo\AutoLoader::getClassesBySuperClass('Foomo\\Services\\Broadcast\\AbstractBroadcaster') as $broadcasterClass) {
			$ret[] = $broadcasterClass;
		}
		return $ret;
	}
	public static function getBroadcasterEvents($broadcasterClass)
	{
		$ret = array();
		$classRefl = new \ReflectionAnnotatedClass($broadcasterClass);
		/* @var $methodRefl \ReflectionAnnotatedMethod */
		foreach($classRefl->getMethods() as $methodRefl) {
			foreach($methodRefl->getAllAnnotations() as $annotation) {
				if($annotation instanceof Event) {
					if(empty($annotation->name)) {
						$annotation->name = $methodRefl->getName();
					}
					$ret[] = $annotation;
				}
			}
		}
		return $ret;
	}
	public function getBroadcasterVOs($broadcasterClass)
	{
		$ret = array();
		/* @var $event Event */
		foreach(self::getBroadcasterEvents($broadcasterClass) as $event) {
			self::addVODependencies($event->type, $ret);
		}
		return $ret;
	}
	private static function addVODependencies($classname, &$deps)
	{
		if(!in_array($classname, $deps)) {
			$deps[] = $classname;
		}
		$classRefl = new \ReflectionClass($classname);
		/* @var $propertyReflection \ReflectionProperty */
		foreach($classRefl->getProperties() as $propertyReflection) {
			if($propertyReflection->isPublic() && !$propertyReflection->isStatic()) {
				$docEntry = new \Foomo\Reflection\PhpDocEntry($propertyReflection->getDocComment());
				if($docEntry->var && $docEntry->var->type) {
					$cleanType = str_replace('[]', '', $docEntry->var->type);
					if(class_exists($cleanType)) {
						self::addVODependencies($cleanType, $deps);
					}
				}
			}
		}
	}
}