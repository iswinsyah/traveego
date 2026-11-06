<?php
/**
 * DROPOFF.ID - SMART IMAGE ROTATOR & ANTI-SPAM COOLDOWN ENGINE
 * 
 * Aturan Bisnis & SEO:
 * 1. Mencegah duplikasi foto featured image yang sama dalam waktu berdekatan.
 * 2. Menerapkan Cooldown Rotasi minimal 90 Hari (3 Bulan) sebelum foto boleh dipakai ulang.
 * 3. Memprioritaskan foto segar (belum pernah dipakai) agar tampilan natural, manusiawi, & bukan hasil AI artifisial.
 * 4. Pencocokan cerdas berdasarkan kategori topik: Bandara, Jalan Tol, Wisata/Puncak, Armada, dan Kenyamanan Kabin.
 */

class ImageRotator {
    private static $poolFile = __DIR__ . '/../data/image_pool.json';
    private static $postsDir = __DIR__ . '/../data/generated_posts';

    /**
     * Ambil featured image alami yang memenuhi aturan rotasi & cooldown
     */
    public static function getFeaturedImage(string $slug, string $title = '', string $category = '', string $seedKeyword = ''): array {
        $poolData = self::loadPoolData();
        $cooldownDays = intval($poolData['cooldown_days'] ?? 90);
        $images = &$poolData['images'];

        if (empty($images)) {
            // Fallback aman jika pool kosong
            return [
                'url' => 'https://images.unsplash.com/photo-1506015391300-4802dc74de2e?auto=format&fit=crop&w=1200&q=80',
                'alt' => 'Layanan sewa mobil drop off All-In Dropoff.id',
                'category' => 'tol_highway',
                'photo_id' => 'fallback-default'
            ];
        }

        // Tentukan kategori terbaik dari keyword dan konteks
        $targetCategory = self::detectTargetCategory($slug . ' ' . $title . ' ' . $category . ' ' . $seedKeyword);

        $now = time();
        $cooldownSeconds = $cooldownDays * 86400;

        // Pisahkan kandidat per kategori dan status cooldown
        $catFresh = [];      // Belum pernah dipakai (kategori cocok)
        $catCooled = [];     // Sudah lewat cooldown 90 hari (kategori cocok)
        $allFresh = [];      // Belum pernah dipakai (semua kategori)
        $allCooled = [];     // Sudah lewat cooldown 90 hari (semua kategori)
        $allImages = [];     // Semua gambar dengan hitungan istirahat terlama

        foreach ($images as $idx => $img) {
            $isSameCat = ($img['category'] === $targetCategory);
            $usageCount = intval($img['usage_count'] ?? 0);
            $lastUsed = !empty($img['last_used_at']) ? strtotime($img['last_used_at']) : 0;
            $daysSinceUsed = $lastUsed > 0 ? floor(($now - $lastUsed) / 86400) : 99999;
            $isOffCooldown = ($usageCount === 0 || $daysSinceUsed >= $cooldownDays);

            $cand = ['index' => $idx, 'item' => $img, 'days_idle' => $daysSinceUsed, 'usage' => $usageCount];

            if ($usageCount === 0) {
                if ($isSameCat) $catFresh[] = $cand;
                $allFresh[] = $cand;
            } elseif ($isOffCooldown) {
                if ($isSameCat) $catCooled[] = $cand;
                $allCooled[] = $cand;
            }

            $allImages[] = $cand;
        }

        // Tentukan pemenang dengan hirarki ketat:
        // 1. Foto segar di kategori yang cocok
        // 2. Foto segar di kategori lain (agar 100% unik & baru)
        // 3. Foto yang sudah lewat masa jeda 90 hari di kategori cocok (diurutkan dari yang paling lama istirahat)
        // 4. Foto yang sudah lewat masa jeda 90 hari di kategori manapun
        // 5. Jika seluruh 50+ foto sudah terpakai dalam 90 hari, ambil yang paling lama istirahatnya

        $chosenCandidate = null;

        if (!empty($catFresh)) {
            // Acak di antara foto segar kategori cocok
            $chosenCandidate = $catFresh[array_rand($catFresh)];
        } elseif (!empty($allFresh)) {
            // Acak di antara foto segar umum
            $chosenCandidate = $allFresh[array_rand($allFresh)];
        } elseif (!empty($catCooled)) {
            // Urutkan berdasarkan yang paling lama tidak dipakai (days_idle terbesar)
            usort($catCooled, function($a, $b) {
                return $b['days_idle'] - $a['days_idle'];
            });
            $chosenCandidate = $catCooled[0];
        } elseif (!empty($allCooled)) {
            usort($allCooled, function($a, $b) {
                return $b['days_idle'] - $a['days_idle'];
            });
            $chosenCandidate = $allCooled[0];
        } else {
            // Fallback ekstrem jika traffic post melebihi kapasitas bank foto
            usort($allImages, function($a, $b) {
                return $b['days_idle'] - $a['days_idle'];
            });
            $chosenCandidate = $allImages[0];
        }

        $chosenIdx = $chosenCandidate['index'];
        $chosenImg = $images[$chosenIdx];

        // Catat penggunaan
        $images[$chosenIdx]['last_used_at'] = date('Y-m-d H:i:s');
        $images[$chosenIdx]['last_used_slug'] = $slug;
        $images[$chosenIdx]['usage_count'] = intval($chosenImg['usage_count'] ?? 0) + 1;

        // Simpan pool yang telah diperbarui
        self::savePoolData($poolData);

        return [
            'url' => $chosenImg['url'],
            'alt' => !empty($title) ? "{$title} - " . $chosenImg['alt'] : $chosenImg['alt'],
            'category' => $chosenImg['category'],
            'photo_id' => $chosenImg['id']
        ];
    }

    /**
     * Sinkronisasikan foto-foto yang sedang aktif dipakai di file data/generated_posts/*.json
     */
    public static function syncUsageFromExistingPosts(): void {
        $poolData = self::loadPoolData();
        $images = &$poolData['images'];

        if (!is_dir(self::$postsDir)) return;
        $files = glob(self::$postsDir . '/*.json');

        $activeUrls = [];
        foreach ($files as $f) {
            $raw = @file_get_contents($f);
            if (!$raw) continue;
            $post = json_decode($raw, true);
            if (!$post) continue;

            $slug = $post['slug'] ?? basename($f, '.json');
            $img = $post['featured_image'] ?? '';
            $date = $post['published_at'] ?? date('Y-m-d H:i:s');

            if ($img) {
                // Bersihkan URL dari parameter query untuk pencocokan akurat
                $cleanImg = strtok($img, '?');
                $activeUrls[$cleanImg] = [
                    'slug' => $slug,
                    'date' => $date
                ];
            }
        }

        // Perbarui data di pool
        foreach ($images as &$item) {
            $cleanPoolUrl = strtok($item['url'], '?');
            if (isset($activeUrls[$cleanPoolUrl])) {
                $usage = $activeUrls[$cleanPoolUrl];
                $item['last_used_at'] = $usage['date'];
                $item['last_used_slug'] = $usage['slug'];
                if (empty($item['usage_count'])) {
                    $item['usage_count'] = 1;
                }
            }
        }

        self::savePoolData($poolData);
    }

    /**
     * Deteksi kategori visual berdasarkan teks & keyword
     */
    public static function detectTargetCategory(string $text): string {
        $text = strtolower($text);

        if (preg_match('/bandara|airport|soetta|soekarno|halim|cengkareng|terminal|pesawat|boarding/i', $text)) {
            return 'airport_dropoff';
        }
        if (preg_match('/puncak|bogor|wisata|liburan|akhir pekan|keluarga|lembang|pemandangan|kabut|alam|bukit/i', $text)) {
            return 'family_holiday_scenery';
        }
        if (preg_match('/innova|avanza|hiace|armada|unit|mobil|reborn|premio|kapasitas|bagasi/i', $text)) {
            return 'fleet_exterior';
        }
        if (preg_match('/sopir|driver|interior|kabin|kemudi|dashboard|jok|kenyamanan|nyaman|wangi/i', $text)) {
            return 'car_interior_comfort';
        }

        return 'tol_highway';
    }

    /**
     * Dapatkan ringkasan statistik kesehatan bank foto & status cooldown
     */
    public static function getPoolStats(): array {
        $poolData = self::loadPoolData();
        $cooldownDays = intval($poolData['cooldown_days'] ?? 90);
        $images = $poolData['images'] ?? [];

        $now = time();
        $total = count($images);
        $freshCount = 0;
        $inCooldownCount = 0;
        $readyRecycleCount = 0;
        $byCategory = [];

        foreach ($images as $img) {
            $cat = $img['category'] ?? 'other';
            $byCategory[$cat] = ($byCategory[$cat] ?? 0) + 1;

            $usage = intval($img['usage_count'] ?? 0);
            if ($usage === 0) {
                $freshCount++;
            } else {
                $lastUsed = !empty($img['last_used_at']) ? strtotime($img['last_used_at']) : 0;
                $days = $lastUsed > 0 ? floor(($now - $lastUsed) / 86400) : 99999;
                if ($days < $cooldownDays) {
                    $inCooldownCount++;
                } else {
                    $readyRecycleCount++;
                }
            }
        }

        return [
            'total_photos' => $total,
            'cooldown_days' => $cooldownDays,
            'cooldown_months' => round($cooldownDays / 30, 1),
            'fresh_photos' => $freshCount,
            'in_cooldown_photos' => $inCooldownCount,
            'ready_recycled_photos' => $readyRecycleCount,
            'available_to_use' => $freshCount + $readyRecycleCount,
            'categories' => $byCategory
        ];
    }

    private static function loadPoolData(): array {
        if (!file_exists(self::$poolFile)) {
            return ['cooldown_days' => 90, 'images' => []];
        }
        $raw = @file_get_contents(self::$poolFile);
        return json_decode($raw, true) ?: ['cooldown_days' => 90, 'images' => []];
    }

    private static function savePoolData(array $data): bool {
        return @file_put_contents(
            self::$poolFile,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ) !== false;
    }
}
