<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class OrderItem extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'order_items'; // optional (Laravel auto-detects)

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'meal_id',
        'quantity',
        'price_at_order_time',
        'subtotal'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

    protected $attributes = [];

    protected $appends = ['meal_name'];

    // Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }

    public function getMealNameAttribute()
    {
        return $this->meal ? $this->meal->name : '';
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

    public function toArray()
    {
        $array = parent::toArray();
        return [
            'id'                    => $array['id'],
            'order_id'              => $array['order_id'],
            'meal_id'               => $array['meal_id'],
            'meal_name'             => $array['meal_name'],
            'quantity'              => $array['quantity'],
            'price_at_order_time'   => $array['price_at_order_time'],
            'subtotal'              => $array['subtotal'],
            'created_at'            => $array['created_at'],
            'updated_at'            => $array['updated_at'],
            'deleted_at'            => $array['deleted_at'],
        ];
    }
}
