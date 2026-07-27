const express = require('express');
const router = express.Router();

/**
 * Create routes with the session manager instance
 * @param {import('./session-manager')} sessionManager
 */
function createRoutes(sessionManager) {

    /**
     * POST /api/sessions/:ownerId/start
     * Start a new WhatsApp session for an owner.
     * Body: { usePairingCode?: boolean, phoneNumber?: string }
     */
    router.post('/sessions/:ownerId/start', async (req, res) => {
        try {
            const { ownerId } = req.params;
            const { usePairingCode = false, phoneNumber = null } = req.body || {};

            const result = await sessionManager.startSession(ownerId, {
                usePairingCode,
                phoneNumber,
            });

            res.json({ success: true, ...result });
        } catch (error) {
            console.error('[API] Start session error:', error.message);
            res.status(500).json({ success: false, error: error.message });
        }
    });

    /**
     * POST /api/sessions/:ownerId/stop
     * Stop/disconnect an owner's WhatsApp session.
     */
    router.post('/sessions/:ownerId/stop', async (req, res) => {
        try {
            const { ownerId } = req.params;
            const result = await sessionManager.stopSession(ownerId);
            res.json({ success: true, ...result });
        } catch (error) {
            console.error('[API] Stop session error:', error.message);
            res.status(500).json({ success: false, error: error.message });
        }
    });

    /**
     * GET /api/sessions/:ownerId/status
     * Get the current status of an owner's session.
     */
    router.get('/sessions/:ownerId/status', (req, res) => {
        const { ownerId } = req.params;
        const status = sessionManager.getStatus(ownerId);
        res.json({ success: true, ...status });
    });

    /**
     * GET /api/sessions/:ownerId/qr
     * Get the current QR code for an owner's session.
     * Returns base64 data URI of the QR code image.
     */
    router.get('/sessions/:ownerId/qr', (req, res) => {
        const { ownerId } = req.params;
        const session = sessionManager.getSession(ownerId);

        if (!session) {
            return res.json({
                success: false,
                error: 'Session not started. Call /start first.',
                qr: null,
            });
        }

        if (session.status === 'connected') {
            return res.json({
                success: true,
                status: 'connected',
                qr: null,
                pairingCode: null,
                phoneNumber: session.phoneNumber,
            });
        }

        res.json({
            success: true,
            status: session.status,
            qr: session.qrBase64 || null,
            pairingCode: session.pairingCode || null,
        });
    });

    /**
     * POST /api/sessions/:ownerId/send
     * Send a WhatsApp message using an owner's session.
     * Body: { phone: string, message: string }
     */
    router.post('/sessions/:ownerId/send', async (req, res) => {
        try {
            const { ownerId } = req.params;
            const { phone, message } = req.body || {};

            if (!phone || !message) {
                return res.status(400).json({
                    success: false,
                    error: 'Missing required fields: phone, message',
                });
            }

            const result = await sessionManager.sendMessage(ownerId, phone, message);
            res.json(result);
        } catch (error) {
            console.error('[API] Send message error:', error.message);
            res.status(500).json({ success: false, error: error.message });
        }
    });

    /**
     * POST /api/sessions/restart-all
     * Restart all previously connected sessions (used on server boot).
     */
    router.post('/sessions/restart-all', async (req, res) => {
        try {
            await sessionManager.restartSavedSessions();
            res.json({ success: true, message: 'All saved sessions are being restarted.' });
        } catch (error) {
            console.error('[API] Restart all error:', error.message);
            res.status(500).json({ success: false, error: error.message });
        }
    });

    /**
     * GET /api/health
     * Health check endpoint.
     */
    router.get('/health', (req, res) => {
        res.json({
            success: true,
            uptime: process.uptime(),
            activeSessions: sessionManager.sessions.size,
        });
    });

    return router;
}

module.exports = createRoutes;
