const {
    default: makeWASocket,
    DisconnectReason,
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
} = require('@whiskeysockets/baileys');
const pino = require('pino');
const QRCode = require('qrcode');
const { useMySQLAuthState, clearAuthState } = require('./auth-store');
const { getPool } = require('./database');
const { handleIncomingMessage, NON_TEXT_REPLY } = require('./ai-handler');

const logger = pino({ level: 'silent' });

class SessionManager {
    constructor() {
        /** @type {Map<string, object>} adminId -> { socket, qr, status, pairingCode } */
        this.sessions = new Map();
    }

    /**
     * Get session info for an Admin
     */
    getSession(adminId) {
        return this.sessions.get(String(adminId)) || null;
    }

    /**
     * Get current status of an Admin's session
     */
    getStatus(adminId) {
        const session = this.getSession(adminId);
        if (!session) return { status: 'disconnected', qr: null, pairingCode: null, phoneNumber: null };

        return {
            status: session.status,
            qr: session.qr || null,
            pairingCode: session.pairingCode || null,
            phoneNumber: session.phoneNumber || null,
        };
    }

    /**
     * Start a new WhatsApp session for an Admin
     * @param {string|number} adminId
     * @param {object} options - { usePairingCode: boolean, phoneNumber: string }
     */
    async startSession(adminId, options = {}) {
        adminId = String(adminId);
        const { usePairingCode = false, phoneNumber = null } = options;

        // If session already exists and is connected, return it
        const existing = this.getSession(adminId);
        if (existing && existing.status === 'connected') {
            return { status: 'already_connected', phoneNumber: existing.phoneNumber };
        }

        // If there's an existing socket, close it first
        if (existing && existing.socket) {
            try {
                existing.socket.end();
            } catch (e) { /* ignore */ }
        }

        // Initialize session data
        this.sessions.set(adminId, {
            socket: null,
            qr: null,
            qrBase64: null,
            pairingCode: null,
            status: 'connecting',
            phoneNumber: null,
            usePairingCode,
            requestedPhoneNumber: phoneNumber,
        });

        await this._updateDbStatus(adminId, 'connecting');

        try {
            await this._createSocket(adminId);
            return { status: 'connecting' };
        } catch (error) {
            console.error(`[Session ${adminId}] Failed to start:`, error.message);
            this.sessions.delete(adminId);
            await this._updateDbStatus(adminId, 'disconnected');
            throw error;
        }
    }

    /**
     * Stop/disconnect a session for an Admin
     */
    async stopSession(adminId) {
        adminId = String(adminId);
        const session = this.getSession(adminId);

        if (session && session.socket) {
            try {
                await session.socket.logout();
            } catch (e) {
                try {
                    session.socket.end();
                } catch (e2) { /* ignore */ }
            }
        }

        this.sessions.delete(adminId);
        await clearAuthState(adminId);
        await this._updateDbStatus(adminId, 'disconnected');

        return { status: 'disconnected' };
    }

    /**
     * Send a WhatsApp message using an Admin's session
     */
    async sendMessage(adminId, targetPhone, message) {
        adminId = String(adminId);
        const session = this.getSession(adminId);

        if (!session || session.status !== 'connected' || !session.socket) {
            return {
                success: false,
                reason: 'WhatsApp Admin tidak terhubung',
            };
        }

        try {
            // Jika target sudah berupa JID lengkap (mengandung @, mis. @s.whatsapp.net atau @lid),
            // gunakan apa adanya. Jika hanya nomor telepon, normalize ke @s.whatsapp.net.
            let jid;
            if (targetPhone.includes('@')) {
                jid = targetPhone;
            } else {
                jid = targetPhone.replace(/[^0-9]/g, '');
                if (jid.startsWith('0')) {
                    jid = '62' + jid.substring(1);
                }
                jid = jid + '@s.whatsapp.net';
            }

            const result = await session.socket.sendMessage(jid, { text: message });

            return {
                success: true,
                messageId: result.key.id,
            };
        } catch (error) {
            console.error(`[Session ${adminId}] Send failed:`, error.message);
            return {
                success: false,
                reason: error.message,
            };
        }
    }

    /**
     * Restart all sessions that were previously connected (on server boot)
     */
    async restartSavedSessions() {
        const db = getPool();
        const [rows] = await db.execute(
            "SELECT admin_id FROM wa_sessions WHERE status = 'connected'"
        );

        console.log(`[SessionManager] Found ${rows.length} saved session(s) to restart.`);

        for (const row of rows) {
            try {
                console.log(`[SessionManager] Restarting session for Admin ${row.admin_id}...`);
                await this.startSession(row.admin_id);
            } catch (error) {
                console.error(`[SessionManager] Failed to restart session for Admin ${row.admin_id}:`, error.message);
            }
        }
    }

    /**
     * Internal: Create Baileys socket
     */
    async _createSocket(adminId) {
        const { state, saveCreds } = await useMySQLAuthState(adminId);
        const { version } = await fetchLatestBaileysVersion();

        const session = this.getSession(adminId);
        const usePairingCode = session?.usePairingCode || false;

        const socket = makeWASocket({
            version,
            logger,
            auth: {
                creds: state.creds,
                keys: makeCacheableSignalKeyStore(state.keys, logger),
            },
            printQRInTerminal: false,
            browser: ['Ubuntu', 'Chrome', '20.0.04'],
            generateHighQualityLinkPreview: false,
        });

        // Update session with socket
        if (this.sessions.has(adminId)) {
            this.sessions.get(adminId).socket = socket;
        }

        // Handle connection updates
        socket.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;
            const currentSession = this.getSession(adminId);
            if (!currentSession) return;

            if (qr && !usePairingCode) {
                // New QR code received
                try {
                    const qrBase64 = await QRCode.toDataURL(qr, { width: 300 });
                    currentSession.qr = qr;
                    currentSession.qrBase64 = qrBase64;
                    currentSession.status = 'connecting';
                    console.log(`[Session ${adminId}] New QR code generated.`);
                } catch (e) {
                    console.error(`[Session ${adminId}] QR generation error:`, e.message);
                }
            }

            if (connection === 'open') {
                // Connected successfully
                const phoneNumber = socket.user?.id?.split(':')[0] || socket.user?.id?.split('@')[0] || null;
                currentSession.status = 'connected';
                currentSession.phoneNumber = phoneNumber;
                currentSession.qr = null;
                currentSession.qrBase64 = null;
                currentSession.pairingCode = null;
                await this._updateDbStatus(adminId, 'connected', phoneNumber);
                console.log(`[Session ${adminId}] Connected as ${phoneNumber}.`);
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const reason = lastDisconnect?.error?.message;
                
                // PENTING: Jangan PERNAH menghapus credential secara otomatis saat restart/crash!
                // Server WA sering memberikan 401/403 palsu saat reconnecting dari state yang tidak bersih.
                // Kredensial HANYA dihapus jika dipanggil dari endpoint /sessions/:id/stop (fungsi stopSession).
                const shouldReconnect = true; // Selalu coba reconnect

                console.log(`[Session ${adminId}] Connection closed. Status: ${statusCode}, Reason: ${reason}. Reconnect: ${shouldReconnect}`);

                if (shouldReconnect) {
                    // Reconnect after a delay
                    currentSession.status = 'connecting';
                    currentSession.qr = null;
                    currentSession.qrBase64 = null;
                    
                    // Gunakan backoff sederhana
                    setTimeout(async () => {
                        try {
                            // Hanya reconnect jika sesi masih ada di map (tidak distop manual)
                            if (this.sessions.has(adminId)) {
                                await this._createSocket(adminId);
                            }
                        } catch (e) {
                            console.error(`[Session ${adminId}] Reconnect failed:`, e.message);
                        }
                    }, 5000);
                }
            }
        });

        // Save credentials on update
        socket.ev.on('creds.update', saveCreds);

        // Handle incoming messages (ONLY for SuperAdmin/system session: adminId === '0')
        if (adminId === '0' || adminId === 0) {
            socket.ev.on('messages.upsert', async ({ messages, type }) => {
                if (type !== 'notify') return;

                for (const msg of messages) {
                    // Ignore messages from ourselves, broadcasts, statuses, or group chats
                    if (msg.key.fromMe || msg.key.remoteJid.includes('@g.us') || msg.key.remoteJid === 'status@broadcast') {
                        continue;
                    }

                    // Only process protocol messages if they contain actual text
                    if (msg.message?.protocolMessage) {
                        continue;
                    }

                    // Extract remote JID and text content
                    let rawJid = msg.key.participant || msg.key.remoteJid;
                    let phoneNumber = rawJid.split('@')[0];

                    const text = msg.message?.conversation ||
                                 msg.message?.extendedTextMessage?.text ||
                                 msg.message?.ephemeralMessage?.message?.extendedTextMessage?.text ||
                                 null;

                    if (!text) {
                        // User sent image, voice, sticker, etc.
                        try {
                            await socket.sendMessage(rawJid, { text: NON_TEXT_REPLY });
                        } catch (e) {
                            console.error(`[AI] Failed to send non-text reply to ${phoneNumber}:`, e.message);
                        }
                        continue;
                    }

                    if (rawJid.includes('@lid')) {
                        console.log(`[AI] Deteksi pesan dari LID (Private JID): ${rawJid}`);
                        // Karena LID menyembunyikan nomor asli, kita tidak bisa langsung
                        // mengubahnya menjadi nomor MSISDN tanpa memanggil API kontak internal WhatsApp.
                    } else {
                        // Normalisasi ke 628xxx (jika bukan LID)
                        phoneNumber = phoneNumber.replace(/\D/g, ''); // Buang non-digit
                        if (phoneNumber.startsWith('0')) {
                            phoneNumber = '62' + phoneNumber.substring(1);
                        }
                    }

                    console.log(`[AI] Received message from ${phoneNumber} (fromMe: ${msg.key.fromMe}): ${text}`);

                    try {
                        const reply = await handleIncomingMessage(phoneNumber, text);
                        await socket.sendMessage(rawJid, { text: reply });
                        console.log(`[AI] Replied to ${phoneNumber}`);
                    } catch (error) {
                        console.error(`[AI] Failed to process/reply to ${phoneNumber}:`, error.message);
                    }
                }
            });
        }

        // If using pairing code, request it after socket is ready
        if (usePairingCode && session?.requestedPhoneNumber && !state.creds.registered) {
            setTimeout(async () => {
                // Pastikan status belum tersambung (open) saat merequest
                const currentSession = this.getSession(adminId);
                if (!currentSession || currentSession.status === 'connected') return;
                
                try {
                    let phone = session.requestedPhoneNumber.replace(/[^0-9]/g, '');
                    if (phone.startsWith('0')) {
                        phone = '62' + phone.substring(1);
                    }
                    const code = await socket.requestPairingCode(phone);
                    if (currentSession) {
                        currentSession.pairingCode = code;
                        currentSession.status = 'connecting';
                        console.log(`[Session ${adminId}] Pairing code: ${code}`);
                    }
                } catch (error) {
                    console.error(`[Session ${adminId}] Pairing code request failed:`, error.message);
                }
            }, 3000);
        }
    }

    /**
     * Internal: Update session status in database
     */
    async _updateDbStatus(adminId, status, phoneNumber = null) {
        const db = getPool();
        const now = new Date().toISOString().slice(0, 19).replace('T', ' ');

        if (status === 'connected') {
            await db.execute(
                `INSERT INTO wa_sessions (admin_id, status, phone_number, connected_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), phone_number = VALUES(phone_number),
                 connected_at = VALUES(connected_at), disconnected_at = NULL, updated_at = VALUES(updated_at)`,
                [adminId, status, phoneNumber, now, now, now]
            );
        } else if (status === 'disconnected') {
            await db.execute(
                `INSERT INTO wa_sessions (admin_id, status, disconnected_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status),
                 disconnected_at = VALUES(disconnected_at), phone_number = NULL, updated_at = VALUES(updated_at)`,
                [adminId, status, now, now, now]
            );
        } else {
            await db.execute(
                `INSERT INTO wa_sessions (admin_id, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = VALUES(updated_at)`,
                [adminId, status, now, now]
            );
        }
    }
}

module.exports = SessionManager;
