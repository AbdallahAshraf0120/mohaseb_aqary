<?php

namespace App\Repositories\Contracts;

use App\Models\Shareholder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ShareholderRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(int $id): Shareholder;

    public function create(array $data): Shareholder;

    public function update(Shareholder $shareholder, array $data): Shareholder;

    public function delete(Shareholder $shareholder): bool;
}
