@extends('layouts.app', ['breadcrumbs' => ['Finance' => null, 'Cash register' => null]])

@section('title', 'Cash register')

@section('content')
    @if ($current)
        <div class="card mb-4"><div class="card-body">
            <h2 class="h6">Open register</h2>
            <dl class="row small">
                <dt class="col-sm-3">Opened</dt><dd class="col-sm-9">{{ $current->opened_at->format('d-m-Y H:i') }}</dd>
                <dt class="col-sm-3">Opening float</dt><dd class="col-sm-9">{{ money($current->opening_balance) }}</dd>
                <dt class="col-sm-3">Expected cash now</dt><dd class="col-sm-9">{{ money($expected) }}</dd>
            </dl>

            @can('close', $current)
                <form method="POST" action="{{ route('finance.cash-register.close', $current) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4"><x-form.input name="closing_balance" label="Counted cash" type="number" step="0.01" min="0" required /></div>
                    <div class="col-md-6"><x-form.input name="notes" label="Notes" maxlength="1000" /></div>
                    <div class="col-md-2 mb-3"><button type="submit" class="btn btn-warning w-100" data-confirm="Close this register?">Close</button></div>
                </form>
            @endcan
        </div></div>
    @else
        @can('open', \App\Models\CashRegister::class)
            <form method="POST" action="{{ route('finance.cash-register.open') }}" class="row g-2 align-items-end mb-4">
                @csrf
                <div class="col-md-4"><x-form.input name="opening_balance" label="Opening float" type="number" step="0.01" min="0" value="0" required /></div>
                <div class="col-md-6"><x-form.input name="notes" label="Notes" maxlength="1000" /></div>
                <div class="col-md-2 mb-3"><button type="submit" class="btn btn-primary w-100">Open register</button></div>
            </form>
        @endcan
    @endif

    <h2 class="h6">Recent registers</h2>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Opened</th><th>Closed</th><th>Cashier</th><th>Branch</th><th class="text-end">Opening</th><th class="text-end">Expected</th><th class="text-end">Counted</th><th class="text-end">Difference</th></tr></thead>
            <tbody>
                @forelse ($registers as $register)
                    <tr>
                        <td>{{ $register->opened_at->format('d-m-Y H:i') }}</td>
                        <td>{{ $register->closed_at?->format('d-m-Y H:i') ?? 'Open' }}</td>
                        <td>{{ $register->user->name }}</td>
                        <td>{{ $register->branch->name }}</td>
                        <td class="text-end">{{ money($register->opening_balance) }}</td>
                        <td class="text-end">{{ $register->expected_balance !== null ? money($register->expected_balance) : '—' }}</td>
                        <td class="text-end">{{ $register->closing_balance !== null ? money($register->closing_balance) : '—' }}</td>
                        <td class="text-end">
                            {{ $register->closing_balance !== null ? money(\App\Support\Money::round(\App\Support\Money::sub($register->closing_balance, $register->expected_balance))) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No registers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $registers->links() }}
@endsection
