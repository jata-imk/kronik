<?php

namespace App\Services\Communications;

final class SmsText
{
    private const GSM = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    private const EXTENDED = "\f^{}\\[~]|€";

    /** Counts encoded units and packs complete characters, including extension escapes/surrogates. */
    public function measure(string $text, bool $detailed = false): array
    {
        if ($text === '' || ! mb_check_encoding($text, 'UTF-8')) {
            return $this->result(0, false, 0, $detailed);
        }
        $characters = mb_str_split($text);
        $unicode = false;
        foreach ($characters as $character) {
            if (! str_contains(self::GSM.self::EXTENDED, $character)) {
                $unicode = true;
                break;
            }
        }
        $units = array_map(fn ($character) => $unicode
            ? strlen(mb_convert_encoding($character, 'UTF-16BE', 'UTF-8')) / 2
            : (str_contains(self::EXTENDED, $character) ? 2 : 1), $characters);
        if (array_sum($units) <= ($unicode ? 70 : 160)) {
            return $this->result(1, $unicode, (int) array_sum($units), $detailed);
        }
        $capacity = $unicode ? 67 : 153;
        $segments = 1;
        $used = 0;
        foreach ($units as $length) {
            if ($used + $length > $capacity) {
                $segments++;
                $used = 0;
            }
            $used += $length;
        }

        return $this->result($segments, $unicode, (int) array_sum($units), $detailed);
    }

    private function result(int $segments, bool $unicode, int $units, bool $detailed): array
    {
        return ['segments' => $segments, 'unicode' => $unicode] + ($detailed ? ['units' => $units, 'encoding' => $unicode ? 'Unicode (UTF-16)' : 'GSM-7'] : []);
    }
}
