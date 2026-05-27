<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Order extends Model
{
    use SoftDeletes, LogsActivity;

    const STATUS_PLACED     = 0;
    const STATUS_CANCELLED  = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_IN_ROUTE   = 3;
    const STATUS_DELIVERED  = 4;
    const STATUS_RECEIVED   = 5;

    protected $table = 'orders'; // optional (Laravel auto-detects)

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'restaurant_id',
        'coupon_id',
        'tip_amount',
        'subtotal',
        'discount_amount',
        'total_amount',
        'status',
        'ordered_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [];

    protected $attributes = [];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderStatusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
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
}
