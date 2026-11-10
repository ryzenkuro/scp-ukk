<?php

namespace App\Services;

use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\User;
use Smalot\PdfParser\Parser as SmalotParser;

class SchedulePdfParserService
{
    /**
     * Kamus Dinamis FET Code -> User Guru (dipelajari dinamis dari pasangan kode & nama resmi di dokumen)
     */
    protected array $dynamicFetMap = [];

    /**
     * Parse file PDF jadwal sekolah (Putaran 1 / Putaran 2)
     * Mengembalikan array entri jadwal siap simpan
     */
    public function parse(string $filePath, ?int $cycleOverride = null): array
    {
        @ini_set('memory_limit', '512M');
        $this->dynamicFetMap = [];
        $fileName = basename($filePath);

        // 1. Tentukan Siklus Putaran
        if ($cycleOverride !== null && in_array($cycleOverride, [1, 2], true)) {
            $cycle = $cycleOverride;
        } else {
            if (preg_match('/(put[_\-\s]*2|putaran[_\-\s]*2)/i', $fileName)) {
                $cycle = 2;
            } elseif (preg_match('/(put[_\-\s]*1|putaran[_\-\s]*1)/i', $fileName)) {
                $cycle = 1;
            } else {
                $cycle = 1;
            }
        }

        // 2. Pre-fetch Data Rombel Kelas dari Database
        $classes = SchoolClass::all();
        $classMap = [];
        foreach ($classes as $c) {
            $norm = preg_replace('/[^a-z0-9]/', '', strtolower($c->name));
            $classMap[$norm] = $c;
        }

        // 3. Pre-fetch Data Guru (dari DB dan fallback kamus master_guru.json)
        $teachers = User::whereIn('role_id', [1, 2, 5])->get();
        $teacherCodeMap = [];
        $teacherNameMap = [];
        $teacherKeywords = [];
        $teacherCompactMap = [];

        // Load 91 official teachers dari master_guru.json jika ada
        $jsonPath = database_path('data/master_guru.json');
        if (file_exists($jsonPath)) {
            $masterGuru = json_decode(file_get_contents($jsonPath), true);
            foreach ($masterGuru as $g) {
                $cCode = (int) $g['kode'];
                $dbUser = $teachers->first(fn ($u) => $u->nip === $g['nip'] || $u->username === $g['username'] || $u->fullname === $g['name']);

                $tObj = (object) [
                    'id' => $dbUser ? $dbUser->id : null,
                    'code' => $cCode,
                    'username' => $g['username'],
                    'fullname' => $g['name'],
                    'nip' => $g['nip'],
                ];
                $teacherCodeMap[$cCode] = $tObj;
                $cleanN = strtoupper(preg_replace('/[^A-Z]/', '', $g['name']));
                $teacherNameMap[$cleanN] = $tObj;

                $noTitle = strtoupper(preg_replace('/[^A-Z]/', '', preg_replace('/\b(S\.?Pd(?:\s*I)?|S\.?Pd\.?I|M\.?Pd|S\.?Kom|S\.?T|S\.?E|M\.?Acc|M\.?Eng|M\.?M|S\.?IP|S\.?Pd\.?Si|Drs|Dra|S\.?FK|SS)\b/i', '', $g['name'])));
                $teacherNameMap[$noTitle] = $tObj;

                $rawNoTitle = trim(preg_replace('/\b(S\.?Pd(?:\s*I)?|S\.?Pd\.?I|M\.?Pd|S\.?Kom|S\.?T|S\.?E|M\.?Acc|M\.?Eng|M\.?M|S\.?IP|S\.?Pd\.?Si|Drs|Dra|S\.?FK|SS)\b/i', '', $g['name']), " \t\n\r\0\x0B,.");
                $words = array_values(array_filter(preg_split('/[\s,\.]+/', $rawNoTitle), function ($w) {
                    $wUpper = strtoupper($w);

                    return strlen($w) >= 5 && ! in_array($wUpper, ['RADEN', 'AGNES', 'INDRI', 'NURUL', 'RETNO', 'UTAMI', 'ENDANG'], true);
                }));

                $teacherKeywords[] = [
                    'teacher' => $tObj,
                    'fullname' => $g['name'],
                    'no_title' => $rawNoTitle,
                    'words' => $words,
                ];

                $compactName = strtoupper(preg_replace('/[^A-Z]/', '', $rawNoTitle));
                $compactWords = array_map(fn ($w) => strtoupper(preg_replace('/[^A-Z]/', '', $w)), $words);

                $teacherCompactMap[] = [
                    'teacher' => $tObj,
                    'fullname' => $g['name'],
                    'compact_name' => $compactName,
                    'compact_words' => $compactWords,
                ];
            }
        }

        foreach ($teachers as $t) {
            $cleanName = strtoupper(preg_replace('/[^A-Z]/', '', $t->fullname));
            if (! isset($teacherNameMap[$cleanName])) {
                $teacherNameMap[$cleanName] = $t;
            }

            $cleanNoTitle = strtoupper(preg_replace('/[^A-Z]/', '', preg_replace('/\b(S\.?Pd(?:\s*I)?|S\.?Pd\.?I|M\.?Pd|S\.?Kom|S\.?T|S\.?E|M\.?Acc|M\.?Eng|M\.?M|S\.?IP|S\.?Pd\.?Si|Drs|Dra|S\.?FK|SS)\b/i', '', $t->fullname)));
            if (! isset($teacherNameMap[$cleanNoTitle])) {
                $teacherNameMap[$cleanNoTitle] = $t;
            }

            if (preg_match('/^guru[\._](\d+)$/', $t->username, $m)) {
                $tCode = (int) $m[1];
                if (! isset($teacherCodeMap[$tCode])) {
                    $teacherCodeMap[$tCode] = $t;
                } elseif ($teacherCodeMap[$tCode] instanceof \stdClass && empty($teacherCodeMap[$tCode]->id)) {
                    $teacherCodeMap[$tCode]->id = $t->id;
                }
            }
            if ($t->nip && ! isset($teacherCodeMap[$t->nip])) {
                $teacherCodeMap[$t->nip] = $t;
            }
        }

        // 4. Tentukan Mesin Parser (Smalot atau Direct Stream Matrix)
        $useSmalot = false;
        try {
            $parser = new SmalotParser;
            $pdf = $parser->parseFile($filePath);
            $pages = $pdf->getPages();
            if (count($pages) > 1 && strlen($pages[1]->getText()) > 100) {
                $useSmalot = true;
            }
        } catch (\Throwable $e) {
            $useSmalot = false;
        }

        if ($useSmalot) {
            return $this->parseWithSmalotEngine($pdf, $filePath, $cycle, $classMap, $teacherCodeMap, $teacherNameMap, $teacherKeywords, $teacherCompactMap);
        }

        return $this->parseWithStreamMatrixEngine($filePath, $cycle, $classMap, $teacherCodeMap, $teacherNameMap, $teacherKeywords, $teacherCompactMap);
    }

    /**
     * Parser Mesin 1: Direct Stream Matrix (untuk PDF 1.7 seperti Putaran 1)
     * Menggunakan batas sel dan batas kolom dinamis langsung dari vektor persegi (re f)
     */
    protected function parseWithStreamMatrixEngine(
        string $filePath,
        int $cycle,
        array $classMap,
        array $teacherCodeMap,
        array $teacherNameMap,
        array $teacherKeywords,
        array $teacherCompactMap
    ): array {
        $content = file_get_contents($filePath);
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $content, $rawStreams);

        $daysList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $slotEndTimes = [
            '07:15' => '08:00:00',
            '08:00' => '08:45:00',
            '08:45' => '09:30:00',
            '09:45' => '10:30:00',
            '10:30' => '11:15:00',
            '11:15' => '12:00:00',
            '12:30' => '13:15:00',
            '13:15' => '14:00:00',
            '14:00' => '14:45:00',
            '14:45' => '15:30:00',
        ];

        $schedules = [];

        foreach ($rawStreams[1] as $s) {
            $un = @gzuncompress($s);
            if (! $un || strpos($un, 'Senin') === false || strpos($un, 'Jumat') === false || strpos($un, 'Daftar Isi') !== false) {
                continue;
            }

            // 1. Ekstrak token teks dan label waktu
            $lines = explode("\n", $un);
            $tm = [1, 0, 0, 1, 0, 0];
            $tlm = [1, 0, 0, 1, 0, 0];
            $tokens = [];
            $rawClassName = null;
            $timePositions = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === 'BT') {
                    $tm = [1, 0, 0, 1, 0, 0];
                    $tlm = [1, 0, 0, 1, 0, 0];
                } elseif (preg_match('/^([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s+Tm$/', $line, $lm)) {
                    $tm = [(float) $lm[1], (float) $lm[2], (float) $lm[3], (float) $lm[4], (float) $lm[5], (float) $lm[6]];
                    $tlm = $tm;
                } elseif (preg_match('/^([\d\.\-]+)\s+([\d\.\-]+)\s+Td$/', $line, $lm)) {
                    $tx = (float) $lm[1];
                    $ty = (float) $lm[2];
                    $newE = $tx * $tlm[0] + $ty * $tlm[2] + $tlm[4];
                    $newF = $tx * $tlm[1] + $ty * $tlm[3] + $tlm[5];
                    $tlm[4] = $newE;
                    $tlm[5] = $newF;
                    $tm = $tlm;
                } elseif (preg_match('/\[(.*?)\]\s*TJ|(\((?:[^\\\\\)]|\\\\.)*\))\s*Tj/', $line, $lm)) {
                    $raw = '';
                    if (! empty($lm[1])) {
                        preg_match_all('/\((.*?)(?<!\\\\)\)/s', $lm[1], $pm);
                        foreach ($pm[1] as $p) {
                            $p = preg_replace_callback('/\\\\([0-7]{1,3})/', fn ($o) => chr(octdec($o[1])), $p);
                            $raw .= str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $p);
                        }
                    } else {
                        $p = substr($lm[2], 1, -1);
                        $p = preg_replace_callback('/\\\\([0-7]{1,3})/', fn ($o) => chr(octdec($o[1])), $p);
                        $raw = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $p);
                    }
                    $raw = trim($raw);
                    if ($raw !== '') {
                        if (preg_match('/^(X|XI|XII)\s+[A-Z0-9\s]+$/', $raw) && $tm[5] < 80) {
                            $rawClassName = $raw;
                        }
                        if (preg_match('/^\d{2}:\d{2}$/', $raw) && $tm[4] < 60) {
                            $timePositions[$raw] = $tm[5];
                        }
                        $tokens[] = ['x' => $tm[4], 'y' => $tm[5], 'text' => $raw];
                    }
                }
            }

            if (! $rawClassName) {
                continue;
            }

            $normClassKey = preg_replace('/[^a-z0-9]/', '', strtolower($rawClassName));
            $schoolClass = $classMap[$normClassKey] ?? null;
            $className = $schoolClass ? $schoolClass->name : $rawClassName;
            $classId = $schoolClass ? $schoolClass->id : null;

            // 2. Deteksi batas kolom vertikal (5 hari)
            preg_match_all('/([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s+re\s+f/', $un, $m, PREG_SET_ORDER);
            $vLines = [];
            foreach ($m as $re) {
                $rx = (float) $re[1];
                $ry = (float) $re[2];
                $rw = (float) $re[3];
                $rh = (float) $re[4];
                if ($rw < 2 && $rh > 100) {
                    $vLines[] = round($rx, 1);
                }
            }
            $vLines = array_values(array_unique($vLines));
            sort($vLines);
            $groupedV = [];
            foreach ($vLines as $v) {
                if ($v < 60) {
                    continue;
                }
                if (empty($groupedV) || ($v - end($groupedV)) > 10) {
                    $groupedV[] = $v;
                }
            }
            if (count($groupedV) < 6) {
                continue;
            }

            $dayCols = [];
            for ($d = 0; $d < 5; $d++) {
                $dayCols[$daysList[$d]] = [$groupedV[$d], $groupedV[$d + 1]];
            }

            // 3. Ekstrak sel per hari
            foreach ($dayCols as $day => $xRange) {
                $colCenter = ($xRange[0] + $xRange[1]) / 2;
                $hLines = [];
                foreach ($m as $re) {
                    $rx = (float) $re[1];
                    $ry = (float) $re[2];
                    $rw = (float) $re[3];
                    $rh = (float) $re[4];
                    if ($rh < 2 && $rx <= $colCenter && ($rx + $rw) >= $colCenter && $ry >= 79 && $ry <= 500) {
                        $hLines[] = round($ry, 1);
                    }
                }
                $hLines = array_values(array_unique($hLines));
                sort($hLines);
                $cleanHLines = [];
                foreach ($hLines as $hl) {
                    if (empty($cleanHLines) || ($hl - end($cleanHLines)) > 4) {
                        $cleanHLines[] = $hl;
                    }
                }

                for ($k = 0; $k < count($cleanHLines) - 1; $k++) {
                    $yTop = $cleanHLines[$k];
                    $yBottom = $cleanHLines[$k + 1];
                    if ($yBottom - $yTop < 10) {
                        continue;
                    }

                    // Petakan waktu mulai dan selesai
                    $coveredTimes = [];
                    foreach ($timePositions as $label => $yPos) {
                        if ($yPos >= ($yTop - 5) && $yPos <= ($yBottom + 5)) {
                            $coveredTimes[] = $label;
                        }
                    }
                    if (empty($coveredTimes)) {
                        $minDiff = 999;
                        $closest = '07:15';
                        foreach ($timePositions as $label => $yPos) {
                            $diff = abs($yPos - ($yTop + $yBottom) / 2);
                            if ($diff < $minDiff) {
                                $minDiff = $diff;
                                $closest = $label;
                            }
                        }
                        $coveredTimes = [$closest];
                    }

                    $startTime = reset($coveredTimes).':00';
                    $lastTime = end($coveredTimes);
                    $endTime = $slotEndTimes[$lastTime] ?? '15:30:00';

                    $cellTokens = [];
                    foreach ($tokens as $t) {
                        if ($t['x'] >= ($xRange[0] - 2) && $t['x'] < ($xRange[1] + 2) && $t['y'] >= ($yTop - 1) && $t['y'] < ($yBottom + 1)) {
                            $cellTokens[] = $t['text'];
                        }
                    }
                    $cellStr = trim(implode(' ', $cellTokens));
                    if ($cellStr === '' || $cellStr === '-x-' || $cellStr === '---' || $cellStr === '--') {
                        continue;
                    }

                    $lessons = $this->extractLessonsFromCell($cellStr, $teacherCodeMap, $teacherNameMap, $teacherKeywords, $teacherCompactMap);
                    foreach ($lessons as $ls) {
                        $schedules[] = [
                            'day_of_week' => $day,
                            'cycle_number' => $cycle,
                            'class_name' => $className,
                            'class_id' => $classId,
                            'subject' => $ls['subject'],
                            'teacher_name' => $ls['teacher_name'] ?? 'Guru Pengampu',
                            'teacher_id' => $ls['teacher_id'],
                            'room_name' => $ls['room'] ?? null,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'academic_year' => '2026/2027',
                        ];
                    }
                }
            }
        }

        return $schedules;
    }

    /**
     * Parser Mesin 2: Smalot Parser (untuk PDF 1.4 seperti Putaran 2)
     * Menggunakan batas kolom dan baris dinamis dari stream vektor per halaman
     */
    protected function parseWithSmalotEngine(
        $pdf,
        string $filePath,
        int $cycle,
        array $classMap,
        array $teacherCodeMap,
        array $teacherNameMap,
        array $teacherKeywords,
        array $teacherCompactMap
    ): array {
        $pages = $pdf->getPages();
        $schedules = [];
        $daysList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $slotTimeMap = [
            1 => '07:15:00',
            2 => '08:00:00',
            3 => '08:45:00',
            4 => '09:30:00',
            5 => '10:30:00',
            6 => '11:15:00',
            7 => '12:00:00',
            8 => '13:15:00',
            9 => '14:00:00',
            10 => '14:45:00',
            11 => '15:30:00',
        ];

        for ($p = 1; $p < count($pages); $p++) {
            $page = $pages[$p];
            $lines = array_values(array_filter(array_map('trim', explode("\n", $page->getText())), fn ($l) => $l !== ''));
            if (count($lines) < 2) {
                continue;
            }

            $rawClassName = $lines[1];
            $normClassKey = preg_replace('/[^a-z0-9]/', '', strtolower($rawClassName));
            $schoolClass = $classMap[$normClassKey] ?? null;
            $className = $schoolClass ? $schoolClass->name : $rawClassName;
            $classId = $schoolClass ? $schoolClass->id : null;

            $contentsObj = $page->get('Contents');
            if (! $contentsObj || ! method_exists($contentsObj, 'getContent')) {
                continue;
            }
            $streamContent = $contentsObj->getContent();
            if (! $streamContent) {
                continue;
            }

            // 1. Batas Kolom Vertikal
            preg_match_all('/([\d\.\-]+)\s+([\d\.\-]+)\s+m\s+([\d\.\-]+)\s+([\d\.\-]+)\s+l/', $streamContent, $m, PREG_SET_ORDER);
            $vLines = [];
            foreach ($m as $line) {
                if (abs((float) $line[1] - (float) $line[3]) < 2) {
                    $vLines[] = round((float) $line[1], 0);
                }
            }
            $vLines = array_values(array_unique($vLines));
            sort($vLines);
            $divs = array_values(array_filter($vLines, fn ($v) => $v >= 50 && $v <= 750));
            $cleanDivs = [];
            foreach ($divs as $d) {
                if (empty($cleanDivs) || ($d - end($cleanDivs)) > 5) {
                    $cleanDivs[] = $d;
                }
            }
            if (count($cleanDivs) < 6) {
                continue;
            }

            // 2. Garis Waktu Horizontal Stream
            preg_match_all('/([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s+re/', $streamContent, $reMatches, PREG_SET_ORDER);
            $yTime = [];
            foreach ($reMatches as $re) {
                if ((float) $re[1] <= 15 && (float) $re[3] >= 35 && (float) $re[4] <= 2) {
                    $yTime[] = (float) $re[2];
                }
            }
            $yTime = array_values(array_unique($yTime));
            sort($yTime);
            $cleanY = [];
            foreach ($yTime as $y) {
                if (empty($cleanY) || ($y - end($cleanY)) > 5) {
                    $cleanY[] = $y;
                }
            }
            if (count($cleanY) < 12) {
                continue;
            }

            // 3. Hubungkan Skala Smalot
            $dataTm = $page->getDataTm();
            $y0715 = null;
            foreach ($dataTm as $item) {
                $text = trim($item[1] ?? '');
                if ($text === '07:15') {
                    $y0715 = (float) $item[0][5];
                    break;
                }
            }
            if ($y0715 === null) {
                continue;
            }

            $stream0715Center = ($cleanY[1] + $cleanY[2]) / 2;
            $scale = -0.72611;
            $topSmalot = $y0715 - ($cleanY[1] - $stream0715Center) * $scale;

            $rawTokens = [];
            foreach ($dataTm as $item) {
                $text = trim($item[1] ?? '');
                if ($text === '' || in_array($text, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'], true)) {
                    continue;
                }
                if (str_contains($text, 'SMK NEGERI') || preg_match('/^(X|XI|XII)\s+/', $text) || str_contains($text, 'Jadwal dihasilkan') || str_contains($text, 'Kembali ke')) {
                    continue;
                }
                $rawTokens[] = [
                    'x' => (float) $item[0][4],
                    'y' => (float) $item[0][5],
                    'text' => $text,
                ];
            }

            $dayCols = [];
            $dayHBorders = [];
            for ($d = 0; $d < 5; $d++) {
                $dName = $daysList[$d];
                $xLeft = 118.75 + $cleanDivs[$d] * 0.72611;
                $xRight = 118.75 + $cleanDivs[$d + 1] * 0.72611;
                $dayCols[$dName] = [$xLeft, $xRight];

                $xStreamMid = ($cleanDivs[$d] + $cleanDivs[$d + 1]) / 2;
                $yBorders = [];
                foreach ($reMatches as $re) {
                    $rx = (float) $re[1];
                    $ry = (float) $re[2];
                    $rw = (float) $re[3];
                    $rh = (float) $re[4];
                    if ($rh <= 2 && $rx <= $xStreamMid && ($rx + $rw) >= $xStreamMid && $ry >= ($cleanY[1] - 5) && $ry <= ($cleanY[11] + 5)) {
                        $yBorders[] = round($ry, 0);
                    }
                }
                $yBorders = array_values(array_unique($yBorders));
                sort($yBorders);
                $cleanHB = [];
                foreach ($yBorders as $yb) {
                    if (empty($cleanHB) || ($yb - end($cleanHB)) > 5) {
                        $cleanHB[] = $yb;
                    }
                }
                $dayHBorders[$dName] = $cleanHB;
            }

            foreach ($daysList as $dName) {
                $xRange = $dayCols[$dName];
                $hBorders = $dayHBorders[$dName];
                if (count($hBorders) < 2) {
                    continue;
                }

                for ($k = 0; $k < count($hBorders) - 1; $k++) {
                    $syTop = $hBorders[$k];
                    $syBottom = $hBorders[$k + 1];
                    if ($syBottom - $syTop < 10) {
                        continue;
                    }

                    $smalotYTop = $topSmalot + ($syTop - $cleanY[1]) * $scale;
                    $smalotYBottom = $topSmalot + ($syBottom - $cleanY[1]) * $scale;
                    $maxY = max($smalotYTop, $smalotYBottom);
                    $minY = min($smalotYTop, $smalotYBottom);

                    $cellTokens = [];
                    foreach ($rawTokens as $rt) {
                        if ($rt['x'] >= ($xRange[0] - 2) && $rt['x'] < ($xRange[1] + 2)) {
                            if ($rt['y'] >= ($minY - 4) && $rt['y'] <= ($maxY + 4)) {
                                $cellTokens[] = $rt;
                            }
                        }
                    }

                    if (empty($cellTokens)) {
                        continue;
                    }

                    usort($cellTokens, function ($a, $b) {
                        if (abs($a['y'] - $b['y']) < 3.0) {
                            return $a['x'] <=> $b['x'];
                        }

                        return $b['y'] <=> $a['y'];
                    });

                    $str = '';
                    for ($ti = 0; $ti < count($cellTokens); $ti++) {
                        $cur = $cellTokens[$ti];
                        if ($str === '') {
                            $str = $cur['text'];
                        } else {
                            $prev = $cellTokens[$ti - 1];
                            $sameLine = abs($cur['y'] - $prev['y']) < 3.0;
                            if ($sameLine) {
                                $gap = $cur['x'] - ($prev['x'] + strlen($prev['text']) * 5);
                                if ($gap < 3 && strlen($prev['text']) < 5 && strlen($cur['text']) < 5) {
                                    $str .= $cur['text'];
                                } else {
                                    $str .= ' '.$cur['text'];
                                }
                            } else {
                                $str .= ' '.$cur['text'];
                            }
                        }
                    }

                    $str = trim($str);
                    if ($str === '' || $str === '-x-' || $str === '---' || $str === '--' || str_contains($str, 'FET 5.44.9') || str_contains($str, 'pada 26/08')) {
                        continue;
                    }

                    // Petakan indeks jam
                    $startIdx = 1;
                    $minTopDiff = 999;
                    for ($si = 1; $si <= 11; $si++) {
                        if (isset($cleanY[$si])) {
                            $diff = abs($syTop - $cleanY[$si]);
                            if ($diff < $minTopDiff) {
                                $minTopDiff = $diff;
                                $startIdx = $si;
                            }
                        }
                    }
                    $startTime = $slotTimeMap[$startIdx] ?? '07:15:00';

                    $endIdx = 11;
                    $minBottomDiff = 999;
                    for ($si = 1; $si <= 11; $si++) {
                        if (isset($cleanY[$si])) {
                            $diff = abs($syBottom - $cleanY[$si]);
                            if ($diff < $minBottomDiff) {
                                $minBottomDiff = $diff;
                                $endIdx = $si;
                            }
                        }
                    }
                    $endTime = $slotTimeMap[$endIdx] ?? '15:30:00';

                    $lessons = $this->extractLessonsFromCell($str, $teacherCodeMap, $teacherNameMap, $teacherKeywords, $teacherCompactMap);
                    foreach ($lessons as $ls) {
                        $schedules[] = [
                            'day_of_week' => $dName,
                            'cycle_number' => $cycle,
                            'class_name' => $className,
                            'class_id' => $classId,
                            'subject' => $ls['subject'],
                            'teacher_name' => $ls['teacher_name'] ?? 'Guru Pengampu',
                            'teacher_id' => $ls['teacher_id'],
                            'room_name' => $ls['room'] ?? null,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'academic_year' => '2026/2027',
                        ];
                    }
                }
            }
        }

        return $schedules;
    }

    /**
     * Ekstrak mata pelajaran dan guru dari teks sel yang sudah terisolasi sempurna
     */
    protected function extractLessonsFromCell(
        string $text,
        array $teacherCodeMap,
        array $teacherNameMap,
        array $teacherKeywords,
        array $teacherCompactMap
    ): array {
        // Kamus Normalisasi Mata Pelajaran Resmi SMK N 1 Bantul
        $canonicalMapel = [
            'Projek IP AS' => 'Projek IPAS',
            'Projek IPAS' => 'Projek IPAS',
            'KK 3 RPL Pemrograman W ebsite' => 'KK 3 RPL Pemrograman Website',
            'KK 3 RPL Pemrograman Website' => 'KK 3 RPL Pemrograman Website',
            'DDPK PM' => 'DDPK Pemasaran',
            'DDPK MP' => 'DDPK Manajemen Perkantoran',
            'Mapil AK' => 'Mata Pelajaran Pilihan Akuntansi',
            'KK 1 DKV Karya UI/UX 20' => 'KK 1 DKV Karya UI/UX',
            'Kreatifitas, Inovasi dan kewirausahaan' => 'Kreativitas, Inovasi dan Kewirausahaan',
            'Kreatifitas, Inovasi dan' => 'Kreativitas, Inovasi dan Kewirausahaan',
            'Online) LAB 8' => 'Mapel Pilihan BR (Bisnis Online)',
            'KK BR (Bisnis Retail)' => 'KK BR (Bisnis Retail)',
            'KK BD ( Bisnid Digital)' => 'KK BD (Bisnis Digital)',
            'KK BD (Bisnid Digital)' => 'KK BD (Bisnis Digital)',
            'DDPK 3 RPL Dasar Algoritma Pemrograman' => 'DDPK 3 RPL Dasar Algoritma Pemrograman',
            'DDPK 1 TKJ K3LH & Budaya' => 'DDPK 1 TKJ K3LH dan Budaya Kerja',
            'DDPK 1 RPL Dasar Profesi dan Budaya kerja' => 'DDPK 1 RPL Dasar Profesi dan Budaya Kerja',
            'DDPK 4 RPL Dasar Web dan jaringan Komputer' => 'DDPK 4 RPL Dasar Web dan Jaringan Komputer',
            'DDPK 4 RPL Dasar' => 'DDPK 4 RPL Dasar Web dan Jaringan Komputer',
            'KK 4 RPL Pemrograman' => 'KK 4 RPL Pemrograman Perangkat Bergerak',
            'KK 2 TKJ' => 'KK 2 TKJ Teknologi Jaringan Berbasis Luas',
            'KK 4 TKJ Administrasi' => 'KK 4 TKJ Administrasi Sistem Jaringan',
        ];

        // 1. Bersihkan format FET
        $cleanText = $text;
        $cleanText = preg_replace('/\b\d+\.\d+\.\d+\s+pada\s+[\d\/\.\s:]+/i', '', $cleanText);
        $cleanText = preg_replace('/(["\'“”]\s*\d{1,2})([A-Za-z])/', '$1. $2', $cleanText);
        $cleanText = preg_replace('/(["\'“”]\s*\d{1,2}),([A-Za-z])/', '$1. $2', $cleanText);
        $cleanText = preg_replace('/"(\d)\s+(\d)([A-Za-z])/', '"$1$2. $3', $cleanText);
        $cleanText = preg_replace('/"(\d)\s+(\d)\s*[\.:]/', '"$1$2. ', $cleanText);
        $cleanText = str_ireplace(
            ['W ebsite', 'IP AS', 'Konseling Projek', 'T elekomunikasi', 'LAB 1 1'],
            ['Website', 'IPAS', 'Konseling', 'Telekomunikasi', 'LAB 11'],
            $cleanText
        );

        // 2. Ruangan pada jadwal kelas dikosongkan (null) sesuai permintaan user, agar diisi presisi dari berkas jadwal ruangan
        $room = null;
        if (preg_match('/\b(LAB\s*\d+|OR\s*\d+|R\.\s*\d+|BENGKEL\s*\d+|LAB\s*IPA)\b/i', $cleanText, $rm)) {
            $cleanText = trim(str_ireplace($rm[0], '', $cleanText));
        }

        // 3. Pola Kode FET: "KODE. NAMA GURU"
        preg_match_all('/["\'“”]\s*(\d{1,3})\s*[\.:,\s]\s*([^"\'“”\n\r]+?)(?=["\'“”]|\b(?:LAB\s*\d+|OR\s*\d+|R\.\s*\d+|BENGKEL\s*\d+|LAB\s*IPA)\b|$)/ui', $cleanText, $matches, PREG_OFFSET_CAPTURE);

        $results = [];
        if (count($matches[0]) > 0) {
            $code = (int) $matches[1][0][0];
            $rawTName = trim($matches[2][0][0]);

            // Prioritas 1: Name-First Resolution (Nama guru yang tertulis di sel lebih dipercaya daripada kode FET)
            $teacher = $this->resolveTeacher($rawTName, $code, $teacherNameMap, $teacherKeywords, $teacherCompactMap);

            $rawSubject = substr($cleanText, 0, $matches[0][0][1]);
            $cleanSubject = trim($rawSubject, " \t\n\r\0\x0B\"'-,.;:");
            $cleanSubject = preg_replace('/["\'“”]+\s*\d{1,3}\s*[\.:,\s]*[A-Za-z\.]*/', '', $cleanSubject);
            $cleanSubject = str_replace(['"', "'", '“', '”'], '', $cleanSubject);

            // Bersihkan nama guru yang bocor (spillover) dari sel atasnya
            $cleanSubject = preg_replace('/^(?:[A-Z\s]+?\b)?(?:NURHIDA\s*Y\s*A\s*T\s+SULISTIA\s*W\s*A\s*N|FITRI\s+SARASW\s*A\s*TI|DEWINT\s*A\s+SARI|CHRISTIY\s*A\s*NTI\s+MANULLANG|P\s*A\s*NGASTUTI|RYAWIDY\s*A\s*NINGTY\s*A\s*S|WINAJENG|SARASW\s*A\s*TI|SELIY\s*A\s*NA|APRILI\s*A|YOGA\s+PAMUNGKAS|HERU\s+PURNOMO|RAHADIAN|AW\s*A\s*LIA\s*TI|BHINT\s*A\s*R\s*TI|KUNT\s*A\s*RI)(?:,?\s*(?:S\.?Pd(?:\s*I)?|S\.?Pd\.?I|M\.?Pd|S\.?S|S\.?Kom)?[\.,]*)?\s*/i', '', $cleanSubject);
            $cleanSubject = trim($cleanSubject, " \t\n\r\0\x0B\"'-,.;:");

            // Bersihkan sisa nama guru yang menempel di judul mapel
            if ($teacher && isset($teacher->fullname)) {
                $cleanT = preg_replace('/\b(S\.?Pd(?:\s*I)?|S\.?Pd\.?I|M\.?Pd|S\.?Kom|S\.?T|S\.?E|M\.?Acc|M\.?Eng|M\.?M|S\.?IP|S\.?Pd\.?Si|Drs|Dra|S\.?FK|SS)\b/i', '', $teacher->fullname);
                $cleanT = trim($cleanT, " \t\n\r\0\x0B,.");
                if (strlen($cleanT) >= 4) {
                    $cleanSubject = trim(str_ireplace($cleanT, '', $cleanSubject));
                }
            }
            foreach ($teacherKeywords as $tk) {
                if (stripos($cleanSubject, $tk['no_title']) !== false) {
                    $cleanSubject = trim(str_ireplace($tk['no_title'], '', $cleanSubject));
                }
            }

            $cleanSubject = trim(preg_replace('/\b(S\.?Pd(?:\s*I)?|S\.?Pd\.?I|M\.?Pd|S\.?Kom|S\.?T|S\.?E|M\.?Acc|M\.?Eng|M\.?M|S\.?IP|S\.?Pd\.?Si|Drs|Dra|S\.?FK|SS)\b/i', '', $cleanSubject), " \t\n\r\0\x0B,.");
            $cleanSubject = trim(preg_replace('/\s+/', ' ', $cleanSubject), " \t\n\r\0\x0B\"'-,.;:");

            if (isset($canonicalMapel[$cleanSubject])) {
                $cleanSubject = $canonicalMapel[$cleanSubject];
            }

            if ($cleanSubject === '' || $cleanSubject === '-x-' || preg_match('/^(?:LAB\s*\d+|OR\s*\d+|R\.\s*\d+|BENGKEL\s*\d+|LAB\s*IPA)$/i', $cleanSubject)) {
                $cleanSubject = 'KBM Mandiri / Mapel';
            }

            $teacherName = $teacher ? ($teacher->fullname ?? $rawTName) : $rawTName;
            $teacherId = $teacher ? ($teacher->id ?? null) : null;

            $results[] = [
                'subject' => $cleanSubject,
                'teacher_name' => $teacherName,
                'teacher_id' => $teacherId,
                'room' => $room,
            ];
        } else {
            // Pencocokan cadangan jika sel tidak memuat pola tanda kutip
            $matchedTeacher = $this->resolveTeacher($cleanText, null, $teacherNameMap, $teacherKeywords, $teacherCompactMap);

            // Bersihkan nama guru dari subject
            if ($matchedTeacher && isset($matchedTeacher->fullname)) {
                $cleanT = preg_replace('/\b(S\.?Pd(?:\s*I)?|S\.?Pd\.?I|M\.?Pd|S\.?Kom|S\.?T|S\.?E|M\.?Acc|M\.?Eng|M\.?M|S\.?IP|S\.?Pd\.?Si|Drs|Dra|S\.?FK|SS)\b/i', '', $matchedTeacher->fullname);
                $cleanT = trim($cleanT, " \t\n\r\0\x0B,.");
                $cleanText = trim(str_ireplace($cleanT, '', $cleanText));
            }
            foreach ($teacherKeywords as $tk) {
                if (stripos($cleanText, $tk['no_title']) !== false) {
                    $cleanText = trim(str_ireplace($tk['no_title'], '', $cleanText));
                }
            }

            // Bersihkan nama guru yang bocor (spillover) dari sel atasnya
            $cleanText = preg_replace('/^(?:[A-Z\s]+?\b)?(?:NURHIDA\s*Y\s*A\s*T\s+SULISTIA\s*W\s*A\s*N|FITRI\s+SARASW\s*A\s*TI|DEWINT\s*A\s+SARI|CHRISTIY\s*A\s*NTI\s+MANULLANG|P\s*A\s*NGASTUTI|RYAWIDY\s*A\s*NINGTY\s*A\s*S|WINAJENG|SARASW\s*A\s*TI|SELIY\s*A\s*NA|APRILI\s*A|YOGA\s+PAMUNGKAS|HERU\s+PURNOMO|RAHADIAN|AW\s*A\s*LIA\s*TI|BHINT\s*A\s*R\s*TI|KUNT\s*A\s*RI)(?:,?\s*(?:S\.?Pd(?:\s*I)?|S\.?Pd\.?I|M\.?Pd|S\.?S|S\.?Kom)?[\.,]*)?\s*/i', '', $cleanText);
            $cleanText = trim(preg_replace('/\b(S\.?Pd(?:\s*I)?|S\.?Pd\.?I|M\.?Pd|S\.?Kom|S\.?T|S\.?E|M\.?Acc|M\.?Eng|M\.?M|S\.?IP|S\.?Pd\.?Si|Drs|Dra|S\.?FK|SS)\b/i', '', $cleanText), " \t\n\r\0\x0B,.");
            $cleanText = preg_replace('/["\'“”]+\s*\d{1,3}\s*[\.:,\s]*[A-Za-z\.]*/', '', $cleanText);
            $cleanText = str_replace(['"', "'", '“', '”'], '', $cleanText);
            $cleanText = trim(preg_replace('/\s+/', ' ', $cleanText), " \t\n\r\0\x0B\"'-,.;:");

            if (isset($canonicalMapel[$cleanText])) {
                $cleanText = $canonicalMapel[$cleanText];
            }

            if ($cleanText === '' || $cleanText === '-x-' || preg_match('/^(?:LAB\s*\d+|OR\s*\d+|R\.\s*\d+|BENGKEL\s*\d+|LAB\s*IPA)$/i', $cleanText)) {
                $cleanText = 'KBM Mandiri / Mapel';
            }

            $results[] = [
                'subject' => $cleanText,
                'teacher_name' => $matchedTeacher ? ($matchedTeacher->fullname ?? 'Guru Pengampu') : 'Guru Pengampu',
                'teacher_id' => $matchedTeacher ? ($matchedTeacher->id ?? null) : null,
                'room' => $room,
            ];
        }

        return $results;
    }

    /**
     * Resolusi Guru Berbasis Nama Resmi (Prioritas 1) & Dynamic FET Dictionary (Prioritas 2)
     */
    protected function resolveTeacher(
        ?string $rawTName,
        ?int $code,
        array $teacherNameMap,
        array $teacherKeywords,
        array $teacherCompactMap
    ): ?object {
        if ($rawTName !== null && trim($rawTName) !== '') {
            $rawClean = trim($rawTName, " \t\n\r\0\x0B\"'-,.;:");

            // Normalisasi ejaan khusus FET & variasi OCR
            if (preg_match('/^W\s*A\s*T\s*I\b/i', $rawClean)) {
                $rawClean = 'WATI, S.Pd I.';
            }
            $rawClean = preg_replace('/SUSILO\s*W\s*A?\s*TI/i', 'SUSILAWATI', $rawClean);

            // 1. Exact Clean Match (Huruf saja)
            $cleanN = strtoupper(preg_replace('/[^A-Z]/', '', $rawClean));
            if ($cleanN !== '' && isset($teacherNameMap[$cleanN])) {
                $t = $teacherNameMap[$cleanN];
                if ($code && $code > 0) {
                    $this->dynamicFetMap[$code] = $t;
                }

                return $t;
            }

            // 2. Exact Clean Match tanpa Gelar Akademik
            $noTitle = strtoupper(preg_replace('/[^A-Z]/', '', preg_replace('/\b(S\.?Pd(?:\s*I)?|S\.?Pd\.?I|M\.?Pd|S\.?Kom|S\.?T|S\.?E|M\.?Acc|M\.?Eng|M\.?M|S\.?IP|S\.?Pd\.?Si|Drs|Dra|S\.?FK|SS)\b/i', '', $rawClean)));
            if ($noTitle !== '' && isset($teacherNameMap[$noTitle])) {
                $t = $teacherNameMap[$noTitle];
                if ($code && $code > 0) {
                    $this->dynamicFetMap[$code] = $t;
                }

                return $t;
            }

            // 3. Compact Name & Token Match (Menangani spasi OCR yang terputus)
            foreach ($teacherCompactMap as $tc) {
                if (strlen($tc['compact_name']) >= 6 && ($cleanN === $tc['compact_name'] || str_contains($cleanN, $tc['compact_name']) || str_contains($tc['compact_name'], $cleanN))) {
                    $t = $tc['teacher'];
                    if ($code && $code > 0) {
                        $this->dynamicFetMap[$code] = $t;
                    }

                    return $t;
                }
                foreach ($tc['compact_words'] as $cw) {
                    if (strlen($cw) >= 5 && str_contains($cleanN, $cw)) {
                        $t = $tc['teacher'];
                        if ($code && $code > 0) {
                            $this->dynamicFetMap[$code] = $t;
                        }

                        return $t;
                    }
                }
            }

            // 4. Keyword Match untuk nama tunggal spesifik
            foreach ($teacherKeywords as $tk) {
                if (! empty($tk['words'])) {
                    foreach ($tk['words'] as $kw) {
                        if (strlen($kw) >= 4 && stripos($rawClean, $kw) !== false) {
                            $t = $tk['teacher'];
                            if ($code && $code > 0) {
                                $this->dynamicFetMap[$code] = $t;
                            }

                            return $t;
                        }
                    }
                }
            }
        }

        // Prioritas 2: Rujuk ke Dynamic FET Dictionary jika nama terpotong tapi kodenya pernah dikenali
        if ($code && $code > 0 && isset($this->dynamicFetMap[$code])) {
            return $this->dynamicFetMap[$code];
        }

        return null;
    }

    /**
     * Parse Berkas PDF Jadwal Ruangan (Matriks 48 Rombel Kelas x Hari x Jam Pelajaran)
     * Mengembalikan array: [day][normClass][] = ['start' => '...', 'end' => '...', 'room' => '...']
     */
    public function parseRoomPdf(string $filePath, int $cycle = 1): array
    {
        @ini_set('memory_limit', '512M');
        $parser = new SmalotParser;
        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();

        $classes = SchoolClass::all();
        $classMap = [];
        foreach ($classes as $c) {
            $norm = preg_replace('/[^a-z0-9]/', '', strtolower($c->name));
            $classMap[$norm] = $c;
        }

        $pageDayMapping = [
            0 => ['top' => 'Senin', 'bottom' => 'Selasa', 'split_y' => 260],
            1 => ['top' => 'Rabu', 'bottom' => 'Kamis', 'split_y' => 310],
            2 => ['top' => 'Jumat', 'bottom' => null, 'split_y' => 0],
        ];

        // 1. Ekstrak Kolom Kelas dari Header Halaman 0
        $page0 = $pages[0];
        $dataTm0 = $page0->getDataTm();
        $headerTokens = [];
        foreach ($dataTm0 as $item) {
            $y = round((float) $item[0][5], 1);
            if ($y >= 490 && $y <= 545) {
                $headerTokens[] = [
                    'x' => round((float) $item[0][4], 1),
                    'y' => $y,
                    'text' => trim($item[1] ?? ''),
                ];
            }
        }
        usort($headerTokens, fn ($a, $b) => $a['x'] <=> $b['x']);
        $columns = [];
        foreach ($headerTokens as $tok) {
            if ($tok['text'] === '' || in_array($tok['text'], ['Hari', 'Jam', 'Waktu', 'UPACARA', 'BENDERA', 'ISTIRAHAT'])) {
                continue;
            }
            $found = false;
            foreach ($columns as &$col) {
                if (abs($col['x_avg'] - $tok['x']) < 6.0) {
                    $col['tokens'][] = $tok;
                    $col['x_avg'] = array_sum(array_column($col['tokens'], 'x')) / count($col['tokens']);
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $columns[] = [
                    'x_avg' => $tok['x'],
                    'tokens' => [$tok],
                ];
            }
        }

        $classColumns = [];
        foreach ($columns as $col) {
            usort($col['tokens'], fn ($a, $b) => $b['y'] <=> $a['y']);
            $rawName = implode(' ', array_column($col['tokens'], 'text'));
            $conv = preg_replace('/^10\b/i', 'X', $rawName);
            $conv = preg_replace('/^11\b/i', 'XI', $conv);
            $conv = preg_replace('/^12\b/i', 'XII', $conv);
            $norm = preg_replace('/[^a-z0-9]/', '', strtolower($conv));
            $sc = $classMap[$norm] ?? null;
            if ($sc) {
                $classColumns[] = [
                    'x' => $col['x_avg'],
                    'class_id' => $sc->id,
                    'class_name' => $sc->name,
                    'norm' => $norm,
                ];
            }
        }

        $roomMap = [];

        foreach ($pages as $pIdx => $page) {
            $pInfo = $pageDayMapping[$pIdx] ?? null;
            if (! $pInfo) {
                continue;
            }

            $dataTm = $page->getDataTm();

            // Deteksi baris waktu (time rows)
            $timeRows = [];
            foreach ($dataTm as $item) {
                $x = round((float) $item[0][4], 1);
                $y = round((float) $item[0][5], 1);
                $text = trim($item[1] ?? '');
                if (preg_match('/(\d{1,2}[\.:]\d{2})\s*[-–]\s*(\d{1,2}[\.:]\d{2})/', $text, $tm)) {
                    $sTime = str_replace('.', ':', $tm[1]).':00';
                    $eTime = str_replace('.', ':', $tm[2]).':00';
                    if (strlen($sTime) === 7) {
                        $sTime = '0'.$sTime;
                    }
                    if (strlen($eTime) === 7) {
                        $eTime = '0'.$eTime;
                    }

                    $day = ($pInfo['bottom'] !== null && $y < $pInfo['split_y']) ? $pInfo['bottom'] : $pInfo['top'];
                    $timeRows[] = [
                        'y' => $y,
                        'day' => $day,
                        'start' => $sTime,
                        'end' => $eTime,
                    ];
                }
            }

            usort($timeRows, fn ($a, $b) => $b['y'] <=> $a['y']);

            // Untuk tiap baris waktu dan tiap kolom kelas, ekstrak kode ruangan
            foreach ($timeRows as $tr) {
                foreach ($classColumns as $cc) {
                    $candidates = [];
                    foreach ($dataTm as $item) {
                        $x = round((float) $item[0][4], 1);
                        $y = round((float) $item[0][5], 1);
                        $text = trim($item[1] ?? '');
                        if ($text === '' || in_array($text, ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'IST', 'UPACARA', 'BENDERA'])) {
                            continue;
                        }
                        if (abs($x - $cc['x']) <= 6.8 && abs($y - $tr['y']) <= 6.0) {
                            $candidates[] = $text;
                        }
                    }

                    if (! empty($candidates)) {
                        $rawRoom = implode(' ', $candidates);
                        $cleanRoom = $this->normalizeRoomName($rawRoom);
                        if ($cleanRoom) {
                            $roomMap[$tr['day']][$cc['norm']][] = [
                                'start' => $tr['start'],
                                'end' => $tr['end'],
                                'room' => $cleanRoom,
                            ];
                        }
                    }
                }
            }
        }

        return $roomMap;
    }

    /**
     * Normalisasi nama/kode ruangan
     */
    public function normalizeRoomName(string $raw): ?string
    {
        $clean = trim($raw, " \t\n\r\0\x0B\"'-,.;:");
        if ($clean === '' || $clean === '-x-' || $clean === '---' || $clean === '--') {
            return null;
        }

        $upper = strtoupper($clean);
        if (preg_match('/^L(?:AB)?\.?\s*(\d+)/i', $upper, $m)) {
            return 'LAB '.$m[1];
        }
        if (str_contains($upper, 'IPA')) {
            return 'LAB IPA';
        }
        if (preg_match('/^OR\s*(\d*)/i', $upper, $m)) {
            return ! empty($m[1]) ? 'OR'.$m[1] : 'OR1';
        }
        if (str_contains($upper, 'HALL')) {
            return 'HALL';
        }
        if (is_numeric($clean)) {
            return 'Ruang '.$clean;
        }

        return $clean;
    }

    /**
     * Menggabungkan jadwal kelas (yang room_name masih null) dengan matriks ruangan
     */
    public function mergeSchedulesWithRooms(array &$schedules, array $roomMap): void
    {
        foreach ($schedules as &$sch) {
            $day = $sch['day_of_week'];
            $normClass = preg_replace('/[^a-z0-9]/', '', strtolower($sch['class_name']));
            $sStart = $sch['start_time'];
            $sEnd = $sch['end_time'];

            $matchedRoom = null;
            if (isset($roomMap[$day][$normClass])) {
                foreach ($roomMap[$day][$normClass] as $slot) {
                    // Cek irisan waktu: slot.start < s.end && slot.end > s.start
                    if ($slot['start'] < $sEnd && $slot['end'] > $sStart) {
                        $matchedRoom = $slot['room'];
                        break;
                    }
                }
            }

            $sch['room_name'] = $matchedRoom ?: ($sch['room_name'] ?? 'Ruang Kelas');
        }
    }
}
