<?php

namespace App\Models;

class UnitInventoryItem extends BaseModel
{
    protected $fillable = ['property_id', 'room', 'name', 'required', 'present', 'damaged', 'replacement_cost', 'version'];

    protected $casts = ['required' => 'integer', 'present' => 'integer', 'damaged' => 'integer', 'version' => 'integer', 'replacement_cost' => 'decimal:2'];
}
