const { getPool } = require('./database');
const { proto } = require('@whiskeysockets/baileys');
const { BufferJSON, initAuthCreds } = require('@whiskeysockets/baileys');

/**
 * Custom Baileys auth state that stores credentials in MySQL instead of filesystem.
 * Each owner has their own set of auth keys identified by owner_id.
 */
async function useMySQLAuthState(ownerId) {
    const db = getPool();

    const writeData = async (keyId, data) => {
        const serialized = JSON.stringify(data, BufferJSON.replacer);
        await db.execute(
            `INSERT INTO wa_auth_keys (owner_id, key_id, key_data)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE key_data = VALUES(key_data), updated_at = CURRENT_TIMESTAMP`,
            [ownerId, keyId, serialized]
        );
    };

    const readData = async (keyId) => {
        const [rows] = await db.execute(
            'SELECT key_data FROM wa_auth_keys WHERE owner_id = ? AND key_id = ?',
            [ownerId, keyId]
        );
        if (rows.length > 0) {
            return JSON.parse(rows[0].key_data, BufferJSON.reviver);
        }
        return null;
    };

    const removeData = async (keyId) => {
        await db.execute(
            'DELETE FROM wa_auth_keys WHERE owner_id = ? AND key_id = ?',
            [ownerId, keyId]
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
 * Remove all auth keys for an owner (used when disconnecting/logging out)
 */
async function clearAuthState(ownerId) {
    const db = getPool();
    await db.execute('DELETE FROM wa_auth_keys WHERE owner_id = ?', [ownerId]);
}

module.exports = { useMySQLAuthState, clearAuthState };
