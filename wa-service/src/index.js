const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '../../.env') });

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

        await sessionManager.restartSavedSessions();

        app.listen(PORT, () => {
            console.log(`[WA-Service] Running on http://localhost:${PORT}`);
            console.log(`[WA-Service] Health check: http://localhost:${PORT}/api/health`);
        });

    } catch (error) {
        console.error('[WA-Service] Fatal error:', error);
        process.exit(1);
    }
}

start();
