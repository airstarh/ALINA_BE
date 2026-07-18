import http from "http";
import { Server } from "socket.io";

const PORT = process.env.PORT || 3000;

const server = http.createServer((req, res) => {
    res.writeHead(200, { "Content-Type": "text/plain" });
    res.end("Signaling server OK");
});

const io = new Server(server, {
    // allowEIO3: true,  <-- ЭТО СТРОКУ УДАЛИТЬ ОБЯЗАТЕЛЬНО
    transports: ["websocket", "polling"],
    cors: {
        origin: [
            "https://zero.home:50443",
            "https://192.168.1.86:50443",
            "https://localhost:8083",
        ],
        methods: ["GET", "POST"],
        credentials: true,
    },
});

io.on("connection", (socket) => {
    console.log("Client connected:", socket.id);

    // Обрабатываем событие точно так, как его шлет твой Vue компонент
    socket.on("simple-signal[discover]", (payload) => {
        // 1. Определяем roomId из payload (поддерживаем разные форматы, которые может слать клиент)
        let roomId = "channel_1";
        if (typeof payload === "string" && payload.length > 0) {
            roomId = payload;
        } else if (Array.isArray(payload) && payload.length > 0) {
            roomId = payload;
        } else if (payload && typeof payload === "object") {
            roomId = payload.roomId || payload.id || "channel_1";
        }

        // 2. Присоединяем сокет к комнате
        socket.join(roomId);
        console.log(`[${socket.id}] Joined room: ${roomId}`);

        // 3. Получаем актуальный список всех участников комнаты
        // adapter.rooms.get(roomId) возвращает Set с ID всех сокетов в комнате
        const roomSet = io.sockets.adapter.rooms.get(roomId);
        const clientsInRoom = roomSet ? Array.from(roomSet) : [];

        // Фильтруем себя из списка пиров (чтобы не пытаться соединиться сам с собой)
        const existingPeers = clientsInRoom.filter((id) => id !== socket.id);

        // 4. Формируем ответ для самого себя (нового пользователя)
        // Для первого пользователя peers будет [] (пустой массив) — это нормально и безопасно для Vue-компонента
        const myResponse = {
            id: socket.id,
            roomId: roomId,
            peers: existingPeers,
        };
        socket.emit("simple-signal[discover]", myResponse);

        // 5. Формируем ответ для всех остальных (чтобы они узнали о новом участнике)
        // ВАЖНО: Мы отправляем им ОБНОВЛЕННЫЙ список всех пиров, включая нового пользователя
        const othersResponse = {
            id: socket.id,
            roomId: roomId,
            peers: [...existingPeers, socket.id],
        };

        // Рассылаем остальным
        existingPeers.forEach((peerId) => {
            io.to(peerId).emit("simple-signal[discover]", othersResponse);
        });
    });

    socket.on("simple-signal[signal]", (data) => {
        if (!data || !data.to) return;
        io.to(data.to).emit("simple-signal[signal]", {
            from: socket.id,
            signal: data.signal,
        });
    });

    socket.on("disconnect", () => {
        console.log("Client disconnected:", socket.id);
    });
});

server.listen(PORT, "0.0.0.0", () => {
    console.log(`Server listening on port ${PORT}`);
});
