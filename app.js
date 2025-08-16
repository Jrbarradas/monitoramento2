const socket = io('ws://localhost:3001');

function setupWebSocket() {
  socket.on('connect', () => {
    console.log('Conectado ao WebSocket');
  });

  socket.on('statusUpdate', (data) => {
    updateUI(data);
  });

  // Solicitar atualização inicial
  const ips = Array.from(document.querySelectorAll('[data-ip]'))
    .map(el => el.dataset.ip);
  socket.emit('requestUpdate', ips);
}

function updateUI(data) {
  data.forEach(status => {
    const element = document.querySelector(`[data-ip="${status.ip}"]`);
    if(element) {
      element.classList.remove('online', 'offline');
      element.classList.add(status.status);
      element.querySelector('.status-text').textContent = status.status.toUpperCase();
    }
  });
}
