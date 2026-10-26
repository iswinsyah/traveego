import os
from datetime import datetime

# ==============================================================
# KONFIGURASI JAM KERJA PC SERVER
# PC Server hanya bekerja aktif dari jam 06:00 s.d. 23:00 WIB
# ==============================================================
WORKING_HOUR_START = 6   # 06:00 WIB
WORKING_HOUR_END = 23    # 23:00 WIB

# ==============================================================
# KONFIGURASI OTAK AI LOKAL (OLLAMA / LOCAL LLM)
# Default menggunakan format OpenAI-compatible di localhost:11434
# ==============================================================
LLM_BASE_URL = os.getenv("LLM_BASE_URL", "http://localhost:11434/v1")
LLM_MODEL = os.getenv("LLM_MODEL", "qwen2.5:7b")  # Pilihan: qwen2.5:7b, qwen2.5:14b, llama3.1:8b
LLM_API_KEY = os.getenv("LLM_API_KEY", "ollama") # Dummy key untuk local provider
LLM_TEMPERATURE = 0.7

# ==============================================================
# FOLDER PENYIMPANAN DATA & OUTPUT
# ==============================================================
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DATA_DIR = os.path.join(BASE_DIR, "data")
OUTPUT_PAGES_DIR = os.path.join(DATA_DIR, "generated_pages")
OUTPUT_POSTS_DIR = os.path.join(DATA_DIR, "generated_posts")
COST_MATRIX_FILE = os.path.join(DATA_DIR, "cost_matrix.json")

def is_within_working_hours():
    """
    Memeriksa apakah saat ini berada dalam rentang jam kerja PC Server (06:00 - 23:00).
    Mengembalikan True jika boleh bekerja, False jika di luar jam kerja.
    """
    now = datetime.now()
    current_hour = now.hour
    
    # 06:00 s.d. 23:00
    if WORKING_HOUR_START <= current_hour < WORKING_HOUR_END:
        return True, f"Saat ini pk {now.strftime('%H:%M:%S')} (Dalam jam operasional server 06:00 - 23:00)."
    else:
        return False, f"Saat ini pk {now.strftime('%H:%M:%S')} (Di luar jam kerja server. Jam kerja aktif: 06:00 - 23:00 WIB)."

def ensure_directories():
    """Memastikan folder data dan output sudah tersedia"""
    os.makedirs(DATA_DIR, exist_ok=True)
    os.makedirs(OUTPUT_PAGES_DIR, exist_ok=True)
    os.makedirs(OUTPUT_POSTS_DIR, exist_ok=True)
