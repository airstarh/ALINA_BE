<?php

declare(strict_types=1);

require_once __DIR__ . '/../Services/FileJob/WakeRequest.php';

use alina\Services\FileJob\WakeRequest;

function wakeExpect(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$request=WakeRequest::parse("{\"publicId\":\"job-1\",\"secret\":\"wake-secret\"}\n");
wakeExpect($request->publicId==='job-1'&&$request->secret==='wake-secret','valid wake frame must parse');
foreach (["not-json\n",json_encode(['publicId'=>'../bad','secret'=>'x'])."\n",str_repeat('a',4097)] as $bad) {
    $rejected=false;try{WakeRequest::parse($bad);}catch(InvalidArgumentException){$rejected=true;}
    wakeExpect($rejected,'malformed or oversized wake frame must be rejected');
}
echo "PASS: file job runner wake protocol\n";
