<?php

namespace App\Repositories\Contracts;

use App\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PropertyRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(int $id): Property;

    public function create(array $data): Property;

    public function update(Property $property, array $data): Property;

    public function delete(Property $property): bool;
}
