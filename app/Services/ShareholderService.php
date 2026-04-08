<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Shareholder;
use App\Repositories\Contracts\ShareholderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

    /**
     * @return Collection<int, object{property: Property, percentage: float, allocation: array<string, mixed>}>
     */
    public function propertyParticipationsFor(Shareholder $shareholder): Collection
    {
        $id = (int) $shareholder->id;
        $name = (string) $shareholder->name;

        return Property::query()
            ->with('area:id,name')
            ->whereNotNull('shareholder_allocations')
            ->orderBy('name')
            ->get()
            ->map(function (Property $property) use ($id, $name) {
                $rows = collect($property->shareholder_allocations ?? []);
                $match = $rows->first(function (array $row) use ($id, $name): bool {
                    if (isset($row['shareholder_id']) && (int) $row['shareholder_id'] === $id) {
                        return true;
                    }
                    $rowName = $row['shareholder_name'] ?? null;

                    return $rowName !== null && $rowName === $name;
                });

                if ($match === null) {
                    return null;
                }

                return (object) [
                    'property' => $property,
                    'percentage' => (float) ($match['percentage'] ?? 0),
                    'allocation' => $match,
                ];
            })
            ->filter()
            ->values();
    }
}
