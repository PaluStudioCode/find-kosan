const mysql = require('mysql2/promise');

let pool;

function getPool() {
    if (!pool) {
        pool = mysql.createPool({
            host: process.env.DB_HOST || '127.0.0.1',
            port: parseInt(process.env.DB_PORT || '3306'),
            user: process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || '',
            database: process.env.DB_NAME || 'kos_online',
            waitForConnections: true,
            connectionLimit: 10,
            queueLimit: 0,
        });
    }
    return pool;
}

/**
 * Initialize database tables for wa-service if they don't exist.
 * These tables are also created by Laravel migrations, but this ensures
 * the Node.js service can run independently.
 */
async function initTables() {
    const db = getPool();

    await db.execute(`
        CREATE TABLE IF NOT EXISTS wa_sessions (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id BIGINT UNSIGNED NOT NULL,
            status ENUM('disconnected', 'connecting', 'connected') NOT NULL DEFAULT 'disconnected',
            phone_number VARCHAR(30) NULL,
            connected_at TIMESTAMP NULL,
            disconnected_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_admin_id (admin_id)
        )
    `);

    await db.execute(`
        CREATE TABLE IF NOT EXISTS wa_auth_keys (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id BIGINT UNSIGNED NOT NULL,
            key_id VARCHAR(255) NOT NULL,
            key_data LONGTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_admin_key (admin_id, key_id)
        )
    `);

    console.log('[DB] Tables initialized.');
}

module.exports = { getPool, initTables };
