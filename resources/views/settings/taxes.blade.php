@extends('layouts.app', ['breadcrumbs' => ['Settings' => route('settings.general'), 'Taxes' => null]])

@section('title', 'Taxes')

@section('content')
    @include('settings.partials.nav')

    <p class="small text-muted">Rate changes apply to new sales and purchases; existing documents keep the rate they were created with.</p>

    <h2 class="h6">Add tax</h2>
    <form method="POST" action="{{ route('settings.taxes.store') }}" class="row g-2 mb-4">
        @csrf
        <div class="col-md-4"><input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Name e.g. GST 18%" required maxlength="100"></div>
        <div class="col-md-2"><input type="text" name="code" value="{{ old('code') }}" class="form-control" placeholder="Code" maxlength="30"></div>
        <div class="col-md-2"><input type="number" name="rate" value="{{ old('rate') }}" class="form-control" placeholder="Rate %" step="0.001" min="0" max="100" required></div>
        <input type="hidden" name="is_active" value="1">
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Add</button></div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>Name / code / rate %</th><th class="text-end">Products</th><th></th></tr></thead>
            <tbody>
                @forelse ($taxes as $tax)
                    <tr>
                        <td>
                            <form method="POST" action="{{ route('settings.taxes.update', $tax) }}" id="tax-{{ $tax->id }}" class="row g-1">
                                @csrf
                                @method('PUT')
                                <div class="col-md-5"><input type="text" name="name" value="{{ $tax->name }}" class="form-control form-control-sm" required maxlength="100"></div>
                                <div class="col-md-3"><input type="text" name="code" value="{{ $tax->code }}" class="form-control form-control-sm" maxlength="30"></div>
                                <div class="col-md-3"><input type="number" name="rate" value="{{ $tax->rate }}" class="form-control form-control-sm" step="0.001" min="0" max="100" required></div>
                                <div class="col-md-1">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($tax->is_active) title="Active">
                                </div>
                            </form>
                        </td>
                        <td class="text-end">{{ $tax->products_count }}</td>
                        <td class="text-end text-nowrap">
                            <button type="submit" form="tax-{{ $tax->id }}" class="btn btn-sm btn-link">Save</button>
                            <form method="POST" action="{{ route('settings.taxes.destroy', $tax) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger" data-confirm="Delete this tax?">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted">No taxes defined.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
