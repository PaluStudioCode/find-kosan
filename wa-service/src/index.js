require('dotenv').config();

const express = require('express');
const { initTables } = require('./database');
const SessionManager = require('./session-manager');
const createRoutes = require('./routes');
const authMiddleware = require('./middleware');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(express.json());

// API routes with auth
const sessionManager = new SessionManager();
app.use('/api', authMiddleware, createRoutes(sessionManager));

// Start server
async function start() {
    try {
        // Initialize database tables
        await initTables();

        // Start Express server
        app.listen(PORT, () => {
            console.log(`[WA-Service] Running on http://localhost:${PORT}`);
            console.log(`[WA-Service] Health check: http://localhost:${PORT}/api/health`);
        });

        // Auto-restart saved sessions after a short delay
        setTimeout(async () => {
            try {
                await sessionManager.restartSavedSessions();
            } catch (error) {
                console.error('[WA-Service] Failed to restart saved sessions:', error.message);
            }
        }, 2000);

    } catch (error) {
        console.error('[WA-Service] Fatal error:', error);
        process.exit(1);
    }
}

start();
