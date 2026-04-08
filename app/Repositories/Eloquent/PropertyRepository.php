<?php

namespace App\Repositories\Eloquent;

use App\Models\Property;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PropertyRepository implements PropertyRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Property::query()
            ->with('owner:id,name')
            ->latest()
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Property
    {
        return Property::query()->with('owner:id,name')->findOrFail($id);
    }

    public function create(array $data): Property
    {
        return Property::query()->create($data);
    }

    public function update(Property $property, array $data): Property
    {
        $property->update($data);

        return $property->refresh();
    }

    public function delete(Property $property): bool
    {
        return (bool) $property->delete();
    }
}
