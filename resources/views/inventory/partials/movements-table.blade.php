{{-- Expects $movements with branch and creator loaded; product is shown when loaded. --}}
<div class="table-responsive">
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Date</th>
                @if ($movements->first()?->relationLoaded('product'))<th>Product</th>@endif
                <th>Branch</th>
                <th>Type</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Balance</th>
                <th>Notes</th>
                <th>By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($movements as $movement)
                <tr>
                    <td>{{ $movement->created_at->format('d-m-Y H:i') }}</td>
                    @if ($movement->relationLoaded('product'))<td>{{ $movement->product->name }}</td>@endif
                    <td>{{ $movement->branch->name }}</td>
                    <td>{{ $movement->type->label() }}</td>
                    <td @class(['text-end', 'text-success' => $movement->quantity > 0, 'text-danger' => $movement->quantity < 0])>{{ qty($movement->quantity) }}</td>
                    <td class="text-end">{{ qty($movement->balance_after) }}</td>
                    <td>{{ $movement->notes }}</td>
                    <td>{{ $movement->creator?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">No movements.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
