<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SettingController extends Controller implements HasMiddleware
{
    private const PREFIX_RULE = ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9\-_\/]*$/'];

    public function __construct(private SettingsService $settings) {}

    public static function middleware(): array
    {
        return [new Middleware('can:settings.manage')];
    }

    public function general(): View
    {
        return view('settings.general', ['settings' => $this->settings->all()]);
    }

    /**
     * Pricing switches only affect documents created afterwards: each sale
     * and purchase stores its own prices_include_tax snapshot.
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:1000'],
            'business_tax_number' => ['nullable', 'string', 'max:50'],
            'currency_code' => ['required', 'string', 'size:3', 'alpha'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'date_format' => ['required', 'in:d-m-Y,d/m/Y,Y-m-d,m/d/Y'],
            'prices_include_tax' => ['required', 'boolean'],
            'allow_negative_stock' => ['required', 'boolean'],
            'purchase_prices_include_tax' => ['required', 'boolean'],
            'update_cost_on_purchase' => ['required', 'boolean'],
        ]);

        $data['currency_code'] = strtoupper($data['currency_code']);
        $data = array_map(fn ($v) => $v ?? '', $data);

        foreach (['general', 'sales', 'purchases'] as $group) {
            $this->settings->setMany($group, $data);
        }

        return redirect()->route('settings.general')->with('success', 'Settings saved.');
    }

    public function invoice(): View
    {
        return view('settings.invoice', ['settings' => $this->settings->group('invoice')]);
    }

    public function updateInvoice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'invoice_prefix' => self::PREFIX_RULE,
            'purchase_prefix' => self::PREFIX_RULE,
            'sale_return_prefix' => self::PREFIX_RULE,
            'purchase_return_prefix' => self::PREFIX_RULE,
            'payment_prefix' => self::PREFIX_RULE,
            'expense_prefix' => self::PREFIX_RULE,
            'adjustment_prefix' => self::PREFIX_RULE,
            'delivery_prefix' => self::PREFIX_RULE,
            'invoice_footer' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->settings->setMany('invoice', array_map(fn ($v) => $v ?? '', $data));

        return redirect()->route('settings.invoice')->with('success', 'Invoice settings saved.');
    }
}
