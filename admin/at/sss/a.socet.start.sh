# Установи socat (если нет)
# sudo apt install -y socat

# Пробросим localhost:3306 на контейнер (только для localhost!)
socat TCP-LISTEN:3306,bind=127.0.0.1,reuseaddr,fork \
       TCP:$(docker inspect -f '{{range.NetworkSettings.Networks}}{{.IPAddress}}{{end}}' alina_mysql):3306 &
