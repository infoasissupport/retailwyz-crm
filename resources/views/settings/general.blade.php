@extends('layouts.app', ['breadcrumbs' => ['Settings' => null, 'General' => null]])

@section('title', 'Settings')

@section('content')
    @include('settings.partials.nav')

    <form method="POST" action="{{ route('settings.general.update') }}">
        @csrf
        @method('PUT')

        <fieldset class="mb-3">
            <legend class="h6">Business</legend>
            <div class="row">
                <div class="col-md-6"><x-form.input name="business_name" label="Business name" :value="$settings['business_name']" required maxlength="255" /></div>
                <div class="col-md-3"><x-form.input name="business_phone" label="Phone" :value="$settings['business_phone']" maxlength="30" /></div>
                <div class="col-md-3"><x-form.input name="business_email" label="Email" type="email" :value="$settings['business_email']" /></div>
                <div class="col-md-6"><x-form.textarea name="business_address" label="Address" :value="$settings['business_address']" /></div>
                <div class="col-md-6"><x-form.input name="business_tax_number" label="Tax number (GSTIN)" :value="$settings['business_tax_number']" maxlength="50" /></div>
                <div class="col-md-3"><x-form.input name="currency_code" label="Currency code" :value="$settings['currency_code']" required maxlength="3" /></div>
                <div class="col-md-3"><x-form.input name="currency_symbol" label="Currency symbol" :value="$settings['currency_symbol']" required maxlength="5" /></div>
                <div class="col-md-3">
                    <x-form.select name="date_format" label="Date format" :value="$settings['date_format']" required
                                   :options="['d-m-Y' => 'DD-MM-YYYY', 'd/m/Y' => 'DD/MM/YYYY', 'Y-m-d' => 'YYYY-MM-DD', 'm/d/Y' => 'MM/DD/YYYY']" />
                </div>
            </div>
        </fieldset>

        <fieldset class="mb-3">
            <legend class="h6">Sales</legend>
            <p class="small text-muted">Changes apply to new sales only; existing invoices keep the pricing mode they were created with.</p>
            <x-form.checkbox name="prices_include_tax" label="Selling prices include tax" :checked="filter_var($settings['prices_include_tax'], FILTER_VALIDATE_BOOLEAN)" />
            <x-form.checkbox name="allow_negative_stock" label="Allow selling below zero stock" :checked="filter_var($settings['allow_negative_stock'], FILTER_VALIDATE_BOOLEAN)" />
        </fieldset>

        <fieldset class="mb-3">
            <legend class="h6">Purchases</legend>
            <x-form.checkbox name="purchase_prices_include_tax" label="Purchase costs include tax" :checked="filter_var($settings['purchase_prices_include_tax'], FILTER_VALIDATE_BOOLEAN)" />
            <x-form.checkbox name="update_cost_on_purchase" label="Update product cost price when goods are received" :checked="filter_var($settings['update_cost_on_purchase'], FILTER_VALIDATE_BOOLEAN)" />
        </fieldset>

        <button type="submit" class="btn btn-primary">Save settings</button>
    </form>
@endsection
