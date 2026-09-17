<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../Services/Chat/ChatServer.php';
class HistoryClient implements \Ratchet\ConnectionInterface {
 public array $received=[];
 public function __construct(public int $resourceId){}
 public function send($data){$this->received[]=$data;return $this;}
 public function close(){}
 public function history():array{
  foreach(array_reverse($this->received) as $raw){$data=json_decode($raw,true);if(($data['type']??'')==='history')return $data['messages'];}
  return [];
 }
}
function event($server,$client,$type,$extra=[]){$server->onMessage($client,json_encode(['version'=>2,'type'=>$type,'channel'=>'history-review','CurrentUser'=>['id'=>17],...$extra]));}
$loop=new \React\EventLoop\StreamSelectLoop();
$scheduledTimers=(new ReflectionProperty($loop,'timers'))->getValue($loop);
$server=new \alina\Services\Chat\ChatServer($loop);
$a=new HistoryClient(1);$b=new HistoryClient(2);$replacement=new HistoryClient(3);
$server->onOpen($a);event($server,$a,'join');
for($i=0;$i<55;$i++)event($server,$a,'message',['msg'=>"message $i"]);
$server->onOpen($b);event($server,$b,'join');
$server->onClose($a);
$server->onOpen($replacement);event($server,$replacement,'join');
if(count($replacement->history())!==50)throw new RuntimeException('FAIL: reconnect with another person online lost history');
echo "PASS: reconnect retains last 50 messages while another person stays online\n";
$server->onClose($b);$server->onClose($replacement);
$timersProperty=new ReflectionProperty($server,'roomCleanupTimers');
$cleanup=$timersProperty->getValue($server)['history-review'];
if(!$scheduledTimers->contains($cleanup))throw new RuntimeException('FAIL: empty room has no delayed cleanup');
$returning=new HistoryClient(4);$server->onOpen($returning);event($server,$returning,'join');
if(count($returning->history())!==50)throw new RuntimeException('FAIL: a brief gap with no sockets erased all 50 messages');
echo "PASS: last connection can reconnect through a brief empty-room gap\n";

if($scheduledTimers->contains($cleanup))throw new RuntimeException('FAIL: returning connection did not cancel cleanup');
echo "PASS: reconnect cancels pending room cleanup\n";
$server->onClose($returning);
$cleanup=$timersProperty->getValue($server)['history-review'];
if($cleanup->getInterval()!==120.0)throw new RuntimeException('FAIL: cleanup does not allow the two-minute grace period');
($cleanup->getCallback())($cleanup);
$nextVisit=new HistoryClient(5);$server->onOpen($nextVisit);event($server,$nextVisit,'join');
if(count($nextVisit->history())!==0)throw new RuntimeException('FAIL: history remains after empty-room cleanup');
echo "PASS: history clears after the room stays empty for the grace period\n";
