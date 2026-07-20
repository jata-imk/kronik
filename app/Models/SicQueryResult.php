<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SicQueryResult extends Model
{
    use HasFactory;

    protected $table = 'sic_query_results';
    protected $fillable = ['sic_query_id', 'tipo_registro', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function sicQuery()
    {
        return $this->belongsTo(SicQuery::class, 'sic_query_id');
    }
}
