{{-- Target amount + incentive rule inputs. $target may be null (new); $idPrefix keeps ids unique. --}}
<div class="row g-2">
    <div class="col-sm-4">
        <label class="form-label small mb-1" for="{{ $idPrefix }}-amount">Sales target</label>
        <input type="number" step="0.01" min="0.01" name="target_amount" id="{{ $idPrefix }}-amount" value="{{ $target?->target_amount ?? old('target_amount') }}" class="form-control form-control-sm" required>
    </div>
    <div class="col-sm-4">
        <label class="form-label small mb-1" for="{{ $idPrefix }}-type">Incentive</label>
        <select name="incentive_type" id="{{ $idPrefix }}-type" class="form-select form-select-sm">
            <option value="percent" @selected(($target?->incentive_type ?? old('incentive_type', 'percent')) === 'percent')>% of achieved sales</option>
            <option value="fixed" @selected(($target?->incentive_type ?? old('incentive_type')) === 'fixed')>Fixed amount</option>
        </select>
    </div>
    <div class="col-sm-4">
        <label class="form-label small mb-1" for="{{ $idPrefix }}-value">Value</label>
        <input type="number" step="0.01" min="0" name="incentive_value" id="{{ $idPrefix }}-value" value="{{ $target?->incentive_value ?? old('incentive_value', '1') }}" class="form-control form-control-sm" required>
    </div>
    <div class="col-12">
        <input type="text" name="notes" value="{{ $target?->notes ?? old('notes') }}" class="form-control form-control-sm" placeholder="Note (optional)" maxlength="1000">
    </div>
</div>
