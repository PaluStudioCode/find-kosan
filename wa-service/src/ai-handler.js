const { getPool } = require('./database');
const { toolDefinitions, executeTool } = require('./ai-tools');

const ROUTER_BASE_URL = (process.env['9ROUTER_BASE_URL'] || 'http://localhost:20128').replace(/\/+$/, '');
const ROUTER_API_KEY = process.env['9ROUTER_API_KEY'] || '';
const ROUTER_MODEL = process.env['9ROUTER_DEFAULT_MODEL'] || 'ag/gemini-3.6-flash-high(high)';

const MAX_TOOL_ITERATIONS = 5;
const HISTORY_LIMIT = 20;
const RATE_LIMIT_WINDOW_MS = 60 * 1000;
const RATE_LIMIT_MAX = 20;

const rateLimitMap = new Map();

const BASE_SYSTEM_PROMPT = `Kamu adalah FindKos AI, asisten customer service platform CariKosanMu (FindKosan).
Kamu HANYA menjawab dalam Bahasa Indonesia.

## Tentang Platform
CariKosanMu adalah platform pencarian kos dan pengelolaan sewa properti. User bisa mencari kos, booking kamar, dan melakukan pembayaran secara online.

## Cara Kerja Platform
1. **Cari Kos**: User mencari kos berdasarkan lokasi, harga, dan fasilitas di website
2. **Booking**: User memilih kamar dan melakukan booking melalui website
3. **Pembayaran**: User membayar tagihan bulanan melalui website (transfer bank / QRIS)
4. **Notifikasi**: Sistem mengirim pengingat tagihan via WhatsApp

## Panduan Menjawab
- Selalu ramah, sopan, dan helpful
- Gunakan tools untuk mengambil data real-time, JANGAN mengarang data
- Sebutkan harga dalam format Rupiah (Rp500.000)
- Jangan terlalu panjang, jawab ringkas dan to the point
- Gunakan emoji secukupnya untuk membuat percakapan lebih ramah

## Batasan Penting
- Kamu HANYA bisa memberikan informasi, TIDAK bisa melakukan aksi (booking, bayar, edit profil, dll)
- Untuk aksi, arahkan user ke website CariKosanMu
- Jangan mengarang data kos, harga, atau informasi yang tidak ada di hasil tool
- Jika tool mengembalikan error atau data kosong, sampaikan dengan jujur
- Jangan memberikan informasi pribadi user lain (nomor WA, dll) kecuali kontak publik kos`;

const NON_TEXT_REPLY = 'Maaf, saat ini saya hanya bisa memproses pesan teks. Silakan kirim pertanyaan Anda dalam bentuk teks ya. 🙏';

function formatForWhatsApp(text) {
    if (!text) return text;
    
    // Ganti **teks** menjadi *teks* (Bold)
    let formatted = text.replace(/\*\*(.*?)\*\*/g, '*$1*');
    
    // Ganti # Header menjadi *Header*
    formatted = formatted.replace(/^#+\s*(.*)$/gm, '*$1*');
    
    // Ganti [Teks](URL) menjadi URL
    formatted = formatted.replace(/\[(.*?)\]\((.*?)\)/g, '$1 ($2)');
    
    return formatted;
}

function checkRateLimit(phoneNumber) {
    const now = Date.now();
    let entry = rateLimitMap.get(phoneNumber);

    if (!entry || now - entry.windowStart > RATE_LIMIT_WINDOW_MS) {
        entry = { windowStart: now, count: 0 };
        rateLimitMap.set(phoneNumber, entry);
    }

    entry.count++;
    return entry.count <= RATE_LIMIT_MAX;
}

function formatForWhatsApp(text) {
    if (!text) return text;
    
    // Ganti **teks** menjadi *teks* (Bold)
    let formatted = text.replace(/\*\*(.*?)\*\*/g, '*$1*');
    
    // Ganti # Header menjadi *Header*
    formatted = formatted.replace(/^#+\s*(.*)$/gm, '*$1*');
    
    // Ganti [Teks](URL) menjadi URL
    formatted = formatted.replace(/\[(.*?)\]\((.*?)\)/g, '$1 ($2)');
    
    return formatted;
}

async function getChatHistory(phoneNumber) {
    const db = getPool();
    const [rows] = await db.execute(
        `SELECT role, content FROM wa_conversations
         WHERE phone_number = ?
         ORDER BY created_at DESC LIMIT ${HISTORY_LIMIT}`,
        [phoneNumber]
    );
    return rows.reverse().map((row) => ({
        role: row.role,
        content: row.content,
    }));
}

async function clearChatHistory(phoneNumber) {
    const db = getPool();
    await db.execute('DELETE FROM wa_conversations WHERE phone_number = ?', [phoneNumber]);
}

async function saveChatMessage(phoneNumber, role, content) {
    const db = getPool();
    const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
    await db.execute(
        `INSERT INTO wa_conversations (phone_number, role, content, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?)`,
        [phoneNumber, role, content, now, now]
    );
}

async function callLLM(messages, tools) {
    const response = await fetch(`${ROUTER_BASE_URL}/v1/chat/completions`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${ROUTER_API_KEY}`,
        },
        body: JSON.stringify({
            model: ROUTER_MODEL,
            messages,
            tools: tools,
            stream: false, // Ensure non-streaming response
        }),
    });

    if (!response.ok) {
        const text = await response.text();
        throw new Error(`LLM API error ${response.status}: ${text}`);
    }

    const textResponse = await response.text();

    try {
        // Coba parse sebagai JSON utuh
        return JSON.parse(textResponse);
    } catch (e) {
        // Fallback jika API membandel mengembalikan stream (SSE: "data: {...}")
        // Ambil baris pertama atau blok JSON yang valid
        const lines = textResponse.split('\n').filter(line => line.startsWith('data: ') && line !== 'data: [DONE]');
        
        if (lines.length > 0) {
            console.log('[AI] Handling unexpected SSE stream format from LLM gateway');
            // Dalam kasus stream, biasanya kita kumpulkan semua chunk.
            // Tapi karena API-nya OpenAI compatible, kita gabung content-nya jika ini delta
            let finalContent = '';
            let finalToolCalls = [];
            
            for (const line of lines) {
                try {
                    const chunk = JSON.parse(line.replace('data: ', '').trim());
                    const delta = chunk.choices?.[0]?.delta;
                    
                    if (delta?.content) {
                        finalContent += delta.content;
                    }
                    if (delta?.tool_calls) {
                        for (const tool of delta.tool_calls) {
                            if (!finalToolCalls[tool.index]) {
                                finalToolCalls[tool.index] = { ...tool, function: { arguments: '' } };
                            }
                            if (tool.function?.name) finalToolCalls[tool.index].function.name = tool.function.name;
                            if (tool.function?.arguments) finalToolCalls[tool.index].function.arguments += tool.function.arguments;
                        }
                    }
                } catch (err) {
                    // Ignore malformed chunk
                }
            }
            
            return {
                choices: [{
                    message: {
                        content: finalContent || null,
                        tool_calls: finalToolCalls.length > 0 ? finalToolCalls.filter(Boolean) : undefined
                    }
                }]
            };
        }
        
        throw new Error(`Invalid JSON response from LLM: ${textResponse.substring(0, 100)}...`);
    }
}

async function handleIncomingMessage(phoneNumber, messageText) {
    if (!checkRateLimit(phoneNumber)) {
        return 'Mohon maaf, Anda terlalu sering mengirim pesan. Silakan tunggu sebentar lalu coba lagi. 🙏';
    }

    const trimmedMessage = messageText.trim().toLowerCase();
    
    // Command handler
    if (trimmedMessage === '/reset' || trimmedMessage === '/clear') {
        try {
            await clearChatHistory(phoneNumber);
            return '✅ Riwayat percakapan Anda dengan saya telah berhasil dihapus. Mari mulai dari awal! Ada yang bisa saya bantu?';
        } catch (error) {
            console.error(`[AI] Error clearing chat for ${phoneNumber}:`, error.message);
            return 'Maaf, terjadi kesalahan saat mereset percakapan. Silakan coba lagi.';
        }
    }

    try {
        // 1. Identifikasi user secara otomatis sebelum memanggil LLM
        let userInfoStr = '';
        try {
            const identity = await executeTool('identify_user', { phone_number: phoneNumber });
            
            // Format identitas untuk disuntikkan ke System Prompt
            if (identity && identity.role !== 'guest' && identity.user) {
                const u = identity.user;
                userInfoStr = `\n\n## INFO PENGIRIM PESAN INI (JANGAN TANYAKAN NOMORNYA LAGI)\n` +
                              `- Nama: ${u.name}\n` +
                              `- Nomor WA: ${u.whatsapp_number}\n` +
                              `- Role: ${u.role === 'admin' || u.role === 'super_admin' ? 'Pemilik Kos / Admin' : 'Penyewa / User'}\n`;
                
                if (u.role === 'admin') {
                    userInfoStr += `- Jumlah Kos Dimiliki: ${u.kos_count}\n` +
                                   `\nSebagai Pemilik Kos, user ini berhak mengecek ringkasan propertinya menggunakan tool get_owner_summary. Selalu gunakan nomor WA di atas untuk parameter phone_number.`;
                } else if (u.role === 'user') {
                    userInfoStr += `- Punya Sewa Aktif: ${u.active_tenancy ? 'Ya' : 'Tidak'}\n` +
                                   `\nSebagai Penyewa, user ini berhak mengecek tagihan dan masa sewanya menggunakan tool get_user_tenancy dan get_user_invoices. Selalu gunakan nomor WA di atas untuk parameter phone_number.`;
                }
            } else {
                userInfoStr = `\n\n## INFO PENGIRIM PESAN INI\n- Role: guest (Belum terdaftar atau belum login)\n- Nomor WA: ${phoneNumber}\n\nSebagai guest, arahkan user untuk mendaftar jika ingin melihat tagihan atau menyewa kos.`;
            }
        } catch (err) {
            console.error('[AI] Gagal mengidentifikasi user:', err.message);
            userInfoStr = `\n\n## INFO PENGIRIM PESAN INI\n- Nomor WA: ${phoneNumber}\n- Status: Tidak dapat diidentifikasi karena gangguan server.`;
        }

        const dynamicSystemPrompt = BASE_SYSTEM_PROMPT + userInfoStr;

        // 2. Ambil Chat History
        const history = await getChatHistory(phoneNumber);

        const messages = [
            { role: 'system', content: dynamicSystemPrompt },
            ...history,
            { role: 'user', content: messageText },
        ];

        let iteration = 0;

        // 3. Kita hapus 'identify_user' dari list tools yang dikirim ke LLM karena sudah kita proses di atas
        const availableTools = toolDefinitions.filter(t => t.function.name !== 'identify_user');

        while (iteration < MAX_TOOL_ITERATIONS) {
            iteration++;
            const result = await callLLM(messages, availableTools);
            const choice = result.choices?.[0];

            if (!choice) {
                throw new Error('Empty response from LLM');
            }

            const message = choice.message;

            if (message.tool_calls && message.tool_calls.length > 0) {
                messages.push(message);

                for (const toolCall of message.tool_calls) {
                    const toolName = toolCall.function.name;
                    const toolArgs = toolCall.function.arguments;

                    console.log(`[AI] Tool call: ${toolName}(${toolArgs})`);

                    const toolResult = await executeTool(toolName, toolArgs);

                    messages.push({
                        role: 'tool',
                        tool_call_id: toolCall.id,
                        content: JSON.stringify(toolResult),
                    });
                }

                continue;
            }

            const rawReply = message.content || 'Maaf, saya tidak bisa memproses permintaan Anda saat ini.';
            const reply = formatForWhatsApp(rawReply);

            await saveChatMessage(phoneNumber, 'user', messageText);
            await saveChatMessage(phoneNumber, 'assistant', reply);

            return reply;
        }

        return 'Maaf, saya membutuhkan waktu lebih lama untuk memproses permintaan Anda. Silakan coba lagi.';
    } catch (error) {
        console.error(`[AI] Error handling message from ${phoneNumber}:`, error.message);
        return 'Maaf, terjadi gangguan pada sistem. Silakan coba lagi nanti. 🙏';
    }
}

module.exports = { handleIncomingMessage, NON_TEXT_REPLY };
