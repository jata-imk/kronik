<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenubarItem extends Model
{
    protected $table = 'menubar_items';
    protected $fillable = [
        'module_id',
        'label',
        'icon',
        'type',
        'value',
        'params',
        'parent_id',
        'sort_order',
    ];

    protected $casts = [
        'params' => 'json',
    ];

    public function children()
    {
        return $this->hasMany(MenubarItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function parent()
    {
        return $this->belongsTo(MenubarItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function modules()
    {
        return $this->belongsToMany(
            Module::class,
            'menubar_item_module',
            'menubar_item_id',
            'module_id'
        )->withPivot('routes')
            ->as('menubarItemModule')
            ->using(MenubarItemModule::class)
            ->withTimestamps();
    }

    public function menubarItemModules()
    {
        return $this->hasMany(MenubarItemModule::class);
    }
}
