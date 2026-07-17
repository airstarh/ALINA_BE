#! /bin/bash

docker logs -f alina_signaling
docker exec -it alina_nginx curl -I http://signaling:3000/socket.io/


