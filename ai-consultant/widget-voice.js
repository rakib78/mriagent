/**
 * widget-voice.js — Voice mode for AI Consultant
 *
 * Lazy-loaded only if voice is enabled in config.
 * Integrates with OpenAI Realtime API (WebRTC).
 *
 * Loaded by: widget.js (on demand)
 */

(function() {
  'use strict';

  const CONFIG = {
    apiBase: '/ai-consultant/api',
    maxSessionMinutes: 30,
    warnAtMinutes: 20,
    model: 'gpt-4o-realtime-preview',
  };

  let voice = {
    enabled: false,
    isRecording: false,
    isConnecting: false,
    sessionStartTime: null,
    rtcClient: null,
    micButton: null,
    statusDisplay: null,
    sessionTimer: null,
  };

  /**
   * Initialize voice if enabled
   */
  window.mri_voice_init = function(containerId) {
    // Check if voice config allows it
    // This would normally come from backend, but for now we check localStorage
    const voiceEnabled = localStorage.getItem('mri_voice_enabled') === 'true';

    if (!voiceEnabled) {
      voice.enabled = false;
      return false;
    }

    // Check browser support
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      console.warn('Voice not supported on this browser');
      voice.enabled = false;
      return false;
    }

    voice.enabled = true;
    createVoiceUI(containerId);
    return true;
  };

  /**
   * Create voice UI elements
   */
  function createVoiceUI(containerId) {
    const container = document.querySelector(containerId || '.mri-input-area');
    if (!container) return;

    // Microphone button
    voice.micButton = document.createElement('button');
    voice.micButton.className = 'mri-voice-btn';
    voice.micButton.setAttribute('aria-label', 'Start voice chat');
    voice.micButton.title = 'Voice chat (beta)';
    voice.micButton.innerHTML = `
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
        <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
        <line x1="12" y1="19" x2="12" y2="23"></line>
        <line x1="8" y1="23" x2="16" y2="23"></line>
      </svg>
    `;
    voice.micButton.addEventListener('click', toggleVoice);

    container.insertBefore(voice.micButton, container.firstChild);

    // Status display (initially hidden)
    voice.statusDisplay = document.createElement('div');
    voice.statusDisplay.className = 'mri-voice-status mri-hidden';
    document.body.appendChild(voice.statusDisplay);
  }

  /**
   * Toggle voice on/off
   */
  async function toggleVoice() {
    if (voice.isRecording) {
      stopVoice();
    } else {
      startVoice();
    }
  }

  /**
   * Start voice session
   */
  async function startVoice() {
    if (voice.isConnecting || voice.isRecording) return;

    voice.isConnecting = true;
    updateMicButton();

    try {
      // Request microphone permission
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      stream.getTracks().forEach(t => t.stop()); // Stop immediately; we just need to request permission

      // Get session token from backend
      const tokenResponse = await fetch(CONFIG.apiBase + '/voice-token.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          session_id: sessionStorage.getItem('mri_session_id'),
          consent_to_record: true,
        }),
      });

      if (!tokenResponse.ok) {
        const data = await tokenResponse.json();
        showVoiceStatus('error', data.error || 'Could not start voice session');
        voice.isConnecting = false;
        updateMicButton();
        return;
      }

      const tokenData = await tokenResponse.json();

      if (!tokenData.success) {
        showVoiceStatus('error', tokenData.error || 'Voice unavailable');
        voice.isConnecting = false;
        updateMicButton();
        return;
      }

      // Connect to Realtime API
      await connectRealtime(tokenData.token);

      voice.isRecording = true;
      voice.isConnecting = false;
      voice.sessionStartTime = Date.now();

      showVoiceStatus('recording', '● Recording...');
      startSessionTimer();
      updateMicButton();
    } catch (err) {
      console.error('Voice error:', err);

      if (err.name === 'NotAllowedError') {
        showVoiceStatus('error', 'Microphone permission denied. Use text chat instead.');
      } else if (err.name === 'NotFoundError') {
        showVoiceStatus('error', 'No microphone found.');
      } else {
        showVoiceStatus('error', 'Could not start voice chat. Try again or use text.');
      }

      voice.isConnecting = false;
      updateMicButton();
    }
  }

  /**
   * Stop voice session
   */
  function stopVoice() {
    voice.isRecording = false;

    if (voice.rtcClient) {
      voice.rtcClient.disconnect();
      voice.rtcClient = null;
    }

    if (voice.sessionTimer) {
      clearInterval(voice.sessionTimer);
      voice.sessionTimer = null;
    }

    showVoiceStatus('hidden', '');
    updateMicButton();
  }

  /**
   * Connect to OpenAI Realtime API
   * (Placeholder; actual implementation requires WebRTC and audio handling)
   */
  async function connectRealtime(token) {
    // This is a simplified placeholder. Full implementation would:
    // 1. Open WebRTC connection to OpenAI Realtime endpoint
    // 2. Handle audio input/output
    // 3. Manage VAD (voice activity detection)
    // 4. Stream transcripts in real-time
    // 5. Handle interruption and reconnection
    //
    // For Phase 4 MVP, this logs that voice would connect.

    console.log('Voice session started (token: ' + token.substring(0, 20) + '...)');

    // Simulate API client object
    voice.rtcClient = {
      disconnect: () => console.log('Voice session ended'),
    };
  }

  /**
   * Update microphone button state
   */
  function updateMicButton() {
    if (!voice.micButton) return;

    if (voice.isRecording) {
      voice.micButton.classList.add('mri-recording');
      voice.micButton.setAttribute('aria-label', 'Stop voice chat');
    } else {
      voice.micButton.classList.remove('mri-recording');
      voice.micButton.setAttribute('aria-label', 'Start voice chat');
    }

    if (voice.isConnecting) {
      voice.micButton.disabled = true;
    } else {
      voice.micButton.disabled = false;
    }
  }

  /**
   * Show status message
   */
  function showVoiceStatus(type, text) {
    if (!voice.statusDisplay) return;

    if (type === 'hidden') {
      voice.statusDisplay.classList.add('mri-hidden');
    } else {
      voice.statusDisplay.className = 'mri-voice-status mri-voice-' + type;
      voice.statusDisplay.textContent = text;
      voice.statusDisplay.classList.remove('mri-hidden');
    }
  }

  /**
   * Session timer (warn at 20 min, stop at 30 min)
   */
  function startSessionTimer() {
    voice.sessionTimer = setInterval(() => {
      if (!voice.sessionStartTime) return;

      const elapsedMinutes = Math.floor((Date.now() - voice.sessionStartTime) / 60000);
      const maxMinutes = CONFIG.maxSessionMinutes;
      const warnMinutes = CONFIG.warnAtMinutes;

      if (elapsedMinutes >= maxMinutes) {
        showVoiceStatus('error', 'Session time limit reached (30 minutes)');
        stopVoice();
      } else if (elapsedMinutes >= warnMinutes) {
        const remaining = maxMinutes - elapsedMinutes;
        showVoiceStatus('warning', remaining + ' minutes remaining');
      }
    }, 60000); // Check every minute
  }
})();
