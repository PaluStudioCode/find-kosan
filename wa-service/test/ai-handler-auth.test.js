const assert = require('node:assert/strict');
const test = require('node:test');

const { sanitizeAuthenticatedReply } = require('../src/ai-handler');

test('does not ask an already verified user to login again', () => {
    const reply = sanitizeAuthenticatedReply(
        'Silakan /login terlebih dahulu agar saya dapat membantu.',
        { phoneNumber: '6281234567890', verificationToken: 'token', expiresAt: Date.now() + 1000 },
    );

    assert.equal(
        reply,
        'Akun Anda sudah terverifikasi. Namun, informasi atau fungsi yang Anda tanyakan belum tersedia di CariKosanMu. Saya dapat membantu pencarian kos, informasi akun yang tersedia, dan laporan penyewa.',
    );
});

test('keeps an honest response for a verified user', () => {
    const reply = sanitizeAuthenticatedReply(
        'Maaf, informasi tersebut belum tersedia di CariKosanMu.',
        { phoneNumber: '6281234567890', verificationToken: 'token', expiresAt: Date.now() + 1000 },
    );

    assert.equal(reply, 'Maaf, informasi tersebut belum tersedia di CariKosanMu.');
});
