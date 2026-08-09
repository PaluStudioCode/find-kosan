const assert = require('node:assert/strict');
const test = require('node:test');
const { DisconnectReason } = require('@whiskeysockets/baileys');
const SessionManager = require('../src/session-manager');

test('restores sessions marked connected or connecting', async () => {
    const manager = new SessionManager();
    const restarted = [];

    manager._getRestartableSessions = async () => [{ admin_id: 0 }, { admin_id: 42 }];
    manager.startSession = async (adminId) => restarted.push(String(adminId));

    await manager.restartSavedSessions();

    assert.deepEqual(restarted, ['0', '42']);
});

test('keeps an in-progress session instead of creating a second socket', async () => {
    const manager = new SessionManager();
    const session = {
        socket: {},
        status: 'connecting',
        qrBase64: 'data:image/png;base64,qr',
        pairingCode: '12345678',
    };

    manager.sessions.set('42', session);
    manager._createSocket = async () => assert.fail('must not create another socket');

    const result = await manager.startSession(42);

    assert.equal(result.status, 'connecting');
    assert.equal(result.qr, session.qrBase64);
    assert.equal(result.pairingCode, session.pairingCode);
});

test('disconnecting from the dashboard clears the live socket and persisted authentication', async () => {
    const manager = new SessionManager();
    const calls = [];
    const socket = {
        logout: async () => calls.push('logout'),
        end: () => calls.push('end'),
    };
    const session = { socket, status: 'connected', stopping: false };
    const timer = setTimeout(() => assert.fail('reconnect timer must be cancelled'), 100);

    manager.sessions.set('42', session);
    manager.reconnectTimers.set('42', timer);
    manager._clearAuthState = async (adminId) => calls.push(`clear:${adminId}`);
    manager._updateDbStatus = async (adminId, status) => calls.push(`db:${adminId}:${status}`);

    const result = await manager.stopSession(42);

    assert.deepEqual(result, { status: 'disconnected' });
    assert.equal(manager.getSession(42), null);
    assert.equal(manager.reconnectTimers.has('42'), false);
    assert.equal(session.stopping, true);
    assert.deepEqual(calls, ['logout', 'clear:42', 'db:42:disconnected']);
});

test('ignores close events from a replaced socket', async () => {
    const manager = new SessionManager();
    const activeSession = { socket: {}, status: 'connected', stopping: false };
    const staleSession = { socket: {}, status: 'connected', stopping: false };
    const updates = [];

    manager.sessions.set('42', activeSession);
    manager._updateDbStatus = async (...args) => updates.push(args);

    await manager._handleConnectionUpdate('42', staleSession, staleSession.socket, {
        connection: 'close',
    });

    assert.equal(activeSession.status, 'connected');
    assert.deepEqual(updates, []);
    assert.equal(manager.reconnectTimers.has('42'), false);
});

test('persists reconnecting state after an unexpected socket close', async () => {
    const manager = new SessionManager();
    const socket = {};
    const session = { socket, status: 'connected', stopping: false, reconnectAttempts: 0 };
    const updates = [];

    manager.sessions.set('42', session);
    manager._updateDbStatus = async (...args) => updates.push(args);

    await manager._handleConnectionUpdate('42', session, socket, {
        connection: 'close',
        lastDisconnect: { error: new Error('connection lost') },
    });

    assert.equal(session.status, 'connecting');
    assert.deepEqual(updates, [['42', 'connecting']]);
    assert.equal(manager.reconnectTimers.has('42'), true);

    manager.clearReconnectTimer('42');
});

test('clears credentials and stops retrying after WhatsApp logs the user out', async () => {
    const manager = new SessionManager();
    const socket = {};
    const session = { socket, status: 'connected', stopping: false, reconnectAttempts: 0 };
    const calls = [];

    manager.sessions.set('42', session);
    manager._clearAuthState = async (adminId) => calls.push(`clear:${adminId}`);
    manager._updateDbStatus = async (adminId, status) => calls.push(`db:${adminId}:${status}`);

    await manager._handleConnectionUpdate('42', session, socket, {
        connection: 'close',
        lastDisconnect: {
            error: {
                output: { statusCode: DisconnectReason.loggedOut },
                message: 'logged out',
            },
        },
    });

    assert.equal(manager.getSession('42'), null);
    assert.equal(manager.reconnectTimers.has('42'), false);
    assert.equal(session.stopping, true);
    assert.deepEqual(calls, ['clear:42', 'db:42:disconnected']);
});
