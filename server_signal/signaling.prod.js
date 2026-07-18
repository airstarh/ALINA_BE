import http from "http";
import { Server } from "socket.io";

const PORT = process.env.PORT || 3000;

const server = http.createServer((req, res) => {
    res.writeHead(200);
    res.end("SimplePeer Fixed Signaling Server running\n");
});

const io = new Server(server, {
    allowEIO3: true,
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
    console.log("Device connected:", socket.id);

    socket.on("simple-signal[discover]", (payload) => {
        let roomId = "channel_1";
        if (typeof payload === "string" && payload.length > 0) {
            roomId = payload;
        } else if (Array.isArray(payload) && payload.length > 0) {
            roomId = payload[0];
        } else if (payload && typeof payload === "object") {
            roomId = payload.roomId || payload.id || "channel_1";
        }

        socket.join(roomId);
        console.log(
            `🎯 MATCH SUCCESS: Device ${socket.id} joined room: ${roomId}`,
        );

        const clientsInRoom = Array.from(
            io.sockets.adapter.rooms.get(roomId) || [],
        );
        const existingPeers = clientsInRoom.filter((id) => id !== socket.id);

        // THE EXACT FIX: The library reads a dictionary object where peers is an array
        const responseData = {
            id: socket.id,
            roomId: roomId,
            peers: existingPeers,
        };

        // Emit dictionary object format directly to self
        socket.emit("simple-signal[discover]", responseData);

        // Notify existing peers inside the room about the new device footprint signature
        existingPeers.forEach((peerId) => {
            io.to(peerId).emit("simple-signal[discover]", responseData);
        });
    });

    socket.on("simple-signal[signal]", (data) => {
        if (!data || !data.to) return;
        io.to(data.to).emit("simple-signal[signal]", {
            from: socket.id,
            signal: data.signal,
        });
    });

    socket.on("disconnecting", () => {
        socket.rooms.forEach((roomId) => {
            if (roomId !== socket.id) {
                socket
                    .to(roomId)
                    .emit("simple-signal[left]", { id: socket.id });
            }
        });
    });

    socket.on("disconnect", () => {
        console.log("Device disconnected:", socket.id);
    });
});

server.listen(PORT, "0.0.0.0", () => {
    console.log(`🚀 SimplePeer Signaling Server active on port ${PORT}`);
});
