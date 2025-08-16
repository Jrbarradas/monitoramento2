const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const app = express();
const server = http.createServer(app);
const io = new Server(server, {
  cors: {
    origin: "*"
  }
});

// Simulação de dados de status
let statusCache = {};

io.on('connection', (socket) => {
  console.log('Cliente conectado');
  
  socket.on('requestUpdate', (ips) => {
    // Lógica de verificação em lote
    const batchResults = checkIPBatch(ips);
    socket.emit('statusUpdate', batchResults);
  });

  socket.on('disconnect', () => {
    console.log('Cliente desconectado');
  });
});

function checkIPBatch(ips) {
  // Implementação do ping em lote
  return ips.map(ip => ({
    ip,
    status: Math.random() > 0.2 ? 'online' : 'offline',
    timestamp: Date.now()
  }));
}

server.listen(3001, () => {
  console.log('WebSocket server running on port 3001');
});
