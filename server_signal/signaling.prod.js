import fs from 'fs';
import https from 'https';
import { Server } from 'socket.io';

// 1. Load your local HTTPS development certificates
const options = {
  key: fs.readFileSync("./_GITOUT/cert/localhost+4-key.pem"),
  cert: fs.readFileSync("./_GITOUT/cert/localhost+4.pem"),
};

// 2. Spin up a secure HTTPS server on port 3000
const server = https.createServer(options, (req, res) => {
  res.writeHead(200);
  res.end("Signaling server running safely over HTTPS\n");
});

const io = new Server(server, {
  cors: {
    origin: "*", // Allows connections from your PC and Phone layout
    methods: ["GET", "POST"]
  }
});

// 3. WebRTC signaling orchestration events
io.on('connection', (socket) => {
  console.log('Device connected:', socket.id);

  socket.on('join', (roomId) => {
    socket.join(roomId);
    console.log(`Device ${socket.id} joined room: ${roomId}`);
    socket.to(roomId).emit('new-user', socket.id);
  });

  socket.on('signal', (data) => {
    // Route WebRTC offer/answer payloads to other room participants
    socket.to(data.to).emit('signal', {
      from: socket.id,
      signal: data.signal
    });
  });

  socket.on('disconnect', () => {
    console.log('Device disconnected:', socket.id);
  });
});

server.listen(3000, '0.0.0.0', () => {
  console.log('🚀 Secure Signaling Server running at https://192.168.1.86:3000');
});
