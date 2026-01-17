<?php

namespace Pterodactyl\Models;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $type
 * @property float $amount
 * @property float $price
 * @property bool $enabled
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class StoreProduct extends Model
{
    /**
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'store_products';

    /**
     * Fields that can be mass assigned.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'type', 'amount', 'price', 'enabled'
    ];

    /**
     * Cast values to correct types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'price' => 'float',
        'enabled' => 'boolean',
    ];

    /**
     * Validation rules for this model.
     *
     * @var array
     */
    public static $validationRules = [
        'name' => 'required|string|max:191',
        'description' => 'nullable|string|max:191',
        'type' => 'required|string|in:cpu,memory,disk,backups,databases,slots',
        'amount' => 'required|numeric|min:0',
        'price' => 'required|numeric|min:0',
        'enabled' => 'boolean',
    ];
}
