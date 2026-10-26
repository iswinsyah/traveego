import json
import os
import sys
from datetime import datetime

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from ai_crew.config import OUTPUT_PAGES_DIR, ensure_directories
from ai_crew.llm_client import LocalLLMClient
from ai_crew.intelligence_agent import IntelligenceAgent

class PageCrew:
    """
    Tim Pembuat Halaman Penawaran (Landing Page Rute):
    1. Route & Market Researcher
    2. Direct-Response Sales Copywriter
    3. Page Architect & Schema Specialist
    """
    def __init__(self):
        self.llm = LocalLLMClient()
        self.intel = IntelligenceAgent()
        ensure_directories()

    def generate_page_for_route(self, route_key: str):
        matrix = self.intel.get_cost_matrix()
        if route_key not in matrix["routes"]:
            raise ValueError(f"Rute '{route_key}' tidak ditemukan di cost matrix.")

        route_data = matrix["routes"][route_key]
        origin = route_data["origin"]
        dest = route_data["destination"]
        dist = route_data["distance_km"]
        toll = route_data["toll_details"]
        prices = route_data["pricing"]

        is_connected, _ = self.llm.check_connection()

        if is_connected:
            # ==============================================================
            # EKSEKUSI MENGGUNAKAN OTAK AI LOKAL (OLLAMA)
            # ==============================================================
            sys_prompt = (
                "Anda adalah Tim AI spesialis Landing Page Sewa Mobil Drop-Off Traveego. "
                "Tugas Anda: Menghasilkan struktur Landing Page Penjualan yang sangat persuasif, "
                "berfokus pada konversi (0% DP, Bayar ke sopir, All-In Tol & BBM, Mobil Privat). "
                "Output WAJIB dalam format JSON murni dengan kunci: "
                "meta_title, meta_description, hero_headline, hero_subheadline, "
                "usp_points, vehicle_table, faq_items, cta_whatsapp_text."
            )

            user_prompt = (
                f"Buat Landing Page Rute: {origin} ke {dest}.\n"
                f"Jarak: {dist} km. Jalur Tol: {toll}.\n"
                f"Tarif Resmi All-In: Avanza ({prices['avanza']['formatted_price']}), "
                f"Innova ({prices['innova']['formatted_price']}), Hiace ({prices['hiace']['formatted_price']})."
            )

            try:
                ai_output = self.llm.generate(sys_prompt, user_prompt, json_mode=True)
                page_content = json.loads(ai_output)
            except Exception:
                page_content = self._fallback_template(route_key, route_data)
        else:
            # Fallback template berbasis data riil jika AI lokal belum menyala
            page_content = self._fallback_template(route_key, route_data)

        # Simpan file hasil generasi
        filename = f"{route_data['slug']}.json"
        filepath = os.path.join(OUTPUT_PAGES_DIR, filename)
        with open(filepath, "w", encoding="utf-8") as f:
            json.dump(page_content, f, indent=2, ensure_ascii=False)

        return filepath, page_content

    def _fallback_template(self, route_key: str, r: dict):
        origin = r["origin"]
        dest = r["destination"]
        slug = r["slug"]
        avanza_price = r["pricing"]["avanza"]["formatted_price"]
        innova_price = r["pricing"]["innova"]["formatted_price"]
        hiace_price = r["pricing"]["hiace"]["formatted_price"]

        return {
            "slug": slug,
            "generated_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "route_key": route_key,
            "meta_title": f"Sewa Mobil Drop Off {origin} - {dest} All-In 0% DP | Traveego",
            "meta_description": f"Jasa sewa mobil Drop-Off privat {origin} ke {dest}. Tarif All-In mulai {avanza_price} sudah termasuk Tol MBZ, BBM & Sopir. Bayar tunai di tujuan tanpa DP.",
            "hero": {
                "headline": f"SEWA MOBIL DROP OFF {dest.upper()}",
                "subheadline": f"Perjalanan privat satu arah dari {origin} ke {dest} tanpa repot. 100% All-In Tol & BBM, 0% Uang Muka, bayar langsung ke sopir saat tiba.",
                "cta_button_text": "Pesan Unit Sekarang (0% DP)",
                "wa_text": f"Halo Traveego, saya mau pesan Drop-Off {origin} ke {dest}"
            },
            "pricing_cards": [
                {
                    "vehicle": "Avanza All New",
                    "price": avanza_price,
                    "features": ["4-5 Penumpang", "Tol Penuh MBZ Termasuk", "BBM All-In", "Door-to-Door", "Bayar di Lokasi"]
                },
                {
                    "vehicle": "Innova Reborn",
                    "price": innova_price,
                    "popular": True,
                    "features": ["6-7 Penumpang", "Suspensi Paling Nyaman", "Tol Penuh MBZ Termasuk", "BBM All-In", "Door-to-Door"]
                },
                {
                    "vehicle": "Hiace Commuter / Premio",
                    "price": hiace_price,
                    "features": ["10-14 Penumpang", "Kabin Lega Rombongan", "Tol Penuh Termasuk", "BBM All-In", "Door-to-Door"]
                }
            ],
            "schema_markup": {
                "@context": "https://schema.org",
                "@type": "Service",
                "serviceType": "Car Rental Drop-Off",
                "provider": {"@type": "LocalBusiness", "name": "Traveego Drop-Off"},
                "areaServed": dest,
                "offers": {
                    "@type": "Offer",
                    "price": str(r["pricing"]["avanza"]["all_in_total"]),
                    "priceCurrency": "IDR"
                }
            }
        }
