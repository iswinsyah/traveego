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
        self.client = OpenAI(base_url=self.base_url, api_key=self.api_key, timeout=90.0)

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
            
            # Cek port terbuka dengan timeout kilat 0.3 detik
            with socket.create_connection((host, port), timeout=0.3):
                pass
            
            res = (True, f"Otak AI Server TERHUBUNG di {self.base_url} (Model target: {self.model})")
            LocalLLMClient._cached_status = res
            return res
        except Exception:
            res = (
                False,
                f"Otak AI Server BELUM TERDETEKSI di {self.base_url}.\n"
                f"Petunjuk: Pastikan Tailscale aktif dan Ollama berjalan di PC Server Anda."
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

        print(f"  [LLM INFERENCE] Menghubungi model {self.model} di PC Server Tailscale...", flush=True)

        if json_mode:
            system_prompt += "\nOutput WAJIB berupa JSON murni yang valid tanpa tambahan markdown atau teks penjelasan."

        messages = [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt}
        ]

        try:
            import httpx
            endpoint = f"{self.base_url.rstrip('/')}/chat/completions"
            payload = {
                "model": self.model,
                "messages": messages,
                "temperature": LLM_TEMPERATURE,
                "max_tokens": 500,
                "stream": False
            }
            res = httpx.post(endpoint, json=payload, timeout=15.0)
            if res.status_code == 200:
                data = res.json()
                raw_text = data["choices"][0]["message"]["content"].strip()
            else:
                raise Exception(f"HTTP {res.status_code}")
        except Exception as err:
            raise Exception(f"LLM Timeout / Error ({err})")

        if json_mode:
            # Bersihkan jika ada pembungkus markdown ```json ... ```
            if "```" in raw_text:
                import re
                match = re.search(r'```(?:json)?\s*(\{.*?\})\s*```', raw_text, re.DOTALL)
                if match:
                    return match.group(1).strip()
            # Cari kurung kurawal pertama dan terakhir
            start = raw_text.find("{")
            end = raw_text.rfind("}")
            if start != -1 and end != -1:
                return raw_text[start:end+1]

        return raw_text
