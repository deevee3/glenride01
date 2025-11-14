<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Edge extends Model
{
    /** @use HasFactory<\Database\Factories\EdgeFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'origin_node_id',
        'destination_node_id',
        'avg_lead_time_days',
        'lead_time_std_days',
        'volume',
        'cost_per_unit',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'origin_node_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'destination_node_id');
    }
}
