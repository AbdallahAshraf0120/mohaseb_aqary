<?php

namespace App\Services;

use App\Models\Shareholder;
use App\Repositories\Contracts\ShareholderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ShareholderService
{
    public function __construct(private readonly ShareholderRepositoryInterface $shareholders)
    {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->shareholders->paginate($perPage);
    }

    public function findOrFail(int $id): Shareholder
    {
        return $this->shareholders->findOrFail($id);
    }

    public function create(array $data): Shareholder
    {
        return $this->shareholders->create($data);
    }

    public function update(Shareholder $shareholder, array $data): Shareholder
    {
        return $this->shareholders->update($shareholder, $data);
    }

    public function delete(Shareholder $shareholder): bool
    {
        return $this->shareholders->delete($shareholder);
    }
}
