<?php

namespace App\Repositories\Eloquent;

use App\Models\Shareholder;
use App\Repositories\Contracts\ShareholderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ShareholderRepository implements ShareholderRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Shareholder::query()
            ->latest()
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Shareholder
    {
        return Shareholder::query()->findOrFail($id);
    }

    public function create(array $data): Shareholder
    {
        return Shareholder::query()->create($data);
    }

    public function update(Shareholder $shareholder, array $data): Shareholder
    {
        $shareholder->update($data);

        return $shareholder->refresh();
    }

    public function delete(Shareholder $shareholder): bool
    {
        return (bool) $shareholder->delete();
    }
}
