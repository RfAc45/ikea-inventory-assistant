<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>IKEA Smart Inventory Assistant</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --ikea-blue:       #003087;
      --ikea-blue-mid:   #0051a2;
      --ikea-blue-light: #1a6bbf;
      --ikea-yellow:     #FFDA1A;
      --ikea-yellow-dim: #f0c800;
      --bg:              #f5f5f0;
      --surface:         #ffffff;
      --surface2:        #f0efe9;
      --border:          #ddddd5;
      --text:            #111111;
      --text-muted:      #555550;
      --text-light:      #888880;
      --success:         #1a7a3c;
      --warn:            #c87000;
      --danger:          #c0392b;
      --radius:          8px;
    }

    html, body { height: 100%; }
    body {
      font-family: 'Noto Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      flex-direction: column;
    }

    /* ── Header ── */
    header {
      background: var(--ikea-blue);
      padding: 0 24px;
      display: flex;
      align-items: center;
      gap: 16px;
      flex-shrink: 0;
      height: 64px;
    }
    .ikea-wordmark {
      background: var(--ikea-yellow);
      color: var(--ikea-blue);
      font-size: 20px;
      font-weight: 900;
      letter-spacing: 3px;
      padding: 5px 12px;
      border-radius: 4px;
      flex-shrink: 0;
      font-family: 'Arial Black', sans-serif;
    }
    .header-divider {
      width: 1px; height: 28px;
      background: rgba(255,255,255,0.2);
    }
    .header-title {
      color: white;
      font-size: 15px;
      font-weight: 600;
    }
    .header-sub {
      color: rgba(255,255,255,0.6);
      font-size: 12px;
      margin-top: 1px;
    }
    .status {
      margin-left: auto;
      display: flex; align-items: center; gap: 7px;
      background: rgba(255,255,255,0.1);
      border-radius: 20px;
      padding: 5px 12px;
      font-size: 12px;
      color: rgba(255,255,255,0.8);
    }
    .status-dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--ikea-yellow);
      box-shadow: 0 0 6px var(--ikea-yellow);
      animation: pulse 2s infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

    /* ── Layout ── */
    .main { display: flex; flex: 1; overflow: hidden; }

    /* ── Sidebar ── */
    .sidebar {
      width: 230px; flex-shrink: 0;
      background: var(--ikea-blue);
      padding: 16px 12px;
      display: flex; flex-direction: column; gap: 4px;
      overflow-y: auto;
    }
    .sidebar-label {
      font-size: 10px; font-weight: 700;
      letter-spacing: 0.12em; text-transform: uppercase;
      color: rgba(255,255,255,0.4);
      padding: 4px 10px; margin: 10px 0 4px;
    }
    .sidebar-label:first-child { margin-top: 0; }
    .sug-btn {
      width: 100%; text-align: left;
      background: transparent;
      border: 1px solid transparent;
      border-radius: 6px; padding: 9px 12px;
      font-size: 13px; color: rgba(255,255,255,0.75);
      cursor: pointer; transition: all 0.15s;
      display: flex; align-items: center; gap: 9px;
      font-family: inherit;
    }
    .sug-btn:hover {
      background: rgba(255,218,26,0.12);
      border-color: rgba(255,218,26,0.25);
      color: var(--ikea-yellow);
    }
    .sug-icon { font-size: 15px; flex-shrink: 0; }

    /* ── Chat area ── */
    .chat-wrap { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    #chat {
      flex: 1; overflow-y: auto; padding: 24px 28px;
      display: flex; flex-direction: column; gap: 16px;
      scroll-behavior: smooth;
    }
    #chat::-webkit-scrollbar { width: 5px; }
    #chat::-webkit-scrollbar-track { background: transparent; }
    #chat::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

    /* ── Messages ── */
    .msg-wrap { display: flex; flex-direction: column; max-width: 78%; }
    .msg-wrap.user { align-self: flex-end; align-items: flex-end; }
    .msg-wrap.bot  { align-self: flex-start; align-items: flex-start; }
    .msg-label {
      font-size: 11px; color: var(--text-light);
      margin-bottom: 5px; padding: 0 4px;
      display: flex; align-items: center; gap: 6px;
    }
    .msg-label-dot {
      width: 16px; height: 16px; border-radius: 3px;
      background: var(--ikea-blue);
      display: flex; align-items: center; justify-content: center;
      font-size: 9px; font-weight: 900; color: var(--ikea-yellow);
      letter-spacing: 0;
      font-family: 'Arial Black', sans-serif;
    }
    .bubble {
      padding: 12px 16px; border-radius: var(--radius);
      font-size: 14px; line-height: 1.65; white-space: pre-wrap;
      word-break: break-word;
    }
    .bubble.user {
      background: var(--ikea-blue);
      color: white;
      border-bottom-right-radius: 2px;
    }
    .bubble.bot {
      background: var(--surface);
      border: 1px solid var(--border);
      color: var(--text);
      border-bottom-left-radius: 2px;
    }

    /* ── Typing ── */
    .typing {
      align-self: flex-start;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius); border-bottom-left-radius: 2px;
      padding: 14px 18px;
      display: flex; gap: 5px; align-items: center;
    }
    .typing span {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--ikea-blue);
      animation: bounce 1.2s infinite; display: inline-block;
    }
    .typing span:nth-child(2) { animation-delay: 0.2s; }
    .typing span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes bounce {
      0%,60%,100%{transform:translateY(0);opacity:0.4}
      30%{transform:translateY(-7px);opacity:1}
    }

    /* ── Welcome ── */
    .welcome {
      align-self: center; text-align: center;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 12px; padding: 32px 36px;
      max-width: 440px; margin: auto;
    }
    .welcome-badge {
      display: inline-block;
      background: var(--ikea-yellow);
      color: var(--ikea-blue);
      font-size: 22px; font-weight: 900;
      letter-spacing: 4px;
      padding: 6px 16px; border-radius: 4px;
      margin-bottom: 16px;
      font-family: 'Arial Black', sans-serif;
    }
    .welcome h2 { font-size: 17px; font-weight: 700; margin-bottom: 8px; color: var(--ikea-blue); }
    .welcome p  { font-size: 13px; color: var(--text-muted); line-height: 1.6; }

    /* ── Input ── */
    .input-area {
      padding: 14px 24px 18px;
      background: var(--surface);
      border-top: 2px solid var(--ikea-yellow);
      flex-shrink: 0;
    }
    .input-row {
      display: flex; gap: 10px; align-items: flex-end;
      background: var(--surface2);
      border: 1.5px solid var(--border);
      border-radius: 8px; padding: 8px 8px 8px 16px;
      transition: border-color 0.2s;
    }
    .input-row:focus-within { border-color: var(--ikea-blue); }
    #msg-input {
      flex: 1; background: transparent; border: none; outline: none;
      color: var(--text); font-size: 14px; resize: none;
      max-height: 120px; min-height: 24px; line-height: 1.5;
      font-family: inherit;
    }
    #msg-input::placeholder { color: var(--text-light); }
    #send-btn {
      width: 38px; height: 38px; border-radius: 6px; flex-shrink: 0;
      background: var(--ikea-blue);
      border: none; cursor: pointer; color: var(--ikea-yellow);
      font-size: 16px; font-weight: bold;
      display: flex; align-items: center; justify-content: center;
      transition: transform 0.15s, background 0.15s;
    }
    #send-btn:hover { background: var(--ikea-blue-mid); transform: scale(1.05); }
    #send-btn:active { transform: scale(0.97); }
    #send-btn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }
    .input-hint { font-size: 11px; color: var(--text-light); margin-top: 7px; text-align: center; }
  </style>
</head>
<body>

<header>
  <div class="ikea-wordmark">IKEA</div>
  <div class="header-divider"></div>
  <div>
    <div class="header-title">Smart Inventory Assistant</div>
    <div class="header-sub">Gestión inteligente de inventario</div>
  </div>
  <div class="status">
    <div class="status-dot"></div>
    Airtable · en tiempo real
  </div>
</header>

<div class="main">

  <aside class="sidebar">
    <div class="sidebar-label">Inventario</div>
    <button class="sug-btn" onclick="send('¿Qué productos tienen bajo stock?')">
      <span class="sug-icon">⚠️</span> Bajo stock
    </button>
    <button class="sug-btn" onclick="send('Mostrar productos críticos')">
      <span class="sug-icon">🚨</span> Productos críticos
    </button>
    <button class="sug-btn" onclick="send('Consultar estado del inventario')">
      <span class="sug-icon">📊</span> Estado general
    </button>

    <div class="sidebar-label">Categorías IKEA</div>
    <button class="sug-btn" onclick="send('Mostrar productos de Almacenamiento')">
      <span class="sug-icon">🗄️</span> Almacenamiento
    </button>
    <button class="sug-btn" onclick="send('Mostrar sillas y sillones')">
      <span class="sug-icon">🪑</span> Sillas y sillones
    </button>
    <button class="sug-btn" onclick="send('Mostrar productos de Iluminación')">
      <span class="sug-icon">💡</span> Iluminación
    </button>
    <button class="sug-btn" onclick="send('Mostrar productos de Organización')">
      <span class="sug-icon">📦</span> Organización
    </button>
    <button class="sug-btn" onclick="send('Mostrar productos de Cocina')">
      <span class="sug-icon">🍽️</span> Cocina
    </button>

    <div class="sidebar-label">Operaciones</div>
    <button class="sug-btn" onclick="send('Registrar incidencia operativa')">
      <span class="sug-icon">🔧</span> Registrar incidencia
    </button>
    <button class="sug-btn" onclick="send('¿Cuánto vale el inventario total?')">
      <span class="sug-icon">💰</span> Valor del inventario
    </button>
    <button class="sug-btn" onclick="send('¿Qué productos necesitan reposición?')">
      <span class="sug-icon">🔄</span> Reposición
    </button>
  </aside>

  <div class="chat-wrap">
    <div id="chat">
      <div class="welcome">
        <div class="welcome-badge">IKEA</div>
        <h2>Smart Inventory Assistant</h2>
        <p>Hola, soy tu asistente de inventario. Puedo consultar el stock en tiempo real, identificar productos críticos, calcular valores y registrar incidencias operativas. ¿En qué te ayudo?</p>
      </div>
    </div>

    <div class="input-area">
      <div class="input-row">
        <textarea id="msg-input" rows="1"
          placeholder="Ej: ¿Qué productos tienen bajo stock?"
          oninput="autoResize(this)"
          onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMsg();}">
        </textarea>
        <button id="send-btn" onclick="sendMsg()" title="Enviar">➤</button>
      </div>
      <p class="input-hint">Enter para enviar · Shift+Enter para nueva línea</p>
    </div>
  </div>

</div>

<script>
  const chatEl    = document.getElementById('chat');
  const inputEl   = document.getElementById('msg-input');
  const sendBtn   = document.getElementById('send-btn');
  const history   = [];
  let   isLoading = false;

  function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
  }

  function addMsg(text, role) {
    const welcome = chatEl.querySelector('.welcome');
    if (welcome) welcome.remove();

    const wrap = document.createElement('div');
    wrap.className = `msg-wrap ${role}`;

    if (role === 'bot') {
      const label = document.createElement('div');
      label.className = 'msg-label';
      label.innerHTML = '<div class="msg-label-dot">IK</div> IKEA Assistant';
      wrap.appendChild(label);
    }

    const bubble = document.createElement('div');
    bubble.className = `bubble ${role}`;
    bubble.textContent = text;
    wrap.appendChild(bubble);
    chatEl.appendChild(wrap);
    chatEl.scrollTop = chatEl.scrollHeight;
  }

  function addTyping() {
    const div = document.createElement('div');
    div.className = 'typing';
    div.innerHTML = '<span></span><span></span><span></span>';
    chatEl.appendChild(div);
    chatEl.scrollTop = chatEl.scrollHeight;
    return div;
  }

  async function send(text) {
    if (!text.trim() || isLoading) return;
    isLoading = true;
    sendBtn.disabled = true;
    inputEl.value = '';
    inputEl.style.height = 'auto';

    addMsg(text, 'user');
    history.push({ role: 'user', content: text });
    const typing = addTyping();

    try {
      const res = await fetch('/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ message: text, history })
      });
      const data = await res.json();
      typing.remove();
      const reply = data.reply || 'Error al obtener respuesta.';
      addMsg(reply, 'bot');
      history.push({ role: 'assistant', content: reply });
    } catch (e) {
      typing.remove();
      addMsg('Error de conexión. Verifica tu red e intenta de nuevo.', 'bot');
    } finally {
      isLoading = false;
      sendBtn.disabled = false;
      inputEl.focus();
    }
  }

  function sendMsg() { send(inputEl.value.trim()); }
</script>

</body>
</html>
