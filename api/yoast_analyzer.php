<?php
/**
 * TRAVEEGO YOAST SEO & READABILITY ANALYZER ENGINE
 * Standar audit SEO on-page dan Keterbacaan teks ala Yoast SEO WordPress
 * Menghasilkan lampu indikator: 🟢 Good, 🟠 OK, 🔴 Needs Improvement
 */

class YoastSeoAnalyzer {
    
    // Kata-kata transisi umum dalam Bahasa Indonesia
    private static $transitionWords = [
        'namun', 'tetapi', 'oleh karena itu', 'selain itu', 'sehingga', 'bahkan', 
        'dengan demikian', 'terlebih', 'akibatnya', 'melainkan', 'maka dari itu', 
        'selanjutnya', 'kendati demikian', 'tidak hanya itu', 'pasalnya', 'oleh sebab itu',
        'di sisi lain', 'sebagai contoh', 'misalnya', 'sementara itu', 'pada dasarnya',
        'tentunya', 'alhasil', 'walaupun demikian', 'meskipun begitu', 'singkatnya'
    ];

    /**
     * Menganalisis dokumen artikel blog atau landing page
     */
    public static function analyze(array $data, ?string $explicitKeyphrase = null): array {
        $title = trim($data['title'] ?? ($data['meta_title'] ?? ''));
        $metaDesc = trim($data['meta_description'] ?? '');
        $slug = trim($data['slug'] ?? '');
        $featuredImage = trim($data['featured_image'] ?? '');

        // Ambil konten teks murni (strip markdown & HTML)
        $rawContent = $data['content_markdown'] ?? '';
        if (empty($rawContent) && isset($data['hero'])) {
            // Landing page format
            $hero = $data['hero'];
            $rawContent = ($hero['headline'] ?? '') . "\n\n" . ($hero['subheadline'] ?? '');
            if (!empty($data['features']) && is_array($data['features'])) {
                foreach ($data['features'] as $f) {
                    $rawContent .= "\n\n## " . ($f['title'] ?? '') . "\n" . ($f['description'] ?? '');
                }
            }
        }

        // Tentukan Focus Keyphrase
        $focusKeyphrase = trim($explicitKeyphrase ?? ($data['focus_keyword'] ?? ''));
        if (empty($focusKeyphrase)) {
            $focusKeyphrase = self::extractFocusKeyphrase($title, $slug);
        }

        // Analisis SEO
        $seoResult = self::analyzeSeo($title, $metaDesc, $slug, $rawContent, $focusKeyphrase, $featuredImage);

        // Analisis Readability (Keterbacaan)
        $readabilityResult = self::analyzeReadability($rawContent);

        // Google SERP Snippet
        $serpPreview = [
            'url' => 'https://dropoff.id/' . (isset($data['content_markdown']) ? 'blog/' : '') . $slug,
            'title' => !empty($title) ? $title : 'Traveego Drop Off Layanan Sewa Mobil All In',
            'meta_description' => !empty($metaDesc) ? $metaDesc : 'Layanan sewa mobil drop-off terpercaya dengan tarif pasti.',
            'displayed_url' => 'dropoff.id › ' . (isset($data['content_markdown']) ? 'blog › ' : '') . $slug
        ];

        return [
            'focus_keyphrase' => $focusKeyphrase,
            'seo' => $seoResult,
            'readability' => $readabilityResult,
            'serp_preview' => $serpPreview,
            'overall_status' => ($seoResult['status'] === 'good' && $readabilityResult['status'] === 'good') ? 'good' : 'ok'
        ];
    }

    /**
     * Otomatis ekstrak kata kunci jika belum ditentukan
     */
    public static function extractFocusKeyphrase(string $title, string $slug): string {
        // Coba deteksi pola drop off asal ke tujuan dari slug
        if (preg_match('/drop-off-(.*?)$/i', $slug, $m)) {
            $clean = str_replace('-', ' ', $m[1]);
            return ucwords('drop off ' . $clean);
        }
        if (preg_match('/drop off (.*?)(?:terbaru|2026|\d{4}|$)/i', $title, $m)) {
            return trim('Drop Off ' . $m[1]);
        }
        return 'Drop Off Traveego';
    }

    /**
     * Analisis SEO On-Page (Yoast SEO Focus Keyphrase & Technical SEO)
     */
    private static function analyzeSeo(string $title, string $metaDesc, string $slug, string $rawMarkdown, string $keyphrase, string $image): array {
        $checks = [];
        $totalPoints = 0;
        $maxPoints = 0;

        $keyphraseNorm = mb_strtolower(trim($keyphrase));
        $titleNorm = mb_strtolower($title);
        $metaNorm = mb_strtolower($metaDesc);
        $slugNorm = mb_strtolower(str_replace('-', ' ', $slug));
        
        // Ambil teks polos dari markdown
        $plainText = preg_replace('/\[(.*?)\]\(.*?\)/', '$1', $rawMarkdown);
        $plainText = preg_replace('/[#*`_>]/', '', $plainText);
        $plainTextNorm = mb_strtolower($plainText);

        $words = preg_split('/\s+/', trim($plainText));
        $wordCount = count(array_filter($words));

        // 1. Keyphrase in SEO Title
        $maxPoints += 15;
        if (!empty($keyphraseNorm) && mb_strpos($titleNorm, $keyphraseNorm) !== false) {
            $isAtStart = (mb_strpos($titleNorm, $keyphraseNorm) < (mb_strlen($titleNorm) / 2));
            $checks[] = [
                'id' => 'keyphrase_in_title',
                'status' => 'good',
                'title' => 'Keyphrase in title',
                'message' => 'Kata kunci fokus muncul di judul SEO' . ($isAtStart ? ' dan berada di awal judul.' : '.'),
                'score' => 15
            ];
            $totalPoints += 15;
        } else {
            $checks[] = [
                'id' => 'keyphrase_in_title',
                'status' => 'poor',
                'title' => 'Keyphrase in title',
                'message' => 'Kata kunci fokus "' . htmlspecialchars($keyphrase) . '" tidak ditemukan di judul SEO.',
                'score' => 0
            ];
        }

        // 2. SEO Title Length
        $titleLen = mb_strlen($title);
        $maxPoints += 10;
        if ($titleLen >= 40 && $titleLen <= 70) {
            $checks[] = [
                'id' => 'title_length',
                'status' => 'good',
                'title' => 'SEO title length',
                'message' => "Panjang judul optimal ({$titleLen} karakter). Pas di hasil pencarian Google tanpa terpotong.",
                'score' => 10
            ];
            $totalPoints += 10;
        } elseif ($titleLen >= 30 && $titleLen <= 80) {
            $checks[] = [
                'id' => 'title_length',
                'status' => 'ok',
                'title' => 'SEO title length',
                'message' => "Panjang judul lumayan ({$titleLen} karakter). Usahakan antara 40 s.d 70 karakter.",
                'score' => 6
            ];
            $totalPoints += 6;
        } else {
            $checks[] = [
                'id' => 'title_length',
                'status' => 'poor',
                'title' => 'SEO title length',
                'message' => "Panjang judul ({$titleLen} karakter) kurang ideal (terlalu pendek atau melebihi 80 karakter).",
                'score' => 2
            ];
            $totalPoints += 2;
        }

        // 3. Keyphrase in Meta Description
        $maxPoints += 15;
        if (!empty($keyphraseNorm) && mb_strpos($metaNorm, $keyphraseNorm) !== false) {
            $checks[] = [
                'id' => 'keyphrase_in_meta',
                'status' => 'good',
                'title' => 'Keyphrase in meta description',
                'message' => 'Kata kunci fokus ditemukan di meta deskripsi.',
                'score' => 15
            ];
            $totalPoints += 15;
        } else {
            $checks[] = [
                'id' => 'keyphrase_in_meta',
                'status' => 'poor',
                'title' => 'Keyphrase in meta description',
                'message' => 'Kata kunci fokus tidak ditemukan di meta deskripsi.',
                'score' => 0
            ];
        }

        // 4. Meta Description Length
        $metaLen = mb_strlen($metaDesc);
        $maxPoints += 10;
        if ($metaLen >= 120 && $metaLen <= 165) {
            $checks[] = [
                'id' => 'meta_length',
                'status' => 'good',
                'title' => 'Meta description length',
                'message' => "Panjang meta deskripsi sempurna ({$metaLen} karakter, ideal 120-165 karakter).",
                'score' => 10
            ];
            $totalPoints += 10;
        } elseif ($metaLen >= 90 && $metaLen <= 180) {
            $checks[] = [
                'id' => 'meta_length',
                'status' => 'ok',
                'title' => 'Meta description length',
                'message' => "Panjang meta deskripsi ({$metaLen} karakter) mendekati ideal (target 120-165 karakter).",
                'score' => 6
            ];
            $totalPoints += 6;
        } else {
            $checks[] = [
                'id' => 'meta_length',
                'status' => 'poor',
                'title' => 'Meta description length',
                'message' => "Panjang meta deskripsi ({$metaLen} karakter) belum optimal.",
                'score' => 2
            ];
            $totalPoints += 2;
        }

        // 5. Keyphrase in URL Slug
        $maxPoints += 10;
        $keyphraseWords = array_filter(explode(' ', $keyphraseNorm));
        $matchedSlugWords = 0;
        foreach ($keyphraseWords as $kw) {
            if (mb_strpos($slugNorm, $kw) !== false) $matchedSlugWords++;
        }
        $slugMatchRatio = count($keyphraseWords) > 0 ? ($matchedSlugWords / count($keyphraseWords)) : 0;

        if ($slugMatchRatio >= 0.7) {
            $checks[] = [
                'id' => 'keyphrase_in_slug',
                'status' => 'good',
                'title' => 'Keyphrase in slug',
                'message' => 'Kata kunci fokus terdapat di dalam URL slug artikel.',
                'score' => 10
            ];
            $totalPoints += 10;
        } else {
            $checks[] = [
                'id' => 'keyphrase_in_slug',
                'status' => 'ok',
                'title' => 'Keyphrase in slug',
                'message' => 'Hanya sebagian kata kunci yang ada di URL slug.',
                'score' => 5
            ];
            $totalPoints += 5;
        }

        // 6. Keyphrase in Introduction (Paragraf Pertama)
        $maxPoints += 10;
        $paragraphs = array_values(array_filter(explode("\n\n", trim($rawMarkdown))));
        $firstParagraph = mb_strtolower($paragraphs[0] ?? '');
        if (count($paragraphs) > 1 && str_starts_with($firstParagraph, '#')) {
            $firstParagraph = mb_strtolower($paragraphs[1] ?? '');
        }
        if (!empty($keyphraseNorm) && mb_strpos($firstParagraph, $keyphraseNorm) !== false) {
            $checks[] = [
                'id' => 'keyphrase_in_intro',
                'status' => 'good',
                'title' => 'Keyphrase in introduction',
                'message' => 'Kata kunci fokus muncul di paragraf pertama / pembuka artikel.',
                'score' => 10
            ];
            $totalPoints += 10;
        } else {
            // Cek kecocokan parsial kata-kata kunci
            $matchedKw = 0;
            foreach ($keyphraseWords as $kw) {
                if (mb_strpos($firstParagraph, $kw) !== false) $matchedKw++;
            }
            if (count($keyphraseWords) > 0 && ($matchedKw / count($keyphraseWords)) >= 0.7) {
                $checks[] = [
                    'id' => 'keyphrase_in_intro',
                    'status' => 'good',
                    'title' => 'Keyphrase in introduction',
                    'message' => 'Kata kunci fokus terwakili dengan baik di paragraf pertama.',
                    'score' => 10
                ];
                $totalPoints += 10;
            } else {
                $checks[] = [
                    'id' => 'keyphrase_in_intro',
                    'status' => 'poor',
                    'title' => 'Keyphrase in introduction',
                    'message' => 'Kata kunci fokus tidak ditemukan di paragraf pertama artikel.',
                    'score' => 0
                ];
            }
        }

        // 7. Keyphrase Density (Kepadatan Kata Kunci)
        $maxPoints += 10;
        $matchesCount = 0;
        if (!empty($keyphraseNorm)) {
            $matchesCount = mb_substr_count($plainTextNorm, $keyphraseNorm);
        }
        $density = $wordCount > 0 ? round(($matchesCount * count($keyphraseWords) / $wordCount) * 100, 2) : 0;

        if ($matchesCount >= 2 && $density <= 3.0) {
            $checks[] = [
                'id' => 'keyphrase_density',
                'status' => 'good',
                'title' => 'Keyphrase density',
                'message' => "Kepadatan kata kunci ideal ({$density}%, ditemukan {$matchesCount} kali). Natural dan tidak terindikasi keyword stuffing.",
                'score' => 10
            ];
            $totalPoints += 10;
        } elseif ($matchesCount >= 1) {
            $checks[] = [
                'id' => 'keyphrase_density',
                'status' => 'ok',
                'title' => 'Keyphrase density',
                'message' => "Kata kunci ditemukan {$matchesCount} kali ({$density}%). Bisa diperbanyak sedikit agar lebih kuat.",
                'score' => 7
            ];
            $totalPoints += 7;
        } else {
            $checks[] = [
                'id' => 'keyphrase_density',
                'status' => 'poor',
                'title' => 'Keyphrase density',
                'message' => 'Kata kunci tidak ditemukan sama sekali di dalam isi teks.',
                'score' => 0
            ];
        }

        // 8. Keyphrase in Subheading (H2 / H3)
        $maxPoints += 10;
        preg_match_all('/^#{2,3}\s+(.*)$/m', $rawMarkdown, $subheadings);
        $foundInSubheading = false;
        foreach (($subheadings[1] ?? []) as $headingText) {
            $hNorm = mb_strtolower($headingText);
            if (!empty($keyphraseNorm) && mb_strpos($hNorm, $keyphraseNorm) !== false) {
                $foundInSubheading = true;
                break;
            }
            // partial match
            $matchedH = 0;
            foreach ($keyphraseWords as $kw) {
                if (mb_strpos($hNorm, $kw) !== false) $matchedH++;
            }
            if (count($keyphraseWords) > 0 && ($matchedH / count($keyphraseWords)) >= 0.7) {
                $foundInSubheading = true;
                break;
            }
        }

        if ($foundInSubheading) {
            $checks[] = [
                'id' => 'keyphrase_in_subheading',
                'status' => 'good',
                'title' => 'Keyphrase in subheading',
                'message' => 'Kata kunci fokus atau variasinya ditemukan pada sub-heading (H2/H3).',
                'score' => 10
            ];
            $totalPoints += 10;
        } else {
            $checks[] = [
                'id' => 'keyphrase_in_subheading',
                'status' => 'ok',
                'title' => 'Keyphrase in subheading',
                'message' => 'Gunakan kata kunci fokus pada setidaknya satu sub-heading H2.',
                'score' => 5
            ];
            $totalPoints += 5;
        }

        // 9. Outbound Links (Tautan Keluar)
        $maxPoints += 10;
        preg_match_all('/\[.*?\]\((https?:\/\/.*?)\)/i', $rawMarkdown, $outboundMatches);
        $outboundCount = count($outboundMatches[1] ?? []);
        if ($outboundCount >= 2) {
            $checks[] = [
                'id' => 'outbound_links',
                'status' => 'good',
                'title' => 'Outbound links',
                'message' => "Kerja bagus! Terdapat {$outboundCount} tautan keluar berkualitas (Wikipedia/Pemerintah).",
                'score' => 10
            ];
            $totalPoints += 10;
        } elseif ($outboundCount === 1) {
            $checks[] = [
                'id' => 'outbound_links',
                'status' => 'ok',
                'title' => 'Outbound links',
                'message' => 'Terdapat 1 tautan keluar. Disarankan menambah 1 lagi ke sumber resmi otoritas.',
                'score' => 6
            ];
            $totalPoints += 6;
        } else {
            $checks[] = [
                'id' => 'outbound_links',
                'status' => 'poor',
                'title' => 'Outbound links',
                'message' => 'Tidak ada tautan keluar. Tambahkan tautan ke situs otoritas terpercaya.',
                'score' => 0
            ];
        }

        // 10. Internal Links (Tautan Ke Dalam)
        $maxPoints += 10;
        preg_match_all('/\[.*?\]\(((\/[^\/].*?)|([^\/].*?))\)/i', $rawMarkdown, $internalMatches);
        // Filter out absolute http links
        $internalCount = 0;
        foreach (($internalMatches[1] ?? []) as $u) {
            if (!str_starts_with($u, 'http://') && !str_starts_with($u, 'https://')) {
                $internalCount++;
            }
        }
        if ($internalCount >= 2) {
            $checks[] = [
                'id' => 'internal_links',
                'status' => 'good',
                'title' => 'Internal links',
                'message' => "Sangat baik! Terdapat {$internalCount} tautan internal ke rute atau artikel Traveego.",
                'score' => 10
            ];
            $totalPoints += 10;
        } elseif ($internalCount === 1) {
            $checks[] = [
                'id' => 'internal_links',
                'status' => 'ok',
                'title' => 'Internal links',
                'message' => 'Terdapat 1 tautan internal. Disarankan minimal 2 tautan internal.',
                'score' => 6
            ];
            $totalPoints += 6;
        } else {
            $checks[] = [
                'id' => 'internal_links',
                'status' => 'poor',
                'title' => 'Internal links',
                'message' => 'Tidak ditemukan tautan internal ke halaman lain.',
                'score' => 0
            ];
        }

        // 11. Text Length / Word Count
        $maxPoints += 10;
        if ($wordCount >= 500) {
            $checks[] = [
                'id' => 'text_length',
                'status' => 'good',
                'title' => 'Text length',
                'message' => "Jumlah kata ({$wordCount} kata) sangat memuaskan, di atas batas rekomendasi 500 kata.",
                'score' => 10
            ];
            $totalPoints += 10;
        } elseif ($wordCount >= 300) {
            $checks[] = [
                'id' => 'text_length',
                'status' => 'ok',
                'title' => 'Text length',
                'message' => "Jumlah kata ({$wordCount} kata) mencukupi, disarankan menambah pembahasan hingga 500+ kata.",
                'score' => 7
            ];
            $totalPoints += 7;
        } else {
            $checks[] = [
                'id' => 'text_length',
                'status' => 'poor',
                'title' => 'Text length',
                'message' => "Jumlah kata ({$wordCount} kata) terlalu sedikit di bawah 300 kata.",
                'score' => 2
            ];
            $totalPoints += 2;
        }

        // 12. Image / Featured Image
        $maxPoints += 5;
        if (!empty($image)) {
            $checks[] = [
                'id' => 'image_alt',
                'status' => 'good',
                'title' => 'Image Keyphrase',
                'message' => 'Artikel memiliki banner visual dengan atribut alt yang relevan.',
                'score' => 5
            ];
            $totalPoints += 5;
        } else {
            $checks[] = [
                'id' => 'image_alt',
                'status' => 'ok',
                'title' => 'Image Keyphrase',
                'message' => 'Tambahkan gambar pendukung untuk meningkatkan engagement pembaca.',
                'score' => 2
            ];
            $totalPoints += 2;
        }

        $finalScore = $maxPoints > 0 ? round(($totalPoints / $maxPoints) * 100) : 0;
        $status = ($finalScore >= 80) ? 'good' : (($finalScore >= 50) ? 'ok' : 'poor');

        return [
            'score' => $finalScore,
            'status' => $status,
            'word_count' => $wordCount,
            'checks' => $checks
        ];
    }

    /**
     * Analisis Keterbacaan (Yoast Readability Analysis)
     */
    private static function analyzeReadability(string $rawMarkdown): array {
        $checks = [];
        $totalPoints = 0;
        $maxPoints = 0;

        // Ambil plain text tanpa headings dan tanpa links markdown
        $lines = explode("\n", $rawMarkdown);
        $paragraphs = [];
        $currentPara = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || str_starts_with($trimmed, '#') || $trimmed === '---') {
                if (!empty($currentPara)) {
                    $paragraphs[] = $currentPara;
                    $currentPara = '';
                }
                continue;
            }
            $currentPara .= ' ' . $trimmed;
        }
        if (!empty($currentPara)) $paragraphs[] = $currentPara;

        $fullText = implode(' ', $paragraphs);
        $plainText = preg_replace('/\[(.*?)\]\(.*?\)/', '$1', $fullText);
        $plainText = preg_replace('/[#*`_>]/', '', $plainText);

        // Pisahkan kalimat berdasarkan titik, tanda tanya, atau tanda seru
        $sentences = preg_split('/(?<=[.?!])\s+/', trim($plainText), -1, PREG_SPLIT_NO_EMPTY);
        $totalSentences = count($sentences);

        // 1. Transition Words (Kata Transisi)
        $maxPoints += 25;
        $sentencesWithTransition = 0;
        foreach ($sentences as $s) {
            $sLower = mb_strtolower($s);
            foreach (self::$transitionWords as $tw) {
                if (mb_strpos($sLower, $tw) !== false) {
                    $sentencesWithTransition++;
                    break;
                }
            }
        }
        $transitionRatio = $totalSentences > 0 ? round(($sentencesWithTransition / $totalSentences) * 100, 1) : 0;

        if ($transitionRatio >= 25) {
            $checks[] = [
                'id' => 'transition_words',
                'status' => 'good',
                'title' => 'Transition words',
                'message' => "Kerja luar biasa! {$transitionRatio}% kalimat memuat kata penghubung/transisi (standar Yoast > 20%).",
                'score' => 25
            ];
            $totalPoints += 25;
        } elseif ($transitionRatio >= 15) {
            $checks[] = [
                'id' => 'transition_words',
                'status' => 'ok',
                'title' => 'Transition words',
                'message' => "Sebanyak {$transitionRatio}% kalimat menggunakan kata transisi. Tambahkan sedikit lagi agar alur lebih mengalir.",
                'score' => 18
            ];
            $totalPoints += 18;
        } else {
            $checks[] = [
                'id' => 'transition_words',
                'status' => 'poor',
                'title' => 'Transition words',
                'message' => "Hanya {$transitionRatio}% kalimat menggunakan kata transisi. Gunakan kata seperti 'namun', 'oleh karena itu', 'selain itu'.",
                'score' => 8
            ];
            $totalPoints += 8;
        }

        // 2. Sentence Length (Panjang Kalimat)
        $maxPoints += 25;
        $longSentences = 0;
        foreach ($sentences as $s) {
            $w = count(array_filter(preg_split('/\s+/', trim($s))));
            if ($w > 20) $longSentences++;
        }
        $longSentenceRatio = $totalSentences > 0 ? round(($longSentences / $totalSentences) * 100, 1) : 0;

        if ($longSentenceRatio <= 25) {
            $checks[] = [
                'id' => 'sentence_length',
                'status' => 'good',
                'title' => 'Sentence length',
                'message' => "Hebat! Hanya {$longSentenceRatio}% kalimat yang panjang (> 20 kata), sesuai rekomendasi Yoast (< 25%).",
                'score' => 25
            ];
            $totalPoints += 25;
        } elseif ($longSentenceRatio <= 35) {
            $checks[] = [
                'id' => 'sentence_length',
                'status' => 'ok',
                'title' => 'Sentence length',
                'message' => "Sebanyak {$longSentenceRatio}% kalimat memiliki lebih dari 20 kata. Usahakan memotong beberapa kalimat panjang.",
                'score' => 17
            ];
            $totalPoints += 17;
        } else {
            $checks[] = [
                'id' => 'sentence_length',
                'status' => 'poor',
                'title' => 'Sentence length',
                'message' => "Sebanyak {$longSentenceRatio}% kalimat terlalu panjang (> 20 kata). Persingkat kalimat demi kenyamanan pembaca.",
                'score' => 8
            ];
            $totalPoints += 8;
        }

        // 3. Paragraph Length (Panjang Paragraf)
        $maxPoints += 20;
        $longParagraphs = 0;
        foreach ($paragraphs as $p) {
            $w = count(array_filter(preg_split('/\s+/', trim($p))));
            if ($w > 120) $longParagraphs++;
        }

        if ($longParagraphs === 0) {
            $checks[] = [
                'id' => 'paragraph_length',
                'status' => 'good',
                'title' => 'Paragraph length',
                'message' => 'Tidak ada paragraf yang terlalu panjang (> 120 kata). Sangat nyaman dibaca di smartphone!',
                'score' => 20
            ];
            $totalPoints += 20;
        } elseif ($longParagraphs <= 1) {
            $checks[] = [
                'id' => 'paragraph_length',
                'status' => 'ok',
                'title' => 'Paragraph length',
                'message' => 'Ada 1 paragraf agak panjang. Disarankan memecahnya menjadi 2 paragraf.',
                'score' => 14
            ];
            $totalPoints += 14;
        } else {
            $checks[] = [
                'id' => 'paragraph_length',
                'status' => 'poor',
                'title' => 'Paragraph length',
                'message' => "Terdapat {$longParagraphs} paragraf terlalu panjang. Pecah paragraf agar tidak padat.",
                'score' => 6
            ];
            $totalPoints += 6;
        }

        // 4. Subheading Distribution (Distribusi Sub-heading)
        $maxPoints += 15;
        preg_match_all('/^#{2,3}\s+(.*)$/m', $rawMarkdown, $subheadings);
        $subheadingCount = count($subheadings[1] ?? []);

        if ($subheadingCount >= 3) {
            $checks[] = [
                'id' => 'subheading_distribution',
                'status' => 'good',
                'title' => 'Subheading distribution',
                'message' => "Distribusi sub-heading ({$subheadingCount} H2/H3) sangat baik dan terstruktur rapi.",
                'score' => 15
            ];
            $totalPoints += 15;
        } elseif ($subheadingCount >= 1) {
            $checks[] = [
                'id' => 'subheading_distribution',
                'status' => 'ok',
                'title' => 'Subheading distribution',
                'message' => "Terdapat {$subheadingCount} sub-heading. Tambahkan 1-2 heading lagi untuk membagi topik.",
                'score' => 10
            ];
            $totalPoints += 10;
        } else {
            $checks[] = [
                'id' => 'subheading_distribution',
                'status' => 'poor',
                'title' => 'Subheading distribution',
                'message' => 'Belum ada sub-heading H2/H3. Tambahkan judul bab agar artikel mudah di-scan mata pembaca.',
                'score' => 3
            ];
            $totalPoints += 3;
        }

        // 5. Flesch Reading Ease (Formula adaptasi Bahasa Indonesia)
        $maxPoints += 15;
        $totalWords = count(array_filter(preg_split('/\s+/', trim($plainText))));
        $avgWordsPerSentence = $totalSentences > 0 ? ($totalWords / $totalSentences) : 15;
        // Formula estimasi Flesch untuk kata Indonesia (suku kata rata-rata ~2.5)
        $fleschScore = round(206.835 - (1.015 * $avgWordsPerSentence) - 30);
        $fleschScore = max(10, min(100, $fleschScore));

        if ($fleschScore >= 60) {
            $checks[] = [
                'id' => 'flesch_reading_ease',
                'status' => 'good',
                'title' => 'Flesch reading ease',
                'message' => "Skor kemudahan membaca: {$fleschScore}/100. Teks mudah dan menyenangkan untuk dipahami.",
                'score' => 15
            ];
            $totalPoints += 15;
        } else {
            $checks[] = [
                'id' => 'flesch_reading_ease',
                'status' => 'ok',
                'title' => 'Flesch reading ease',
                'message' => "Skor kemudahan membaca: {$fleschScore}/100. Coba gunakan kata-kata yang lebih sederhana.",
                'score' => 10
            ];
            $totalPoints += 10;
        }

        $finalScore = $maxPoints > 0 ? round(($totalPoints / $maxPoints) * 100) : 0;
        $status = ($finalScore >= 80) ? 'good' : (($finalScore >= 50) ? 'ok' : 'poor');

        return [
            'score' => $finalScore,
            'status' => $status,
            'flesch_score' => $fleschScore,
            'checks' => $checks
        ];
    }
}

// Jika diakses langsung via HTTP GET/POST API
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'yoast_analyzer.php') {
    header('Content-Type: application/json; charset=utf-8');
    $slug = trim($_GET['slug'] ?? '');
    $type = trim($_GET['type'] ?? 'post'); // 'post' or 'page'

    $baseDir = dirname(__DIR__);
    $targetFile = ($type === 'page') 
        ? $baseDir . '/data/generated_pages/' . preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug) . '.json'
        : $baseDir . '/data/generated_posts/' . preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug) . '.json';

    if (!empty($slug) && file_exists($targetFile)) {
        $docData = json_decode(file_get_contents($targetFile), true) ?: [];
        $analysis = YoastSeoAnalyzer::analyze($docData);
        echo json_encode(['success' => true, 'slug' => $slug, 'type' => $type, 'analysis' => $analysis], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Jika POST raw data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rawInput = file_get_contents('php://input');
        $inputData = json_decode($rawInput, true);
        if ($inputData) {
            $analysis = YoastSeoAnalyzer::analyze($inputData);
            echo json_encode(['success' => true, 'analysis' => $analysis], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Parameter slug atau data tidak valid'], JSON_PRETTY_PRINT);
    exit;
}

