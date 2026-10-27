<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Key/value business settings. Read through App\Services\SettingsService,
 * which caches values and supplies defaults.
 */
class Setting extends Model
{
    use Auditable;

    protected $fillable = ['group', 'key', 'value'];
}
