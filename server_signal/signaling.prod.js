import http from "http";
import { Server } from "socket.io";

const PORT = process.env.PORT || 3000;

// Spin up a plain HTTP server (Nginx handles the external SSL/HTTPS layers)
const server = http.createServer((req, res) => {
    res.writeHead(200);
    res.end("Signaling server running internally over HTTP\n");
});

const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"],
    },
});

io.on("connection", (socket) => {
    console.log("Device connected:", socket.id);

    socket.on("join", (roomId) => {
        socket.join(roomId);
        console.log(`Device ${socket.id} joined room: ${roomId}`);
        socket.to(roomId).emit("new-user", socket.id);
    });

    socket.on("signal", (data) => {
        socket.to(data.to).emit("signal", {
            from: socket.id,
            signal: data.signal,
        });
    });

    socket.on("disconnect", () => {
        console.log("Device disconnected:", socket.id);
    });
});

server.listen(PORT, "0.0.0.0", () => {
    console.log(
        `🚀 Production Signaling Server processing internally on port ${PORT}`,
    );
});
