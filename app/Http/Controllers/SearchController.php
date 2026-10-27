<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Header search. Each section is searched only when the user may see that
 * module, and branch-owned records are limited to the user's branches.
 */
class SearchController extends Controller
{
    private const LIMIT = 8;

    public function index(Request $request): View
    {
        $user = $request->user();
        $q = $request->string('q')->trim()->limit(100, '')->toString();
        $results = [];

        if (mb_strlen($q) >= 2) {
            if ($user->can('leads.view')) {
                $results['Leads'] = Lead::query()->visibleTo($user)->search($q)->latest('id')->limit(self::LIMIT)->get()
                    ->map(fn ($l) => ['title' => $l->name, 'meta' => collect([$l->phone, $l->status->label()])->filter()->implode(' · '), 'url' => route('leads.show', $l), 'icon' => 'bi-person-lines-fill']);
            }

            if ($user->can('sales.view')) {
                $results['Orders'] = Sale::query()->visibleTo($user)
                    ->with('customer:id,name')
                    ->where(fn ($s) => $s->where('invoice_no', 'like', "%{$q}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")))
                    ->latest('id')->limit(self::LIMIT)->get()
                    ->map(fn ($s) => ['title' => $s->invoice_no, 'meta' => ($s->customer?->name ?? 'Walk-in').' · '.money($s->grand_total).' · '.$s->sale_date->format('d M Y'), 'url' => route('sales.show', $s), 'icon' => 'bi-receipt']);
            }

            if ($user->can('customers.view')) {
                $results['Customers'] = Customer::query()->search($q)->orderBy('name')->limit(self::LIMIT)->get()
                    ->map(fn ($c) => ['title' => $c->name, 'meta' => collect([$c->code, $c->phone])->filter()->implode(' · '), 'url' => route('customers.show', $c), 'icon' => 'bi-people']);
            }

            if ($user->can('products.view')) {
                $results['Products'] = Product::query()->search($q)->orderBy('name')->limit(self::LIMIT)->get()
                    ->map(fn ($p) => ['title' => $p->name, 'meta' => $p->sku.' · '.money($p->selling_price), 'url' => route('products.show', $p), 'icon' => 'bi-box-seam']);
            }

            if ($user->can('deliveries.view')) {
                $results['Deliveries'] = Delivery::query()->visibleTo($user)->search($q)->latest('id')->limit(self::LIMIT)->get()
                    ->map(fn ($d) => ['title' => $d->delivery_no, 'meta' => $d->customer_name.' · '.$d->status->label(), 'url' => route('deliveries.show', $d), 'icon' => 'bi-truck']);
            }

            $results = array_filter($results, fn ($group) => $group->isNotEmpty());
        }

        return view('search.index', ['q' => $q, 'results' => $results]);
    }
}
