import json
import os
import sys
from datetime import datetime

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from ai_crew.config import COST_MATRIX_FILE, ensure_directories
from ai_crew.llm_client import LocalLLMClient

# ==============================================================
# DATA STANDAR DASAR BIAYA OPERASIONAL TRAVEEGO (BASE BENCHMARK)
# ==============================================================
DEFAULT_COST_DATA = {
    "last_updated": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
    "updated_by": "Cost & Tariff Intelligence Agent",
    "currency": "IDR",
    "fuel_prices_per_liter": {
        "pertalite": 10000,
        "pertamax": 12100,
        "dexlite": 13700
    },
    "vehicle_specs": {
        "avanza": {
            "name": "Toyota All New Avanza",
            "capacity": "4-5 Penumpang",
            "km_per_liter": 12.0,
            "fuel_type": "pertalite",
            "driver_fee_multiplier": 1.0,
            "margin_percent": 0.25
        },
        "innova": {
            "name": "Toyota Innova Reborn",
            "capacity": "6-7 Penumpang",
            "km_per_liter": 10.0,
            "fuel_type": "dexlite",
            "driver_fee_multiplier": 1.25,
            "margin_percent": 0.28
        },
        "hiace": {
            "name": "Toyota Hiace Commuter/Premio",
            "capacity": "10-14 Penumpang",
            "km_per_liter": 8.0,
            "fuel_type": "dexlite",
            "driver_fee_multiplier": 1.5,
            "margin_percent": 0.30
        }
    },
    "routes": {
        "jakarta_bandara_soetta": {
            "slug": "drop-off-bandara-soekarno-hatta",
            "origin": "Jabodetabek (Jakarta, Bogor, Depok, Tangerang, Bekasi)",
            "destination": "Bandara Internasional Soekarno-Hatta (CGK)",
            "distance_km": 45,
            "toll_cost": 45000,
            "toll_details": "Tol Dalam Kota / JORR + Tol Bandara Prof. Sedyatmo",
            "base_driver_fee": 125000
        },
        "jakarta_bandara_halim": {
            "slug": "drop-off-bandara-halim",
            "origin": "Jabodetabek (Jakarta, Bogor, Depok, Tangerang, Bekasi)",
            "destination": "Bandara Halim Perdanakusuma (HLP)",
            "distance_km": 35,
            "toll_cost": 25000,
            "toll_details": "Tol Dalam Kota / Tol Jagorawi",
            "base_driver_fee": 110000
        },
        "jabodetabek_dalam_kota": {
            "slug": "drop-off-dalam-kota-jabodetabek",
            "origin": "Kawasan Jabodetabek (Titik Jemput)",
            "destination": "Kawasan Jabodetabek (Tujuan Point-to-Point)",
            "distance_km": 40,
            "toll_cost": 30000,
            "toll_details": "Tol Dalam Kota / JORR / Tol Jagorawi",
            "base_driver_fee": 120000
        },
        "jakarta_baraya": {
            "slug": "drop-off-jakarta-bandung-baraya",
            "origin": "Jabodetabek (Jakarta, Bogor, Depok, Tangerang, Bekasi)",
            "destination": "Baraya (Bandung Raya: Kota Bandung, Cimahi, Lembang)",
            "distance_km": 150,
            "toll_cost": 85000,
            "toll_details": "Tol Layang MBZ + Tol Cipularang + Pasteur / Buah Batu",
            "base_driver_fee": 200000
        }
    }
}

class IntelligenceAgent:
    """
    Agent Pemantau Biaya & Tarif: Menghitung struktur harga All-In transparan
    yang menjadi acuan bagi Page Crew, Post Crew, dan CS AI Tiara.
    """
    def __init__(self):
        self.llm = LocalLLMClient()
        ensure_directories()

    def calculate_prices(self, cost_data=None):
        """
        Menghitung tarif All-In untuk semua mobil di setiap rute:
        Rumus: Biaya BBM + Tol + Jasa Sopir + Margin Traveego
        """
        data = cost_data or DEFAULT_COST_DATA.copy()
        fuel_prices = data["fuel_prices_per_liter"]
        vehicles = data["vehicle_specs"]
        routes = data["routes"]

        calculated_routes = {}

        for route_key, route in routes.items():
            dist = route["distance_km"]
            toll = route["toll_cost"]
            base_driver = route["base_driver_fee"]

            pricing_per_vehicle = {}

            for v_key, v in vehicles.items():
                fuel_price = fuel_prices[v["fuel_type"]]
                liters_needed = dist / v["km_per_liter"]
                fuel_cost = round(liters_needed * fuel_price)
                driver_fee = round(base_driver * v["driver_fee_multiplier"])
                
                subtotal = fuel_cost + toll + driver_fee
                margin = round(subtotal * v["margin_percent"])
                raw_total = subtotal + margin
                
                # Bulatkan ke kelipatan 25.000 terdekat agar nyaman bagi pelanggan
                rounded_total = int(round(raw_total / 25000.0) * 25000)

                pricing_per_vehicle[v_key] = {
                    "vehicle_name": v["name"],
                    "fuel_cost": fuel_cost,
                    "toll_cost": toll,
                    "driver_fee": driver_fee,
                    "margin": margin,
                    "all_in_total": rounded_total,
                    "formatted_price": f"Rp {rounded_total:,}".replace(",", ".")
                }

            calculated_routes[route_key] = {
                **route,
                "pricing": pricing_per_vehicle
            }

        result = {
            "last_updated": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "fuel_prices_per_liter": fuel_prices,
            "routes": calculated_routes
        }

        # Simpan ke data/cost_matrix.json
        with open(COST_MATRIX_FILE, "w", encoding="utf-8") as f:
            json.dump(result, f, indent=2, ensure_ascii=False)

        return result

    def get_cost_matrix(self):
        """Membaca cost matrix yang tersimpan, jika belum ada maka buat baru."""
        if os.path.exists(COST_MATRIX_FILE):
            with open(COST_MATRIX_FILE, "r", encoding="utf-8") as f:
                return json.load(f)
        return self.calculate_prices()
