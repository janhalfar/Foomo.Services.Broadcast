<?

use Foomo\Services\Reflection\ServiceObjectType;
use Foomo\Flash\ActionScript\PHPUtils;

?>
package <?= $model['broadcaster']->getRemotePackage()  ?>

{

	import flash.events.Event;
	import org.foomo.rpc.broadcast.RPCReceiverClient;
	import org.foomo.rpc.broadcast.GenericRPCReceiver;
	import org.foomo.rpc.broadcast.events.IncomingDataEvent;	
	import <?= $model['broadcaster']->getRemotePackage()  ?>.events.*;
	
	// data VOs
<? 
$importedTypes = array();
foreach($model['events'] as $event): 
	$eventTypeObj = new ServiceObjectType($event->type);
	$import = $eventTypeObj->getRemotePackage() . '.' . PHPUtils::getASType($event->type);
?>
	[Event(name="<?= str_replace('\\', '_', $model['broadcaster']->type) . '_' . $event->name ?>", type="<?= $model['broadcaster']->getRemotePackage()  ?>.events.<?= ucfirst($event->name) ?>Event")]
<?

if(PHPUtils::isASStandardType($event->type)) {
	continue;
}

?>
	import <?= $import ?>;
<? endforeach; ?>


	public class Receiver extends GenericRPCReceiver
	{
		public function Receiver(receiverClient:RPCReceiverClient)
		{
			super(receiverClient);
		}	
		override protected function handleIncoming(event:IncomingDataEvent):void
		{
			switch(event.name) {
<? 
					foreach($model['events'] as $event): 
						$eventTypeObj = new ServiceObjectType($event->type);
				?>
				case <?= ucfirst($event->name) ?>Event.<?= PHPUtils::camelCaseToConstant($event->name) ?>:
					var <?= $event->name ?>Event:<?= ucfirst($event->name) ?>Event = new <?= ucfirst($event->name) ?>Event(event.data as <?= PHPUtils::getASType($event->type) ?>);
					this.dispatchEvent(<?= $event->name ?>Event);
					break;
<? endforeach; ?>
				default:
						trace('unknown incoming broadcast : ' + event.name);
			}
		}
	}
}
