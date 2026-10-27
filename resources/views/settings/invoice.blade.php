@extends('layouts.app', ['breadcrumbs' => ['Settings' => route('settings.general'), 'Invoice & numbering' => null]])

@section('title', 'Invoice & numbering')

@section('content')
    @include('settings.partials.nav')

    <form method="POST" action="{{ route('settings.invoice.update') }}">
        @csrf
        @method('PUT')

        <p class="small text-muted">Prefixes apply to newly numbered documents. Sequence numbers continue from where they are.</p>

        <div class="row">
            @foreach ([
                'invoice_prefix' => 'Sales invoice prefix',
                'sale_return_prefix' => 'Sales return prefix',
                'purchase_prefix' => 'Purchase prefix',
                'purchase_return_prefix' => 'Purchase return prefix',
                'payment_prefix' => 'Payment prefix',
                'expense_prefix' => 'Expense prefix',
                'adjustment_prefix' => 'Stock adjustment prefix',
                'delivery_prefix' => 'Delivery prefix',
            ] as $key => $label)
                <div class="col-md-3"><x-form.input :name="$key" :label="$label" :value="$settings[$key]" maxlength="10" /></div>
            @endforeach
            <div class="col-12"><x-form.textarea name="invoice_footer" label="Invoice footer" :value="$settings['invoice_footer']" /></div>
        </div>

        <button type="submit" class="btn btn-primary">Save invoice settings</button>
    </form>
@endsection
