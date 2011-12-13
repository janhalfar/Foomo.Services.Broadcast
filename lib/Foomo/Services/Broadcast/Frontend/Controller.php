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
		$generator = new $generator($broadcaster);
		$generator->generateReceiver();
	}
}