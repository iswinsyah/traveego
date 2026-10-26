import sys
import os
import argparse
from datetime import datetime

# Pastikan root workspace ada di sys.path
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from ai_crew.config import is_within_working_hours, WORKING_HOUR_START, WORKING_HOUR_END
from ai_crew.llm_client import LocalLLMClient
from ai_crew.intelligence_agent import IntelligenceAgent
from ai_crew.page_crew import PageCrew
from ai_crew.post_crew import PostCrew

def print_banner():
    print("=" * 65)
    print("      TRAVEEGO AI CREW ORCHESTRATOR (PC SERVER LOCAL)       ")
    print(f"      Jam Operasional Server: {WORKING_HOUR_START:02d}:00 s.d. {WORKING_HOUR_END:02d}:00 WIB")
    print("=" * 65)

def main():
    parser = argparse.ArgumentParser(description="Traveego AI Agent Runner")
    parser.add_argument("--check-hours", action="store_true", help="Cek status jam kerja PC Server")
    parser.add_argument("--check-llm", action="store_true", help="Cek koneksi ke Otak AI Lokal (Ollama)")
    parser.add_argument("--run-intelligence", action="store_true", help="Jalankan Agent Pemantau Biaya & Tarif")
    parser.add_argument("--run-pages", action="store_true", help="Jalankan Page Crew (Generate Halaman Rute)")
    parser.add_argument("--run-posts", action="store_true", help="Jalankan Post Crew (Generate Artikel Blog SEO)")
    parser.add_argument("--run-all", action="store_true", help="Jalankan seluruh pipeline AI")
    parser.add_argument("--force", action="store_true", help="Paksa jalan meski di luar jam 06:00 - 23:00 (untuk testing)")

    args = parser.parse_args()
    print_banner()

    # 1. Cek Jam Operasional Server
    is_ok, msg = is_within_working_hours()
    print(f"[STATUS WAKTU] {msg}")

    if args.check_hours and not (args.run_intelligence or args.run_pages or args.run_posts or args.run_all or args.check_llm):
        return

    # 2. Cek Koneksi ke Otak AI Lokal
    llm = LocalLLMClient()
    connected, llm_msg = llm.check_connection()
    print(f"[STATUS OTAK AI] {llm_msg}\n")

    if args.check_llm and not (args.run_intelligence or args.run_pages or args.run_posts or args.run_all):
        return

    # Jika di luar jam kerja dan tanpa flag --force, batalkan proses
    if not is_ok and not args.force:
        print("[AKSI DIBATALKAN] PC Server sedang istirahat. Pekerjaan dijadwalkan kembali saat jam 06:00 WIB.")
        print("Gunakan opsi --force jika Anda ingin menguji coba secara manual saat ini.")
        return

    # Inisialisasi Agent
    intel = IntelligenceAgent()
    page_crew = PageCrew()
    post_crew = PostCrew()

    # A. Jalankan Intelligence Agent
    if args.run_intelligence or args.run_all:
        print("\n--- [1/3] Menjalankan Cost & Tariff Intelligence Agent ---")
        matrix = intel.calculate_prices()
        print(f"[SUKSES] Matrix Biaya diperbarui: {len(matrix['routes'])} rute dihitung All-In.")
        for r_key, r in matrix['routes'].items():
            avanza_p = r['pricing']['avanza']['formatted_price']
            print(f"  * {r['origin']} -> {r['destination']}: Avanza {avanza_p}")

    # B. Jalankan Page Crew
    if args.run_pages or args.run_all:
        print("\n--- [2/3] Menjalankan Page Crew (Landing Page Rute Aktif) ---")
        routes_to_gen = [
            "jakarta_bandara_soetta",
            "jakarta_bandara_halim",
            "jabodetabek_dalam_kota",
            "jakarta_baraya"
        ]
        for r_key in routes_to_gen:
            filepath, page = page_crew.generate_page_for_route(r_key)
            print(f"  [PAGE CREW] Berhasil membuat halaman: {page['meta_title']} -> {filepath}")

    # C. Jalankan Post Crew
    if args.run_posts or args.run_all:
        print("\n--- [3/3] Menjalankan Post Crew (Artikel Blog SEO Rute Aktif) ---")
        topics = [
            ("Panduan Tarif Drop Off Bandara Soekarno-Hatta (CGK) Bebas Antre 24 Jam", "jakarta_bandara_soetta"),
            ("Rincian Biaya Sewa Drop Off Jakarta ke Baraya (Bandung Raya) via Tol MBZ 2026", "jakarta_baraya"),
            ("Layanan Antar Jemput Drop Off Bandara Halim Perdanakusuma Door to Door", "jakarta_bandara_halim"),
            ("Sewa Mobil Drop Off Dalam Kota Jabodetabek Tarif All-In Tanpa DP", "jabodetabek_dalam_kota")
        ]
        for topic, r_key in topics:
            filepath, post = post_crew.generate_post(topic, r_key)
            print(f"  [POST CREW] Berhasil membuat artikel: {post['title']} -> {filepath}")

    print("\n" + "=" * 65)
    print("SEMUA TUGAS SELESAI DIEKSEKUSI DENGAN BAIK!")
    print("=" * 65)

if __name__ == "__main__":
    main()
