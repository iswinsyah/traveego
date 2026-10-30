import json
import os
import sys
from datetime import datetime

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from ai_crew.config import OUTPUT_POSTS_DIR, ensure_directories
from ai_crew.llm_client import LocalLLMClient
from ai_crew.intelligence_agent import IntelligenceAgent

class PostCrew:
    """
    Tim Pembuat Artikel Blog SEO & Edukasi:
    1. SEO Keyword & Search Intent Analyst
    2. Engaging Content Writer (Menulis dengan Variabel Placeholder Dinamis)
    3. Funnel Linker & Editor (Memasang Internal Link ke Halaman Rute)
    """
    def __init__(self):
        self.llm = LocalLLMClient()
        self.intel = IntelligenceAgent()
        ensure_directories()

    def generate_post(self, topic: str, target_route_key: str = "jakarta_baraya"):
        matrix = self.intel.get_cost_matrix()
        default_route = next(iter(matrix["routes"].values()))
        route_data = matrix["routes"].get(target_route_key, default_route)
        
        is_connected, _ = self.llm.check_connection()

        if is_connected:
            # ==============================================================
            # EKSEKUSI MENGGUNAKAN OTAK AI LOKAL (OLLAMA)
            # ==============================================================
            sys_prompt = (
                "Anda adalah Mbak Maya, Content Writer & SEO Specialist Dropoff.id Drop-Off. "
                "Tugas: Tulis artikel blog mendalam (600 - 1000 kata) yang wajib memenuhi standar 🟢 HIJAU YOAST SEO dan 🟢 HIJAU KETERBACAAN (Readability).\n"
                "PEDOMAN KETAT YOAST SEO & KETERBACAAN:\n"
                "1. Tentukan satu 'focus_keyword' spesifik (contoh: 'Drop Off Jakarta ke Bandung').\n"
                "2. Judul SEO (title): 40-70 karakter, wajib memuat 'focus_keyword' di bagian awal.\n"
                "3. Meta Description: 120-160 karakter, wajib memuat 'focus_keyword'.\n"
                "4. Slug: huruf kecil pemisah tanda hubung, memuat 'focus_keyword'.\n"
                "5. Paragraf 1 (Pembuka): Wajib memuat 'focus_keyword' dan 2 Outbound Link ke situs otoritas (Wikipedia Indonesia https://id.wikipedia.org/... dan Situs Pemerintah/BUMN https://bpjt.pu.go.id atau https://dephub.go.id).\n"
                "6. Paragraf Terakhir (Penutup): Wajib memuat 2 Internal Link (halaman layanan rute /... dan artikel terkait /blog/...).\n"
                "7. Kepadatan Kata Kunci: Muncul 3-5 kali secara alami di seluruh isi teks.\n"
                "8. Sub-heading: Minimal 3-4 heading H2, dengan salah satu H2 memuat 'focus_keyword'.\n"
                "9. KETERBACAAN (READABILITY YOAST):\n"
                "   - Kalimat pendek dan padat (maksimal 20 kata per kalimat, hindari kalimat bertele-tele).\n"
                "   - Paragraf ringkas (maksimal 80-100 kata per paragraf).\n"
                "   - Wajib kaya kata transisi/penghubung (minimal 25% dari total kalimat menggunakan kata seperti: 'namun', 'oleh karena itu', 'selain itu', 'sehingga', 'dengan demikian', 'terlebih', 'bahkan', 'sebagai contoh', 'di sisi lain').\n"
                "10. Variabel Dinamis: Gunakan tag harga seperti {{tol_...}}, {{harga_avanza_...}}, {{harga_innova_...}}.\n"
                "Output WAJIB JSON: {title, slug, focus_keyword, category, meta_description, featured_image, content_markdown}"
            )

            user_prompt = f"Topik Artikel: {topic}\nRute Terkait: {route_data['origin']} ke {route_data['destination']}."
            
            try:
                ai_output = self.llm.generate(sys_prompt, user_prompt, json_mode=True)
                post_content = json.loads(ai_output)
            except Exception:
                post_content = self._fallback_template(topic, route_data)
        else:
            post_content = self._fallback_template(topic, route_data)

        # Simpan file artikel dengan slug yang bersih
        raw_slug = post_content.get("slug", "artikel-sewa-drop-off")
        clean_slug = "".join([c if c.isalnum() or c in "-_" else "-" for c in raw_slug]).strip("-")
        filename = f"{clean_slug}.json"
        filepath = os.path.join(OUTPUT_POSTS_DIR, filename)
        with open(filepath, "w", encoding="utf-8") as f:
            json.dump(post_content, f, indent=2, ensure_ascii=False)

        return filepath, post_content

    def _fallback_template(self, topic: str, route_data: dict):
        dest_clean = route_data["destination"].split("(")[0].strip().lower().replace(" ", "-")
        origin_clean = route_data["origin"].split("(")[0].strip().lower().replace(" ", "-")
        slug = f"panduan-tarif-drop-off-{origin_clean}-ke-{dest_clean}"
        dest = route_data["destination"]
        origin = route_data["origin"]
        r_slug = route_data["slug"].replace('-', '_')
        focus_kw = f"Drop Off {origin} ke {dest}"

        return {
            "title": f"Panduan Biaya & Tarif Tol Drop Off {origin} ke {dest} 2026",
            "slug": slug,
            "focus_keyword": focus_kw,
            "category": "Info Tarif & Tol",
            "published_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "target_route_slug": route_data["slug"],
            "featured_image": "https://images.unsplash.com/photo-1506015391300-4802dc74de2e?auto=format&fit=crop&w=1200&q=80",
            "meta_description": f"Panduan lengkap rincian biaya sewa mobil drop off {origin} ke {dest} All-In. Termasuk estimasi tarif tol, BBM, dan jaminan tanpa uang muka 0% DP.",
            "content_markdown": (
                f"# Panduan Lengkap Perjalanan & Tarif Tol Drop Off {origin} ke {dest}\n\n"
                f"Bepergian antar kota dari {origin} ke {dest} kini semakin praktis dan nyaman. "
                f"Layanan sewa mobil **{focus_kw.lower()}** menjadi pilihan utama pelancong bisnis dan keluarga. "
                f"Hal ini didukung oleh infrastruktur jalan tol [Jalan Tol Trans Jawa](https://id.wikipedia.org/wiki/Jalan_Tol_Trans-Jawa) "
                f"serta pemeliharaan keselamatan dari [Badan Pengatur Jalan Tol (BPJT) PUPR](https://bpjt.pu.go.id/).\n\n"
                f"Namun, banyak calon penumpang masih bingung memperhitungkan anggaran perjalanan. "
                f"Oleh karena itu, kami menyajikan rincian tarif tol dan perbandingan biaya secara transparan.\n\n"
                f"---\n\n"
                f"## 1. Rincian Tarif Tol {focus_kw}\n\n"
                f"Untuk rute {origin} menuju {dest}, jalur utama yang dilalui adalah {route_data['toll_details']}. "
                f"Estimasi total biaya tarif tol Golongan 1 saat ini adalah **{{{{tol_{r_slug}}}}}**.\n\n"
                f"Selain itu, pengemudi Dropoff.id siap memilihkan gerbang keluar paling strategis di kota tujuan. "
                f"Dengan demikian, waktu tempuh perjalanan Anda menjadi jauh lebih efisien.\n\n"
                f"---\n\n"
                f"## 2. Estimasi Konsumsi Bahan Bakar (BBM)\n\n"
                f"Jarak tempuh rute ini tercatat sekitar {route_data['distance_km']} km. "
                f"Rata-rata konsumsi bensin mobil keluarga (Avanza) membutuhkan biaya sekitar **{{{{bbm_avanza_{r_slug}}}}}**.\n\n"
                f"Di sisi lain, menyetir sendiri selama berjam-jam sering kali menguras tenaga fisik. "
                f"Bahkan, Anda masih harus memikirkan biaya perjalanan pulang jika membawa kendaraan sendiri.\n\n"
                f"---\n\n"
                f"## 3. Keunggulan Paket All-In Dropoff.id Tanpa Uang Muka\n\n"
                f"Daripada lelah menyetir di jalan tol, Dropoff.id menyediakan layanan sewa mobil {focus_kw.lower()} privat:\n"
                f"- **Avanza All New**: Hanya **{{{{harga_avanza_{r_slug}}}}}** All-In.\n"
                f"- **Innova Reborn**: Hanya **{{{{harga_innova_{r_slug}}}}}** All-In.\n\n"
                f"Semua unit sudah mencakup mobil privat, sopir ramah, BBM, dan tol penuh tanpa biaya tambahan. "
                f"Terlebih lagi, sistem pemesanan kami bebas uang muka (0% DP). Pembayaran dilakukan tunai di lokasi tujuan.\n\n"
                f"---\n\n"
                f"## 4. Tips Jadwal Keberangkatan Fleksibel\n\n"
                f"Sebelum bepergian, pastikan Anda menentukan jam penjemputan di luar jam macet. "
                f"Sebagai contoh, berangkat di pagi hari sebelum pukul 06.00 WIB sangat disarankan untuk perjalanan santai.\n\n"
                f"Untuk konsultasi rute dan pemesanan unit, Anda dapat membuka [Halaman Layanan Drop Off {dest}](/{route_data['slug']}) "
                f"atau membaca [Panduan Perjalanan Drop Off Lainnya](/blog/panduan-tarif-drop-off-jakarta-ke-bandung) untuk info mobilitas lengkap."
            )
        }
