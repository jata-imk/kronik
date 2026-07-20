<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MenubarItemModule extends Pivot
{
    protected $table = 'menubar_item_module';

    protected $fillable = [
        'menubar_item_id',
        'module_id',
        'routes',
    ];

    protected $casts = [
        'routes' => 'json',
    ];

    /**
     * Relación con el item del menú.
     */
    public function menubarItem(): BelongsTo
    {
        return $this->belongsTo(MenubarItem::class);
    }

    /**
     * Relación con el módulo.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
