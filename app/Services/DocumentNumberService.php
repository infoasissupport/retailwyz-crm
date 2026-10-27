<?php

namespace App\Services;

use App\Models\NumberSequence;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Sequential document numbers (INV-00001...). The sequence row is locked so
 * concurrent requests never receive the same number; call it inside the
 * transaction that saves the document so a rollback releases nothing twice.
 */
class DocumentNumberService
{
    public function __construct(private SettingsService $settings) {}

    public function next(string $sequence, string $prefixSetting, int $pad = 5): string
    {
        $prefix = (string) $this->settings->get($prefixSetting, '');

        return DB::transaction(function () use ($sequence, $prefix, $pad) {
            $row = NumberSequence::where('key', $sequence)->lockForUpdate()->first() ?? $this->create($sequence);

            $number = $row->next_value;
            $row->increment('next_value');

            return $prefix.str_pad((string) $number, $pad, '0', STR_PAD_LEFT);
        });
    }

    private function create(string $sequence): NumberSequence
    {
        try {
            return DB::transaction(fn () => NumberSequence::create(['key' => $sequence, 'next_value' => 1]));
        } catch (UniqueConstraintViolationException) {
            return NumberSequence::where('key', $sequence)->lockForUpdate()->firstOrFail();
        }
    }
}
