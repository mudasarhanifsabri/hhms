<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Building extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'buildings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'building_name',
        'management_email',
        'security_contact',
        'gas_provider',
        'address',
        'city',
        'state',
        'country',
        'google_map_link',
        'year_built',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'year_built' => 'integer',
    ];

    /**
     * Automatically generate a UUID when creating.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Relationships
     */
    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}
