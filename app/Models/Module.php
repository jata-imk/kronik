<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules';
    protected $fillable = [
        'name',
        'icon',
        'route_name',
        'parent_id'
    ];

    public function parent()
    {
        return $this->belongsTo(Module::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Module::class, 'parent_id');
    }

    public function menubarItems()
    {
        return $this->belongsToMany(
            MenubarItem::class,
            'menubar_item_module',
            'module_id',
            'menubar_item_id'
        )->withPivot('routes')
            ->as('menubarItemModule')
            ->using(MenubarItemModule::class)
            ->withTimestamps();;
    }

    public function menubarItemModules()
    {
        return $this->hasMany(Module::class);
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }
}
