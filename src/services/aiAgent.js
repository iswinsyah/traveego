require('dotenv').config();

const OLLAMA_HOST = process.env.OLLAMA_HOST || 'http://127.0.0.1:11434';
const OLLAMA_MODEL = process.env.OLLAMA_MODEL || 'qwen2.5-coder:7b';

// Prompt persona CS AI Dropoff.id
const SYSTEM_PROMPT = `Anda adalah "Hasna", Customer Service AI resmi dari Dropoff.id (https://dropoff.id).
Dropoff.id melayani jasa sewa mobil khusus Drop Off (Antar Langsung Sekali Jalan) antarkota, utamanya koridor:
1. Jakarta - Bandung (Mulai Rp 700.000 all-in tol Cipularang & sopir)
2. Bandara Soekarno Hatta - Bandung (Mulai Rp 750.000 all-in tol)
3. Bandung - Jakarta / Bandara Soetta (Mulai Rp 700.000 - Rp 750.000)
4. Surabaya - Malang/Batu (Mulai Rp 450.000 all-in)

Pilihan Unit Mobil:
- Avanza / Xpander (Kapasitas: 1-4 orang, 2 koper)
- Innova Reborn (Kapasitas: 1-5 orang, 4 koper, kenyamanan bisnis)
- Hiace Commuter/Premio (Kapasitas: 8-12 orang, rombongan keluarga)

Gaya Komunikasi:
- Ramah, sopan, menggunakan panggilan "Kak / Kakak"
- Sigap menanyakan: Titik jemput lengkap, tujuan di Bandung/kota tujuan, tanggal & jam keberangkatan, serta jumlah penumpang/koper
- Berikan harga pasti yang jelas dan informasikan bahwa pembayaran dilakukan CASH langsung ke sopir setelah tiba di tempat tujuan.`;

async function chatWithAI(userMessage, conversationHistory = []) {
    try {
        const messages = [
            { role: 'system', content: SYSTEM_PROMPT },
            ...conversationHistory,
            { role: 'user', content: userMessage }
        ];

        // Fetch ke API Ollama lokal
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 12000); // 12 detik timeout

        const response = await fetch(`${OLLAMA_HOST}/api/chat`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                model: OLLAMA_MODEL,
                messages: messages,
                stream: false
            }),
            signal: controller.signal
        });

        clearTimeout(timeout);

        if (!response.ok) {
            throw new Error(`Ollama status: ${response.status}`);
        }

        const data = await response.json();
        return data.message.content;
    } catch (err) {
        console.warn('Ollama Local Offline / Timeout, menggunakan Respon Cerdas Cadangan:', err.message);
        
        // Smart fallback jika Ollama lokal sedang dimatikan
        const lower = userMessage.toLowerCase();
        if (lower.includes('harga') || lower.includes('tarif') || lower.includes('berapa')) {
            return "Halo Kak! Untuk layanan Drop-Off sekali jalan Jakarta - Bandung tarif kami mulai Rp 700.000 (Avanza) dan Rp 950.000 (Innova Reborn) sudah ALL-IN termasuk mobil, sopir, dan BBM/Tol Cipularang. Boleh diinfokan rencana penjemputan di mana dan untuk jam berapa Kak?";
        }
        if (lower.includes('bandara') || lower.includes('soetta')) {
            return "Halo Kak! Untuk penjemputan dari Bandara Soekarno-Hatta (Terminal 1, 2, atau 3) ke Bandung, tarif kami Rp 750.000 (Avanza) atau Rp 1.050.000 (Innova Reborn) all-in tol langsung diantar sampai depan pintu rumah/hotel di Bandung. Rencana tiba di Soetta jam berapa Kak?";
        }
        return "Halo Kak! Terima kasih sudah menghubungi Dropoff.id. Kami melayani antar langsung Jakarta - Bandung, Bandara Soetta, dan kota lainnya dengan armada bersih dan sopir berpengalaman. Mau booking untuk tanggal berapa dan berapa orang penumpangnya Kak?";
    }
}

module.exports = {
    chatWithAI
};
