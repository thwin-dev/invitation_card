<?php

namespace App\Modules\Product\Support;

final class Ean13
{
    public static function bars(string $code): ?string
    {
        if (! preg_match('/^\d{13}$/D', $code)) {
            return null;
        }
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $code[$i] * ($i % 2 ? 3 : 1);
        }
        if ($sum % 10) {
            return null;
        }
        $left = ['0001101', '0011001', '0010011', '0111101', '0100011', '0110001', '0101111', '0111011', '0110111', '0001011'];
        $even = ['0100111', '0110011', '0011011', '0100001', '0011101', '0111001', '0000101', '0010001', '0001001', '0010111'];
        $right = ['1110010', '1100110', '1101100', '1000010', '1011100', '1001110', '1010000', '1000100', '1001000', '1110100'];
        $parity = ['LLLLLL', 'LLGLGG', 'LLGGLG', 'LLGGGL', 'LGLLGG', 'LGGLLG', 'LGGGLL', 'LGLGLG', 'LGLGGL', 'LGGLGL'];
        $bits = '101';
        for ($i = 1; $i <= 6; $i++) {
            $bits .= $parity[(int) $code[0]][$i - 1] === 'L' ? $left[(int) $code[$i]] : $even[(int) $code[$i]];
        }
        $bits .= '01010';
        for ($i = 7; $i <= 12; $i++) {
            $bits .= $right[(int) $code[$i]];
        }

        return $bits.'101';
    }
}
