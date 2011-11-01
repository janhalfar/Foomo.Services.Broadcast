<?php

var_dump(\Foomo\Services\Broadcast::getClients());

Foomo\Log\Logger::transactionBegin('viewContent', $comment = '/de/DAMEN');
Foomo\Log\Logger::transactionComplete('viewContent', $comment = '/de/DAMEN');

$chatService = new \Foomo\Services\Broadcast\Mock\Chat\Service();

for($i=0;$i<100;$i++) {
	Foomo\Timer::start('broadcast');
	// usleep(10000);
	$chatService->join(str_repeat('Hans_Peter_' . $i . ' löäü üäöößßß ßßß => ' .  uniqid(), rand(1, 500)));
	ob_flush();
	flush();
	Foomo\Timer::stop('broadcast');
}


?><pre>
<?= Foomo\Timer::getStats();