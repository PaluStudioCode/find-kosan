const { getPool } = require('./database');
const { proto } = require('@whiskeysockets/baileys');
const { BufferJSON, initAuthCreds } = require('@whiskeysockets/baileys');

/**
 * Custom Baileys auth state that stores credentials in MySQL instead of filesystem.
 * Each Admin has their own set of auth keys identified by admin_id.
 */
async function useMySQLAuthState(adminId) {
    const db = getPool();

    const writeData = async (keyId, data) => {
        const serialized = JSON.stringify(data, BufferJSON.replacer);
        await db.execute(
            `INSERT INTO wa_auth_keys (admin_id, key_id, key_data)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE key_data = VALUES(key_data), updated_at = CURRENT_TIMESTAMP`,
            [adminId, keyId, serialized]
        );
    };

    const readData = async (keyId) => {
        const [rows] = await db.execute(
            'SELECT key_data FROM wa_auth_keys WHERE admin_id = ? AND key_id = ?',
            [adminId, keyId]
        );
        if (rows.length > 0) {
            return JSON.parse(rows[0].key_data, BufferJSON.reviver);
        }
        return null;
    };

    const removeData = async (keyId) => {
        await db.execute(
            'DELETE FROM wa_auth_keys WHERE admin_id = ? AND key_id = ?',
            [adminId, keyId]
        );
    };

    // Load or initialize creds
    let creds = await readData('creds');
    if (!creds) {
        creds = initAuthCreds();
        await writeData('creds', creds);
    }

    return {
        state: {
            creds,
            keys: {
                get: async (type, ids) => {
                    const data = {};
                    await Promise.all(
                        ids.map(async (id) => {
                            const keyId = `${type}-${id}`;
                            let value = await readData(keyId);
                            if (type === 'app-state-sync-key' && value) {
                                value = proto.Message.AppStateSyncKeyData.fromObject(value);
                            }
                            data[id] = value;
                        })
                    );
                    return data;
                },
                set: async (data) => {
                    const tasks = [];
                    for (const category in data) {
                        for (const id in data[category]) {
                            const value = data[category][id];
                            const keyId = `${category}-${id}`;
                            if (value) {
                                tasks.push(writeData(keyId, value));
                            } else {
                                tasks.push(removeData(keyId));
                            }
                        }
                    }
                    await Promise.all(tasks);
                },
            },
        },
        saveCreds: async () => {
            await writeData('creds', creds);
        },
    };
}

/**
 * Remove all auth keys for an Admin (used when disconnecting/logging out)
 */
async function clearAuthState(adminId) {
    const db = getPool();
    await db.execute('DELETE FROM wa_auth_keys WHERE admin_id = ?', [adminId]);
}

module.exports = { useMySQLAuthState, clearAuthState };
