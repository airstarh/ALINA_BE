import http from "http";
import { Server } from "socket.io";

const PORT = process.env.PORT || 3000;

const server = http.createServer((req, res) => {
    res.writeHead(200);
    res.end("Signaling server running internally over HTTP\n");
});

// FIXED: Added precise port mapping and disabled the node client version validator check
const io = new Server(server, {
    allowEIO3: true, // Force backward compatibility with the older vue-webrtc client engine
    cors: {
        origin: [
            "https://zero.home:56443",
            "https://192.168.1.86:56443",
            "https://localhost:8083",
        ],
        methods: ["GET", "POST"],
        credentials: true,
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
