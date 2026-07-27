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

const logger = pino({ level: 'silent' });

class SessionManager {
    constructor() {
        /** @type {Map<string, object>} ownerId -> { socket, qr, status, pairingCode } */
        this.sessions = new Map();
    }

    /**
     * Get session info for an owner
     */
    getSession(ownerId) {
        return this.sessions.get(String(ownerId)) || null;
    }

    /**
     * Get current status of an owner's session
     */
    getStatus(ownerId) {
        const session = this.getSession(ownerId);
        if (!session) return { status: 'disconnected', qr: null, pairingCode: null, phoneNumber: null };

        return {
            status: session.status,
            qr: session.qr || null,
            pairingCode: session.pairingCode || null,
            phoneNumber: session.phoneNumber || null,
        };
    }

    /**
     * Start a new WhatsApp session for an owner
     * @param {string|number} ownerId
     * @param {object} options - { usePairingCode: boolean, phoneNumber: string }
     */
    async startSession(ownerId, options = {}) {
        ownerId = String(ownerId);
        const { usePairingCode = false, phoneNumber = null } = options;

        // If session already exists and is connected, return it
        const existing = this.getSession(ownerId);
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
        this.sessions.set(ownerId, {
            socket: null,
            qr: null,
            qrBase64: null,
            pairingCode: null,
            status: 'connecting',
            phoneNumber: null,
            usePairingCode,
            requestedPhoneNumber: phoneNumber,
        });

        await this._updateDbStatus(ownerId, 'connecting');

        try {
            await this._createSocket(ownerId);
            return { status: 'connecting' };
        } catch (error) {
            console.error(`[Session ${ownerId}] Failed to start:`, error.message);
            this.sessions.delete(ownerId);
            await this._updateDbStatus(ownerId, 'disconnected');
            throw error;
        }
    }

    /**
     * Stop/disconnect a session for an owner
     */
    async stopSession(ownerId) {
        ownerId = String(ownerId);
        const session = this.getSession(ownerId);

        if (session && session.socket) {
            try {
                await session.socket.logout();
            } catch (e) {
                try {
                    session.socket.end();
                } catch (e2) { /* ignore */ }
            }
        }

        this.sessions.delete(ownerId);
        await clearAuthState(ownerId);
        await this._updateDbStatus(ownerId, 'disconnected');

        return { status: 'disconnected' };
    }

    /**
     * Send a WhatsApp message using an owner's session
     */
    async sendMessage(ownerId, targetPhone, message) {
        ownerId = String(ownerId);
        const session = this.getSession(ownerId);

        if (!session || session.status !== 'connected' || !session.socket) {
            return {
                success: false,
                reason: 'WhatsApp owner tidak terhubung',
            };
        }

        try {
            // Normalize phone number: ensure it ends with @s.whatsapp.net
            let jid = targetPhone.replace(/[^0-9]/g, '');
            if (jid.startsWith('0')) {
                jid = '62' + jid.substring(1);
            }
            if (!jid.includes('@')) {
                jid = jid + '@s.whatsapp.net';
            }

            const result = await session.socket.sendMessage(jid, { text: message });

            return {
                success: true,
                messageId: result.key.id,
            };
        } catch (error) {
            console.error(`[Session ${ownerId}] Send failed:`, error.message);
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
            "SELECT owner_id FROM wa_sessions WHERE status = 'connected'"
        );

        console.log(`[SessionManager] Found ${rows.length} saved session(s) to restart.`);

        for (const row of rows) {
            try {
                console.log(`[SessionManager] Restarting session for owner ${row.owner_id}...`);
                await this.startSession(row.owner_id);
            } catch (error) {
                console.error(`[SessionManager] Failed to restart session for owner ${row.owner_id}:`, error.message);
            }
        }
    }

    /**
     * Internal: Create Baileys socket
     */
    async _createSocket(ownerId) {
        const { state, saveCreds } = await useMySQLAuthState(ownerId);
        const { version } = await fetchLatestBaileysVersion();

        const session = this.getSession(ownerId);
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
        if (this.sessions.has(ownerId)) {
            this.sessions.get(ownerId).socket = socket;
        }

        // Handle connection updates
        socket.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;
            const currentSession = this.getSession(ownerId);
            if (!currentSession) return;

            if (qr && !usePairingCode) {
                // New QR code received
                try {
                    const qrBase64 = await QRCode.toDataURL(qr, { width: 300 });
                    currentSession.qr = qr;
                    currentSession.qrBase64 = qrBase64;
                    currentSession.status = 'connecting';
                    console.log(`[Session ${ownerId}] New QR code generated.`);
                } catch (e) {
                    console.error(`[Session ${ownerId}] QR generation error:`, e.message);
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
                await this._updateDbStatus(ownerId, 'connected', phoneNumber);
                console.log(`[Session ${ownerId}] Connected as ${phoneNumber}.`);
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

                console.log(`[Session ${ownerId}] Connection closed. Status: ${statusCode}. Reconnect: ${shouldReconnect}`);

                if (shouldReconnect) {
                    // Reconnect after a delay
                    currentSession.status = 'connecting';
                    currentSession.qr = null;
                    currentSession.qrBase64 = null;
                    setTimeout(async () => {
                        try {
                            if (this.sessions.has(ownerId)) {
                                await this._createSocket(ownerId);
                            }
                        } catch (e) {
                            console.error(`[Session ${ownerId}] Reconnect failed:`, e.message);
                        }
                    }, 3000);
                } else {
                    // Logged out - clean up
                    this.sessions.delete(ownerId);
                    await clearAuthState(ownerId);
                    await this._updateDbStatus(ownerId, 'disconnected');
                    console.log(`[Session ${ownerId}] Logged out. Session cleaned.`);
                }
            }
        });

        // Save credentials on update
        socket.ev.on('creds.update', saveCreds);

        // If using pairing code, request it after socket is ready
        if (usePairingCode && session?.requestedPhoneNumber && !state.creds.registered) {
            setTimeout(async () => {
                try {
                    let phone = session.requestedPhoneNumber.replace(/[^0-9]/g, '');
                    if (phone.startsWith('0')) {
                        phone = '62' + phone.substring(1);
                    }
                    const code = await socket.requestPairingCode(phone);
                    const currentSession = this.getSession(ownerId);
                    if (currentSession) {
                        currentSession.pairingCode = code;
                        currentSession.status = 'connecting';
                        console.log(`[Session ${ownerId}] Pairing code: ${code}`);
                    }
                } catch (error) {
                    console.error(`[Session ${ownerId}] Pairing code request failed:`, error.message);
                }
            }, 3000);
        }
    }

    /**
     * Internal: Update session status in database
     */
    async _updateDbStatus(ownerId, status, phoneNumber = null) {
        const db = getPool();
        const now = new Date().toISOString().slice(0, 19).replace('T', ' ');

        if (status === 'connected') {
            await db.execute(
                `INSERT INTO wa_sessions (owner_id, status, phone_number, connected_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), phone_number = VALUES(phone_number),
                 connected_at = VALUES(connected_at), disconnected_at = NULL, updated_at = VALUES(updated_at)`,
                [ownerId, status, phoneNumber, now, now, now]
            );
        } else if (status === 'disconnected') {
            await db.execute(
                `INSERT INTO wa_sessions (owner_id, status, disconnected_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status),
                 disconnected_at = VALUES(disconnected_at), phone_number = NULL, updated_at = VALUES(updated_at)`,
                [ownerId, status, now, now, now]
            );
        } else {
            await db.execute(
                `INSERT INTO wa_sessions (owner_id, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = VALUES(updated_at)`,
                [ownerId, status, now, now]
            );
        }
    }
}

module.exports = SessionManager;
