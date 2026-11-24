/**
 * widget.js — AI Consultant Widget
 *
 * Lazy-loaded on mrakibulislam.com; creates a self-contained chat interface.
 *
 * Usage: add to index.html before </body>:
 *   <link rel="stylesheet" href="/ai-consultant/widget.css">
 *   <script defer src="/ai-consultant/widget.js"></script>
 *
 * No external dependencies. Pure vanilla JS.
 */

(function() {
  'use strict';

  const CONFIG = {
    apiBase: '/ai-consultant/api',
    domain: 'mrakibulislam.com',
    maxMessageLength: 1000,
    typingIndicatorMs: 300,
  };

  let widget = {
    isOpen: false,
    isLoading: false,
    sessionId: null,
    messages: [],
    container: null,
    chatBox: null,
    messagesDiv: null,
    inputField: null,
    sendBtn: null,
    launcherBtn: null,
  };

  /**
   * Initialize the widget on page load
   */
  function init() {
    // Check HTTPS
    if (window.location.protocol !== 'https:' && !window.location.hostname.includes('localhost')) {
      console.warn('AI Consultant widget requires HTTPS');
      return;
    }

    // Generate session ID
    widget.sessionId = generateSessionId();

    // Create launcher
    createLauncher();

    // Create hidden chat container
    createChatWindow();

    // Check service health (optional, non-blocking)
    checkHealth();
  }

  /**
   * Generate a unique session ID for this visitor
   */
  function generateSessionId() {
    const existing = sessionStorage.getItem('mri_session_id');
    if (existing) return existing;

    const id = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    sessionStorage.setItem('mri_session_id', id);
    return id;
  }

  /**
   * Create the launcher button
   */
  function createLauncher() {
    widget.launcherBtn = document.createElement('button');
    widget.launcherBtn.id = 'mri-launcher';
    widget.launcherBtn.className = 'mri-launcher';
    widget.launcherBtn.setAttribute('aria-label', 'Open AI Consultant chat');
    widget.launcherBtn.setAttribute('title', 'Chat with our consultant');

    widget.launcherBtn.innerHTML = `
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
      </svg>
    `;

    widget.launcherBtn.addEventListener('click', toggleChat);
    document.body.appendChild(widget.launcherBtn);
  }

  /**
   * Create the chat window (hidden by default)
   */
  function createChatWindow() {
    // Container
    widget.container = document.createElement('div');
    widget.container.id = 'mri-widget-container';
    widget.container.className = 'mri-widget-container mri-hidden';

    // Chat box
    widget.chatBox = document.createElement('div');
    widget.chatBox.className = 'mri-chat-box';

    // Header
    const header = document.createElement('div');
    header.className = 'mri-chat-header';
    header.innerHTML = `
      <h2>AI Consultant</h2>
      <button class="mri-close-btn" aria-label="Close chat">×</button>
    `;
    header.querySelector('.mri-close-btn').addEventListener('click', closeChat);

    // Messages area
    widget.messagesDiv = document.createElement('div');
    widget.messagesDiv.className = 'mri-messages';

    // Input area
    const inputArea = document.createElement('div');
    inputArea.className = 'mri-input-area';

    widget.inputField = document.createElement('textarea');
    widget.inputField.className = 'mri-input';
    widget.inputField.placeholder = 'Ask about services, pricing, or book a call...';
    widget.inputField.setAttribute('aria-label', 'Message input');
    widget.inputField.addEventListener('keydown', handleInputKeydown);

    widget.sendBtn = document.createElement('button');
    widget.sendBtn.className = 'mri-send-btn';
    widget.sendBtn.setAttribute('aria-label', 'Send message');
    widget.sendBtn.innerHTML = '→';
    widget.sendBtn.addEventListener('click', sendMessage);

    inputArea.appendChild(widget.inputField);
    inputArea.appendChild(widget.sendBtn);

    // Welcome message
    addSystemMessage('Hi! I\'m here to help you understand our services and get you started. What can I help with today?');

    widget.chatBox.appendChild(header);
    widget.chatBox.appendChild(widget.messagesDiv);
    widget.chatBox.appendChild(inputArea);

    widget.container.appendChild(widget.chatBox);
    document.body.appendChild(widget.container);
  }

  /**
   * Toggle chat window open/closed
   */
  function toggleChat() {
    if (widget.isOpen) {
      closeChat();
    } else {
      openChat();
    }
  }

  /**
   * Open chat window
   */
  function openChat() {
    widget.isOpen = true;
    widget.container.classList.remove('mri-hidden');
    widget.launcherBtn.classList.add('mri-open');
    widget.inputField.focus();
  }

  /**
   * Close chat window
   */
  function closeChat() {
    widget.isOpen = false;
    widget.container.classList.add('mri-hidden');
    widget.launcherBtn.classList.remove('mri-open');
  }

  /**
   * Handle input field keypress (Enter to send, Shift+Enter for newline)
   */
  function handleInputKeydown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    } else if (e.key === 'Escape') {
      closeChat();
    }
  }

  /**
   * Send a message to the API
   */
  async function sendMessage() {
    const text = widget.inputField.value.trim();

    if (!text) return;
    if (widget.isLoading) return;
    if (text.length > CONFIG.maxMessageLength) {
      alert('Message is too long (max ' + CONFIG.maxMessageLength + ' chars)');
      return;
    }

    // Add user message
    addMessage('user', text);
    widget.inputField.value = '';

    // Show loading indicator
    widget.isLoading = true;
    addTypingIndicator();

    try {
      const response = await fetch(CONFIG.apiBase + '/chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message: text,
          session_id: widget.sessionId,
          consent_to_store: false,
        }),
      });

      if (!response.ok) {
        throw new Error('API error: ' + response.status);
      }

      const data = await response.json();

      removeTypingIndicator();

      if (data.success && data.reply) {
        addMessage('assistant', data.reply);
      } else {
        addErrorMessage(data.error || 'No response. Please try again.');
      }
    } catch (err) {
      removeTypingIndicator();
      console.error('Chat error:', err);
      addErrorMessage('Service unavailable. Please email hello@mrakibulislam.com');
    } finally {
      widget.isLoading = false;
      widget.inputField.focus();
    }
  }

  /**
   * Add a user or assistant message to the chat
   */
  function addMessage(role, text) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'mri-message mri-message-' + role;

    const contentDiv = document.createElement('div');
    contentDiv.className = 'mri-message-content';
    contentDiv.textContent = text;

    msgDiv.appendChild(contentDiv);
    widget.messagesDiv.appendChild(msgDiv);

    // Scroll to latest
    setTimeout(() => {
      widget.messagesDiv.scrollTop = widget.messagesDiv.scrollHeight;
    }, 10);

    widget.messages.push({ role, text, timestamp: new Date() });
  }

  /**
   * Add a system message (e.g., welcome, error)
   */
  function addSystemMessage(text) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'mri-message mri-message-system';

    const contentDiv = document.createElement('div');
    contentDiv.className = 'mri-message-content';
    contentDiv.textContent = text;

    msgDiv.appendChild(contentDiv);
    widget.messagesDiv.appendChild(msgDiv);

    widget.messages.push({ role: 'system', text, timestamp: new Date() });
  }

  /**
   * Add an error message
   */
  function addErrorMessage(text) {
    const msgDiv = document.createElement('div');
    msgDiv.className = 'mri-message mri-message-error';

    const contentDiv = document.createElement('div');
    contentDiv.className = 'mri-message-content';
    contentDiv.textContent = text;

    // Add email contact info safely
    const emailInfo = document.createElement('small');
    emailInfo.style.display = 'block';
    emailInfo.style.marginTop = '8px';
    emailInfo.textContent = 'Or email hello@mrakibulislam.com';
    contentDiv.appendChild(emailInfo);

    msgDiv.appendChild(contentDiv);
    widget.messagesDiv.appendChild(msgDiv);

    widget.messages.push({ role: 'error', text, timestamp: new Date() });
  }

  /**
   * Show typing indicator while waiting for response
   */
  function addTypingIndicator() {
    const msgDiv = document.createElement('div');
    msgDiv.id = 'mri-typing-indicator';
    msgDiv.className = 'mri-message mri-message-assistant mri-typing';

    const contentDiv = document.createElement('div');
    contentDiv.className = 'mri-message-content';
    contentDiv.innerHTML = '<span></span><span></span><span></span>';

    msgDiv.appendChild(contentDiv);
    widget.messagesDiv.appendChild(msgDiv);

    widget.messagesDiv.scrollTop = widget.messagesDiv.scrollHeight;
  }

  /**
   * Remove typing indicator
   */
  function removeTypingIndicator() {
    const indicator = document.getElementById('mri-typing-indicator');
    if (indicator) indicator.remove();
  }

  /**
   * Check if the service is healthy (optional, non-blocking)
   */
  async function checkHealth() {
    try {
      const response = await fetch(CONFIG.apiBase + '/health.php');
      if (!response.ok) {
        console.warn('AI Consultant service check failed');
      }
    } catch (err) {
      console.warn('AI Consultant service unreachable:', err);
    }
  }

  /**
   * Initialize when DOM is ready
   */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
