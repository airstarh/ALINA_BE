<?php

declare(strict_types=1);

namespace alina\Services\FileJob;

final class FileJobWakeClient
{
    public function wake(string $publicId,string $secret,string $address='tcp://alina_socket:8083'):bool
    {
        $socket=@stream_socket_client($address,$errno,$error,1.0);
        if (!is_resource($socket)) return false;
        stream_set_timeout($socket,1);
        fwrite($socket,json_encode(['publicId'=>$publicId,'secret'=>$secret],JSON_UNESCAPED_SLASHES)."\n");
        $response=fgets($socket,4096);fclose($socket);
        $decoded=is_string($response)?json_decode($response,true):null;
        return is_array($decoded)&&($decoded['ok']??false)===true;
    }
}
