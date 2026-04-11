@extends('layouts.appAdmin')

@section('title', 'Conversaciones Channex')

@section('styles')
<style>
    .channex-chat-container {
        display: flex;
        height: calc(100vh - 140px);
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }

    /* --- Sidebar de conversaciones --- */
    .channex-sidebar {
        width: 360px;
        min-width: 300px;
        border-right: 1px solid #e9ecef;
        display: flex;
        flex-direction: column;
        background: #fff;
    }
    .channex-sidebar-header {
        padding: 16px;
        border-bottom: 1px solid #e9ecef;
        background: #f8f9fa;
    }
    .channex-sidebar-header h5 {
        margin: 0 0 10px 0;
        font-weight: 700;
        color: #2c3e50;
    }
    .channex-search-box {
        position: relative;
    }
    .channex-search-box input {
        width: 100%;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        padding: 8px 16px 8px 36px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
    }
    .channex-search-box input:focus {
        border-color: #4a90d9;
    }
    .channex-search-box .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
        font-size: 14px;
    }
    .channex-conversations-list {
        flex: 1;
        overflow-y: auto;
    }
    .channex-conversation-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f1f3f5;
        transition: background 0.15s;
    }
    .channex-conversation-item:hover {
        background: #e8f4fd;
    }
    .channex-conversation-item.active {
        background: #d0e8f7;
        border-left: 3px solid #4a90d9;
    }
    .channex-conversation-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #4a90d9;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        flex-shrink: 0;
        margin-right: 12px;
    }
    .channex-conversation-info {
        flex: 1;
        min-width: 0;
    }
    .channex-conversation-name {
        font-weight: 600;
        font-size: 14px;
        color: #2c3e50;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .channex-conversation-preview {
        font-size: 13px;
        color: #7f8c8d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .channex-conversation-meta {
        text-align: right;
        flex-shrink: 0;
        margin-left: 8px;
    }
    .channex-conversation-date {
        font-size: 11px;
        color: #95a5a6;
    }
    .channex-conversation-sender-badge {
        display: inline-block;
        font-size: 10px;
        padding: 1px 6px;
        border-radius: 8px;
        margin-top: 4px;
    }
    .badge-guest {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .badge-hotel {
        background: #e3f2fd;
        color: #1565c0;
    }

    /* --- Panel de chat --- */
    .channex-chat-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #f0f2f5;
    }
    .channex-chat-header {
        padding: 14px 20px;
        background: #fff;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
    }
    .channex-chat-header .back-btn {
        display: none;
        border: none;
        background: none;
        font-size: 20px;
        color: #4a90d9;
        margin-right: 10px;
        cursor: pointer;
    }
    .channex-chat-header-info h6 {
        margin: 0;
        font-weight: 700;
        color: #2c3e50;
    }
    .channex-chat-header-info small {
        color: #95a5a6;
        font-size: 12px;
    }
    .channex-chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
    }
    .channex-chat-empty {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #bdc3c7;
        font-size: 16px;
        flex-direction: column;
    }
    .channex-chat-empty i {
        font-size: 60px;
        margin-bottom: 16px;
        color: #dfe6e9;
    }

    /* --- Burbujas de mensajes --- */
    .channex-msg-row {
        display: flex;
        margin-bottom: 8px;
        max-width: 75%;
    }
    .channex-msg-row.msg-guest {
        align-self: flex-start;
    }
    .channex-msg-row.msg-hotel {
        align-self: flex-end;
    }
    .channex-msg-bubble {
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 14px;
        line-height: 1.4;
        position: relative;
        word-wrap: break-word;
    }
    .msg-guest .channex-msg-bubble {
        background: #fff;
        color: #2c3e50;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.06);
    }
    .msg-hotel .channex-msg-bubble {
        background: #d9fdd3;
        color: #2c3e50;
        border-bottom-right-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.06);
    }
    .channex-msg-time {
        font-size: 11px;
        color: #95a5a6;
        margin-top: 4px;
        text-align: right;
    }
    .channex-msg-sender-label {
        font-size: 11px;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .msg-guest .channex-msg-sender-label {
        color: #4a90d9;
    }
    .msg-hotel .channex-msg-sender-label {
        color: #27ae60;
    }

    /* --- Date separator --- */
    .channex-date-separator {
        text-align: center;
        margin: 16px 0;
        position: relative;
    }
    .channex-date-separator span {
        background: #e2e8f0;
        padding: 4px 14px;
        border-radius: 10px;
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }

    /* --- Loading spinner --- */
    .channex-loading {
        display: none;
        text-align: center;
        padding: 30px;
    }
    .channex-loading .spinner-border {
        width: 30px;
        height: 30px;
        color: #4a90d9;
    }

    /* --- Responsive --- */
    @media (max-width: 768px) {
        .channex-chat-container {
            height: calc(100vh - 80px);
        }
        .channex-sidebar {
            width: 100%;
            position: absolute;
            z-index: 10;
            left: 0;
            top: 0;
            height: 100%;
        }
        .channex-chat-panel {
            position: absolute;
            z-index: 5;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        .channex-chat-panel.visible {
            transform: translateX(0);
            z-index: 15;
        }
        .channex-chat-header .back-btn {
            display: inline-block;
        }
        .channex-chat-container {
            position: relative;
            overflow: hidden;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3">
    <div class="channex-chat-container" id="channex-app">
        {{-- Sidebar de conversaciones --}}
        <div class="channex-sidebar" id="channex-sidebar">
            <div class="channex-sidebar-header">
                <h5><i class="fas fa-comments me-2" style="color:#4a90d9"></i>Channex (Booking/Airbnb)</h5>
                <div class="channex-search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="channex-search" placeholder="Buscar conversacion...">
                </div>
            </div>
            <div class="channex-conversations-list" id="channex-conversations-list">
                @forelse($conversaciones as $conv)
                    <div class="channex-conversation-item"
                         data-booking-id="{{ $conv->booking_id }}"
                         data-thread-id="{{ $conv->thread_id }}">
                        <div class="channex-conversation-avatar">
                            {{ strtoupper(substr($conv->sender === 'guest' ? 'G' : 'H', 0, 1)) }}
                        </div>
                        <div class="channex-conversation-info">
                            <div class="channex-conversation-name" title="{{ $conv->booking_id }}">
                                Reserva: {{ Str::limit($conv->booking_id, 12) }}
                            </div>
                            <div class="channex-conversation-preview">
                                {{ Str::limit($conv->message, 50) }}
                            </div>
                        </div>
                        <div class="channex-conversation-meta">
                            <div class="channex-conversation-date">
                                {{ $conv->received_at ? $conv->received_at->format('d/m/Y H:i') : $conv->created_at->format('d/m/Y H:i') }}
                            </div>
                            <span class="channex-conversation-sender-badge {{ $conv->sender === 'guest' ? 'badge-guest' : 'badge-hotel' }}">
                                {{ $conv->sender === 'guest' ? 'Huesped' : 'Hotel' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-inbox" style="font-size:40px;color:#dfe6e9"></i>
                        <p class="mt-3">No hay conversaciones</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Panel de chat --}}
        <div class="channex-chat-panel" id="channex-chat-panel">
            <div class="channex-chat-header" id="channex-chat-header" style="display:none">
                <button class="back-btn" id="channex-back-btn">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="channex-chat-header-info">
                    <h6 id="channex-chat-title">-</h6>
                    <small id="channex-chat-subtitle">-</small>
                </div>
            </div>
            <div class="channex-chat-messages" id="channex-chat-messages">
                <div class="channex-chat-empty" id="channex-empty-state">
                    <i class="fas fa-comments"></i>
                    <p>Selecciona una conversacion para ver los mensajes</p>
                </div>
            </div>
            <div class="channex-loading" id="channex-loading">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando mensajes...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const conversationItems = document.querySelectorAll('.channex-conversation-item');
    const chatMessages = document.getElementById('channex-chat-messages');
    const chatHeader = document.getElementById('channex-chat-header');
    const chatTitle = document.getElementById('channex-chat-title');
    const chatSubtitle = document.getElementById('channex-chat-subtitle');
    const emptyState = document.getElementById('channex-empty-state');
    const loading = document.getElementById('channex-loading');
    const chatPanel = document.getElementById('channex-chat-panel');
    const backBtn = document.getElementById('channex-back-btn');
    const searchInput = document.getElementById('channex-search');

    let activeBookingId = null;

    // Buscar conversaciones
    searchInput.addEventListener('input', function () {
        const val = this.value.toLowerCase();
        conversationItems.forEach(function (item) {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(val) ? '' : 'none';
        });
    });

    // Click en conversacion
    conversationItems.forEach(function (item) {
        item.addEventListener('click', function () {
            const bookingId = this.getAttribute('data-booking-id');
            if (activeBookingId === bookingId) return;

            // Marcar activo
            conversationItems.forEach(function (i) { i.classList.remove('active'); });
            this.classList.add('active');
            activeBookingId = bookingId;

            // Header
            chatHeader.style.display = 'flex';
            chatTitle.textContent = 'Reserva: ' + bookingId.substring(0, 16) + '...';
            chatSubtitle.textContent = 'Booking ID: ' + bookingId;

            // Mobile: mostrar panel
            chatPanel.classList.add('visible');

            // Cargar mensajes
            cargarMensajes(bookingId);
        });
    });

    // Boton volver (mobile)
    backBtn.addEventListener('click', function () {
        chatPanel.classList.remove('visible');
        activeBookingId = null;
        conversationItems.forEach(function (i) { i.classList.remove('active'); });
    });

    function cargarMensajes(bookingId) {
        emptyState.style.display = 'none';
        loading.style.display = 'block';
        chatMessages.innerHTML = '';

        fetch("{{ url('admin/channex-mensajes') }}/" + bookingId)
            .then(function (res) { return res.json(); })
            .then(function (mensajes) {
                loading.style.display = 'none';

                if (mensajes.length === 0) {
                    chatMessages.innerHTML = '<div class="channex-chat-empty"><i class="fas fa-comment-slash"></i><p>Sin mensajes</p></div>';
                    return;
                }

                let html = '';
                let lastDate = '';

                mensajes.forEach(function (msg) {
                    const fecha = msg.received_at ? new Date(msg.received_at) : new Date(msg.created_at);
                    const dateStr = fecha.toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                    const timeStr = fecha.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

                    // Separador de fecha
                    if (dateStr !== lastDate) {
                        html += '<div class="channex-date-separator"><span>' + capitalize(dateStr) + '</span></div>';
                        lastDate = dateStr;
                    }

                    const isGuest = (msg.type === 'guest' || msg.sender === 'guest');
                    const rowClass = isGuest ? 'msg-guest' : 'msg-hotel';
                    const senderLabel = isGuest ? 'Huesped' : 'Hawkins IA';

                    html += '<div class="channex-msg-row ' + rowClass + '">';
                    html += '  <div class="channex-msg-bubble">';
                    html += '    <div class="channex-msg-sender-label">' + senderLabel + '</div>';
                    html += '    <div>' + escapeHtml(msg.message) + '</div>';
                    html += '    <div class="channex-msg-time">' + timeStr + '</div>';
                    html += '  </div>';
                    html += '</div>';
                });

                chatMessages.innerHTML = html;
                chatMessages.scrollTop = chatMessages.scrollHeight;
            })
            .catch(function (err) {
                loading.style.display = 'none';
                chatMessages.innerHTML = '<div class="channex-chat-empty"><i class="fas fa-exclamation-triangle" style="color:#e74c3c"></i><p>Error al cargar mensajes</p></div>';
                console.error(err);
            });
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
});
</script>
@endsection
