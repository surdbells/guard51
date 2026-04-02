/**
 * Guard51 WebSocket Server
 * Handles: guard-location, panic-alerts, dispatch, chat
 * Auth: JWT token in connection URL query param
 */
require('./load-env');
const { WebSocketServer, WebSocket } = require('ws');
const jwt = require('jsonwebtoken');
const http = require('http');

const PORT = process.env.WS_PORT || 8089;
const JWT_SECRET = process.env.JWT_SECRET || 'guard51_jwt_secret';

// In-memory channel subscriptions
const channels = new Map(); // channel -> Set<ws>
const clients = new Map();  // ws -> { userId, tenantId, role }

const server = http.createServer((req, res) => {
  if (req.url === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ status: 'ok', connections: clients.size, channels: channels.size }));
    return;
  }
  res.writeHead(404);
  res.end();
});

const wss = new WebSocketServer({ server });

wss.on('connection', (ws, req) => {
  // Extract JWT from query string
  const url = new URL(req.url, `http://localhost:${PORT}`);
  const token = url.searchParams.get('token');

  if (!token) {
    ws.close(4001, 'Missing token');
    return;
  }

  let user;
  try {
    user = jwt.verify(token, JWT_SECRET);
  } catch (e) {
    ws.close(4002, 'Invalid token');
    return;
  }

  const meta = { userId: user.user_id || user.sub, tenantId: user.tenant_id, role: user.role };
  clients.set(ws, meta);

  // Auto-subscribe to tenant channels
  if (meta.tenantId) {
    subscribe(ws, `tenant:${meta.tenantId}:location`);
    subscribe(ws, `tenant:${meta.tenantId}:alerts`);
    subscribe(ws, `tenant:${meta.tenantId}:dispatch`);
  }

  console.log(`[WS] Connected: ${meta.userId} (${meta.role}) tenant:${meta.tenantId}. Total: ${clients.size}`);

  ws.on('message', (raw) => {
    try {
      const msg = JSON.parse(raw.toString());
      handleMessage(ws, meta, msg);
    } catch (e) {
      ws.send(JSON.stringify({ error: 'Invalid JSON' }));
    }
  });

  ws.on('close', () => {
    // Remove from all channels
    for (const [ch, subs] of channels) {
      subs.delete(ws);
      if (subs.size === 0) channels.delete(ch);
    }
    clients.delete(ws);
    console.log(`[WS] Disconnected: ${meta.userId}. Total: ${clients.size}`);
  });

  ws.send(JSON.stringify({ type: 'connected', userId: meta.userId }));
});

function handleMessage(ws, meta, msg) {
  switch (msg.type) {
    case 'location_update':
      // Guard sends GPS position
      if (meta.role === 'guard' && meta.tenantId) {
        broadcast(`tenant:${meta.tenantId}:location`, {
          type: 'guard_position',
          guard_id: meta.userId,
          lat: msg.lat,
          lng: msg.lng,
          accuracy: msg.accuracy,
          battery: msg.battery,
          timestamp: new Date().toISOString(),
        }, ws); // Exclude sender
      }
      break;

    case 'panic':
      // Guard triggers panic alert
      if (meta.tenantId) {
        broadcast(`tenant:${meta.tenantId}:alerts`, {
          type: 'panic_alert',
          guard_id: meta.userId,
          lat: msg.lat,
          lng: msg.lng,
          message: msg.message || 'PANIC ALERT',
          timestamp: new Date().toISOString(),
        });
        console.log(`[PANIC] Guard ${meta.userId} in tenant ${meta.tenantId}`);
      }
      break;

    case 'dispatch_update':
      // Dispatcher sends call update
      if (['company_admin', 'supervisor', 'dispatcher'].includes(meta.role) && meta.tenantId) {
        broadcast(`tenant:${meta.tenantId}:dispatch`, {
          type: 'dispatch_update',
          call_id: msg.call_id,
          status: msg.status,
          assigned_to: msg.assigned_to,
          timestamp: new Date().toISOString(),
        });
      }
      break;

    case 'subscribe':
      if (msg.channel && msg.channel.startsWith(`tenant:${meta.tenantId}:`)) {
        subscribe(ws, msg.channel);
      }
      break;

    case 'ping':
      ws.send(JSON.stringify({ type: 'pong' }));
      break;
  }
}

function subscribe(ws, channel) {
  if (!channels.has(channel)) channels.set(channel, new Set());
  channels.get(channel).add(ws);
}

function broadcast(channel, data, exclude = null) {
  const subs = channels.get(channel);
  if (!subs) return;
  const msg = JSON.stringify(data);
  for (const ws of subs) {
    if (ws !== exclude && ws.readyState === WebSocket.OPEN) {
      ws.send(msg);
    }
  }
}

server.listen(PORT, () => {
  console.log(`[Guard51 WS] Running on port ${PORT}`);
  console.log(`[Guard51 WS] Health: http://localhost:${PORT}/health`);
});
