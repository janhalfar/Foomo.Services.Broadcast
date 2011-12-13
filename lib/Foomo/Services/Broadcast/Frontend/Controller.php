<?php

namespace Foomo\Services\Broadcast\Frontend;

class Controller {
	/**
	 * @var Model
	 */
	public $model;
	public function actionDefault() {}
	public function actionGenerateReceiver($generator, $broadcaster)
	{
		$refl = new \ReflectionClass($generator);
		if(($refl instanceof \ReflectionClass) && !$refl->isAbstract() && $refl->isSubclassOf('Foomo\\Services\\Broadcast\\ReceiverGenerator\\AbstractGenerator')) {
			$generator = new $generator($broadcaster);
			$generator->generateReceiver();
		} else {
			throw new \Exception('invalid generator class ' . $generator);
		}
	}
}