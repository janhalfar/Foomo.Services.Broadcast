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
		\Foomo\MVC::abort();
		$generator = new $generator($broadcaster);
		$generator->generateReceiver();
		exit;
	}
}