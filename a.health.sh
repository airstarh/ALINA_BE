#! /bin/bash

docker logs -f alina_signaling
docker exec -it alina_nginx curl -k -I https://signaling:3000/socket.io/

