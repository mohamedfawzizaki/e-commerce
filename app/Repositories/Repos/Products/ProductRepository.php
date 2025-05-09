<?php

namespace App\Repositories\Repos\Products;

use App\Models\Products\Product;
use App\Repositories\RepositoryPropertiesInterface;
use App\Repositories\EloquentBased\MainBaseRepository;

class ProductRepository extends MainBaseRepository implements RepositoryPropertiesInterface
{
    // public array $relationships = ['category', 'brand'];
    public array $relationships = [];
    public array $relationshipMap = [
        // 'category_id' => 'category',
        // 'brand_id' => 'brand',
    ];
    public bool $hasPivot = false;
    public array $pivotWith = [];
    public array $defaultIDsForPivot = [];
    public array $fillable = [
        'vendor_id',
        'brand_id',
        'category_id',
        'name',
        'slug',
        'description',
        'base_price',
        'base_compare_price',
        'status',
        'currency_code'
    ];
    public array $availableColumns = [
        'id',
        'vendor_id',
        'brand_id',
        'category_id',
        'name',
        'slug',
        'description',
        'base_price',
        'base_compare_price',
        'status',
        'currency_code'
    ];
    public array $availableConditionColumns = [
        'id',
        'vendor_id',
        'brand_id',
        'category_id',
        'name',
        'slug',
        'description',
        'base_price',
        'base_compare_price',
        'status',
        'currency_code'
    ];
    public array $availableColumnsForMassUpdate = [
        // 'vendor_id',
        'brand_id',
        'category_id',
        'description',
        'base_price',
        'base_compare_price',
        'status',
        'currency_code'
    ];

    /**
     * ProductRepository constructor.
     *
     * @param Product $product The Product model instance.
     */
    public function __construct(Product $product)
    {
        parent::__construct($product);
    }

    public function getRelationships(): array
    {
        return $this->relationships;
    }

    public function getRelationshipMap(): array
    {
        return $this->relationshipMap;
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function getAvailableColumns(): array
    {
        return $this->availableColumns;
    }

    public function getAvailableConditionColumns(): array
    {
        return $this->availableConditionColumns;
    }

    public function getAvailableColumnsForMassUpdate(): array
    {
        return $this->availableColumnsForMassUpdate;
    }

    public function hasPivot(): bool
    {
        return $this->hasPivot;
    }
    public function getPivotWith(): array
    {
        return $this->pivotWith;
    }
    public function getDefualtIDsForPivot(): array
    {
        return $this->defaultIDsForPivot;
    }
}