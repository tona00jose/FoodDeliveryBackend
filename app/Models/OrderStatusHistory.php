<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class OrderStatusHistory extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'order_status_histories'; // optional (Laravel auto-detects)

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'old_status',
        'new_status',
        'changed_by'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

    protected $attributes = [];

    protected $appends = ['old_status_name', 'new_status_name'];

    // Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Team {$eventName}");
    }

    public function getOldStatusNameAttribute()
    {
        return match ($this->old_status) {
            Order::STATUS_PLACED     => 'placed',
            Order::STATUS_CANCELLED  => 'cancelled',
            Order::STATUS_PROCESSING => 'processing',
            Order::STATUS_IN_ROUTE   => 'in route',
            Order::STATUS_DELIVERED  => 'delivered',
            Order::STATUS_RECEIVED   => 'received',
            Order::STATUS_REJECTED   => 'rejected',
            default                  => '',
        };
    }

    public function getNewStatusNameAttribute()
    {
        return match ($this->new_status) {
            Order::STATUS_PLACED     => 'placed',
            Order::STATUS_CANCELLED  => 'cancelled',
            Order::STATUS_PROCESSING => 'processing',
            Order::STATUS_IN_ROUTE   => 'in route',
            Order::STATUS_DELIVERED  => 'delivered',
            Order::STATUS_RECEIVED   => 'received',
            Order::STATUS_REJECTED   => 'rejected',
            default                  => '',
        };
    }

    public function toArray()
    {
        $array = parent::toArray();

        return [
            'id'               => $array['id'],
            'order_id'         => $array['order_id'],
            'old_status'       => $array['old_status'],
            'old_status_name'  => $array['old_status_name'],
            'new_status'       => $array['new_status'],
            'new_status_name'  => $array['new_status_name'],
            'changed_by'       => $array['changed_by'],
            'created_at'       => $array['created_at'],
            'updated_at'       => $array['updated_at'],
            'deleted_at'       => $array['deleted_at'],
        ];
    }
}
