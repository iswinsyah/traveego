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

    def generate_post(self, topic: str, target_route_key: str = "jakarta_bandung"):
        matrix = self.intel.get_cost_matrix()
        route_data = matrix["routes"].get(target_route_key, matrix["routes"]["jakarta_bandung"])
        
        is_connected, _ = self.llm.check_connection()

        if is_connected:
            # ==============================================================
            # EKSEKUSI MENGGUNAKAN OTAK AI LOKAL (OLLAMA)
            # ==============================================================
            sys_prompt = (
                "Anda adalah Tim Penulis Konten SEO Traveego Drop-Off. "
                "Tugas: Tulis artikel blog mendalam (800 - 1200 kata) yang sangat informatif, ramah, "
                "dan menjawab pertanyaan pengguna di Google. "
                "PENTING: Jangan tulis angka tarif mati! Gunakan tag variabel dinamis seperti: "
                "{{tol_jakarta_bandung}}, {{harga_avanza_bandung}}, {{harga_innova_bandung}}. "
                "Sertakan ajakan memesan dan link ke halaman rute Traveego di tengah dan akhir tulisan. "
                "Output dalam format JSON: {title, slug, meta_description, category, content_html, faq_schema}"
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

        return {
            "title": f"Rincian Biaya & Tarif Tol Drop Off {origin} ke {dest} Terbaru 2026",
            "slug": slug,
            "category": "Info Tarif & Tol",
            "published_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "target_route_slug": route_data["slug"],
            "meta_description": f"Panduan lengkap estimasi biaya sewa mobil drop-off {origin} ke {dest}. Termasuk rincian tarif Tol MBZ, BBM, dan paket All-In tanpa DP.",
            "content_markdown": (
                f"# Panduan Lengkap Perjalanan & Tarif Tol {origin} ke {dest}\n\n"
                f"Bepergian dari {origin} ke {dest} kini semakin nyaman berkat tersambungnya jalan tol bebas hambatan. "
                f"Namun, banyak keluarga atau pelancong bisnis yang masih bingung memperhitungkan total biaya jika "
                f"harus menyetir sendiri dibanding menyewa mobil drop-off privat.\n\n"
                f"## 1. Rincian Tarif Tol Terkini\n"
                f"Untuk rute {origin} menuju {dest}, jalur utama yang dilalui adalah {route_data['toll_details']}. "
                f"Estimasi total biaya tarif tol Golongan 1 saat ini adalah **{{{{tol_{route_data['slug'].replace('-', '_')}}}}}**.\n\n"
                f"## 2. Estimasi Konsumsi Bahan Bakar (BBM)\n"
                f"Dengan jarak tempuh sekitar {route_data['distance_km']} km, rata-rata konsumsi bensin mobil keluarga (Avanza) "
                f"membutuhkan biaya sekitar **{{{{bbm_avanza_{route_data['slug'].replace('-', '_')}}}}}**.\n\n"
                f"## 3. Solusi Hemat & Anti Ribet: Paket All-In Traveego\n"
                f"Daripada lelah menyetir di jalan tol dan membayar bensin serta tol secara terpisah, "
                f"Traveego menyediakan layanan sewa mobil Drop-Off privat khusus satu arah dengan tarif All-In:\n"
                f"- **Avanza All New**: Hanya **{{{{harga_avanza_{route_data['slug'].replace('-', '_')}}}}}**\n"
                f"- **Innova Reborn**: Hanya **{{{{harga_innova_{route_data['slug'].replace('-', '_')}}}}}**\n\n"
                f"Semua unit sudah mencakup mobil privat, sopir berpengalaman, BBM, dan Tol MBZ penuh tanpa ada biaya tersembunyi.\n\n"
                f"> **Mau langsung pesan atau konsultasi rute?**\n"
                f"> Anda bisa langsung mengecek detail rute kami di [Halaman Layanan Drop Off {dest}](/{route_data['slug']}) "
                f"atau langsung chat WhatsApp ke admin Traveego tanpa uang muka (0% DP)."
            )
        }
