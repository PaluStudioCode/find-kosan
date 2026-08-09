const assert = require('node:assert/strict');
const test = require('node:test');

const { handleIncomingMessage } = require('../src/ai-handler');

test('collects a tenant report, asks for a tenancy, and sends it only after YA', async (context) => {
    const originalFetch = global.fetch;
    const originalConsoleLog = console.log;
    const phoneNumber = '628777000111';
    const sentReports = [];

    context.after(() => {
        global.fetch = originalFetch;
        console.log = originalConsoleLog;
    });

    console.log = () => {};
    global.fetch = async (url, options) => {
        const requestUrl = new URL(url);

        if (requestUrl.pathname === `/api/ai/identify-user/${phoneNumber}`) {
            return {
                ok: true,
                json: async () => ({
                    role: 'user',
                    user: { id: 1, role: 'user' },
                }),
            };
        }

        if (requestUrl.pathname === `/api/ai/user/${phoneNumber}/tenancy`) {
            assert.equal(options.headers['X-AI-Requester-Phone'], phoneNumber);
            assert.equal(options.headers['X-AI-Verification-Token'], 'verified-session-token');
            return {
                ok: true,
                json: async () => ({
                    tenancies: [
                        { id: 11, kos_name: 'Kos Mawar', room_number: 'A1' },
                        { id: 12, kos_name: 'Kos Melati', room_number: 'B2' },
                    ],
                }),
            };
        }

        if (requestUrl.pathname === `/api/ai/user/${phoneNumber}/report`) {
            assert.equal(options.headers['X-AI-Requester-Phone'], phoneNumber);
            assert.equal(options.headers['X-AI-Verification-Token'], 'verified-session-token');
            sentReports.push(JSON.parse(options.body));
            return {
                ok: true,
                json: async () => ({
                    success: true,
                    message: 'Laporan Anda telah diteruskan ke pemilik kos.',
                }),
            };
        }

        if (requestUrl.pathname === '/api/ai/request-otp') {
            return {
                ok: true,
                json: async () => ({ success: true, message: 'Kode OTP telah dikirimkan.' }),
            };
        }

        if (requestUrl.pathname === '/api/ai/verify-otp') {
            return {
                ok: true,
                json: async () => ({
                    success: true,
                    message: 'Verifikasi berhasil!',
                    verification_token: 'verified-session-token',
                }),
            };
        }

        assert.fail(`Unexpected request: ${requestUrl.pathname}`);
    };

    const loginReply = await handleIncomingMessage(phoneNumber, `/login ${phoneNumber}`);
    assert.equal(loginReply, 'Kode OTP telah dikirimkan.');
    const otpReply = await handleIncomingMessage(phoneNumber, '/otp 123456');
    assert.match(otpReply, /Autentikasi berhasil/);

    const choiceReply = await handleIncomingMessage(phoneNumber, 'Saya mau melaporkan AC kamar rusak.');
    assert.match(choiceReply, /Kos Mawar/);
    assert.match(choiceReply, /Kos Melati/);

    const confirmationReply = await handleIncomingMessage(phoneNumber, '1');
    assert.match(confirmationReply, /Ketik \*YA\* untuk kirim/);

    const sentReply = await handleIncomingMessage(phoneNumber, 'YA');
    assert.equal(sentReply, 'Laporan Anda telah diteruskan ke pemilik kos.');
    assert.deepEqual(sentReports, [{
        tenancy_id: 11,
        report: 'AC kamar rusak.',
    }]);
});
