<?php

namespace App\Services;

use App\Models\Property;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PropertyService
{
    public function __construct(private readonly PropertyRepositoryInterface $properties)
    {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->properties->paginate($perPage);
    }

    public function findOrFail(int $id): Property
    {
        return $this->properties->findOrFail($id);
    }

    public function create(array $data): Property
    {
        return $this->properties->create($data);
    }

    public function update(Property $property, array $data): Property
    {
        return $this->properties->update($property, $data);
    }

    public function delete(Property $property): bool
    {
        return $this->properties->delete($property);
    }
}
