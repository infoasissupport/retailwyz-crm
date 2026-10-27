<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Business settings stored in the `settings` table. Keys are unique across
 * groups; the group only organises the settings screens.
 */
class SettingsService
{
    private const CACHE_KEY = 'app.settings';

    /** @var array<string, array<string, string>> group => key => default */
    public const DEFAULTS = [
        'general' => [
            'business_name' => 'FurnishWyz',
            'business_phone' => '',
            'business_email' => '',
            'business_address' => '',
            'business_tax_number' => '',
            'currency_code' => 'INR',
            'currency_symbol' => '₹',
            'date_format' => 'd-m-Y',
        ],
        'sales' => [
            'prices_include_tax' => '0',
            'allow_negative_stock' => '0',
        ],
        'purchases' => [
            'purchase_prices_include_tax' => '0',
            'update_cost_on_purchase' => '1',
        ],
        'invoice' => [
            'invoice_prefix' => 'INV-',
            'purchase_prefix' => 'PO-',
            'sale_return_prefix' => 'SR-',
            'purchase_return_prefix' => 'PR-',
            'payment_prefix' => 'PAY-',
            'expense_prefix' => 'EXP-',
            'adjustment_prefix' => 'ADJ-',
            'delivery_prefix' => 'DEL-',
            'invoice_footer' => 'Thank you for your business.',
        ],
    ];

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    public function bool(string $key): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        // Only stored values are cached, so defaults added in code apply without a cache clear.
        return array_merge(
            array_merge(...array_values(self::DEFAULTS)),
            Cache::rememberForever(self::CACHE_KEY, fn () => Setting::pluck('value', 'key')->all()),
        );
    }

    /** @return array<string, string|null> */
    public function group(string $group): array
    {
        return array_intersect_key($this->all(), self::DEFAULTS[$group] ?? []);
    }

    /** @param array<string, mixed> $values */
    public function setMany(string $group, array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS[$group] ?? []) as $key => $value) {
            Setting::updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value],
            );
        }

        Cache::forget(self::CACHE_KEY);
    }
}
