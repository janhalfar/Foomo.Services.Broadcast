<?php
/* @var $model \Foomo\Services\Broadcast\Frontend\Model */
/* @var $view \Foomo\MVC\View */
$generators = \Foomo\Services\Broadcast\Utils::getReceiverGenerators();
?>
<h1>Available brodcasters in this site</h1>
<i>This is an experimental project</i>
<ul>
	<? foreach(\Foomo\Services\Broadcast\Utils::getBroadcasters() as $broadcasterClass): ?>
		<li>
			<?= $broadcasterClass ?>
			<? foreach($generators as $generator): ?>
				<p>
					<?= $view->link('generate a receiver with ' . $generator, 'generateReceiver', array($generator, $broadcasterClass)) ?>
				</p>
			<? endforeach; ?>
			<ul>
				<? 
					/* @var $event type */
					foreach(\Foomo\Services\Broadcast\Utils::getBroadcasterEvents($broadcasterClass) as $event): 
				?>
					<li>
						name: <?= $event->name ?>, type: <?= $event->type ?>
					</li>
				<? endforeach; ?>
			</ul>
		</li>
	<? endforeach; ?>
</ul>
