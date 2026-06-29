<?php

namespace App\Services\PurchaseInvoices;

use Carbon\Carbon;

class DataNormalizer
{
    public function money(null|string|float|int $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = preg_replace('/[^\d,.\-]/', '', (string) $value) ?? '';

        if ($clean === '') {
            return null;
        }

        $lastComma = strrpos($clean, ',');
        $lastDot = strrpos($clean, '.');

        if ($lastComma !== false && $lastDot !== false) {
            $decimal = $lastComma > $lastDot ? ',' : '.';
            $thousand = $decimal === ',' ? '.' : ',';
            $clean = str_replace($thousand, '', $clean);
            $clean = str_replace($decimal, '.', $clean);
        } elseif ($lastComma !== false) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }

        return round((float) $clean, 2);
    }

    public function date(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.Y', 'd/m/y', 'd-m-y'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
                //
            }
        }

        return null;
    }
}
