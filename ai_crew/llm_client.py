import json
import socket
import urllib.parse
from openai import OpenAI
from ai_crew.config import LLM_BASE_URL, LLM_MODEL, LLM_API_KEY, LLM_TEMPERATURE

class LocalLLMClient:
    """
    Klien untuk menghubungkan AI Agent ke Otak AI Lokal (Ollama / Local LLM)
    menggunakan interface OpenAI-compatible.
    """
    _cached_status = None

    def __init__(self, base_url=LLM_BASE_URL, model=LLM_MODEL, api_key=LLM_API_KEY):
        self.base_url = base_url
        self.model = model
        self.api_key = api_key
        self.client = OpenAI(base_url=self.base_url, api_key=self.api_key)

    def check_connection(self, force_refresh=False):
        """
        Memeriksa apakah server Otak AI Lokal (Ollama) sedang berjalan dengan ultra-fast socket probe.
        """
        if LocalLLMClient._cached_status is not None and not force_refresh:
            return LocalLLMClient._cached_status

        try:
            parsed = urllib.parse.urlparse(self.base_url)
            host = parsed.hostname or "127.0.0.1"
            port = parsed.port or 11434
            
            # Cek port terbuka dengan timeout kilat 0.2 detik
            with socket.create_connection((host, port), timeout=0.2):
                pass
            
            res = (True, f"Otak AI Lokal TERHUBUNG di {self.base_url} (Model target: {self.model})")
            LocalLLMClient._cached_status = res
            return res
        except Exception:
            res = (
                False,
                f"Otak AI Lokal BELUM TERDETEKSI di {self.base_url}.\n"
                f"Petunjuk: Jalankan Ollama di PC Anda saat Anda siap mengaktifkan otaknya.\n"
                f"Contoh perintah: ollama run {self.model}"
            )
            LocalLLMClient._cached_status = res
            return res

    def generate(self, system_prompt: str, user_prompt: str, json_mode: bool = False) -> str:
        """
        Mengirim prompt ke Otak AI Lokal dan mengembalikan respon teks atau JSON.
        """
        is_connected, msg = self.check_connection()
        if not is_connected:
            raise ConnectionError(msg)

        messages = [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt}
        ]

        kwargs = {
            "model": self.model,
            "messages": messages,
            "temperature": LLM_TEMPERATURE,
        }

        if json_mode:
            kwargs["response_format"] = {"type": "json_object"}

        response = self.client.chat.completions.create(**kwargs)
        return response.choices[0].message.content.strip()
