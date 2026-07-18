<?php

namespace App\Services;

use App\Models\LandParcel;
use App\Models\LandParcelShareholder;
use App\Models\Project;
use App\Models\ProjectShareholder;
use App\Models\Property;
use App\Models\Shareholder;
use App\Repositories\Contracts\ShareholderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ShareholderService
{
    public function __construct(private readonly ShareholderRepositoryInterface $shareholders) {}

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
        return $this->shareholders->create(['name' => $data['name']]);
    }

    public function update(Shareholder $shareholder, array $data): Shareholder
    {
        return $this->shareholders->update($shareholder, ['name' => $data['name']]);
    }

    public function delete(Shareholder $shareholder): bool
    {
        return $this->shareholders->delete($shareholder);
    }

    /**
     * يربط المساهم بمشروع (أو يحدّث التمويل/النسبة).
     */
    public function attachToProject(Shareholder $shareholder, Project $project, float $investment): ProjectShareholder
    {
        $percentage = $project->shareholderPercentageForInvestment($investment);

        return ProjectShareholder::query()->updateOrCreate(
            [
                'shareholder_id' => (int) $shareholder->id,
                'project_id' => (int) $project->id,
            ],
            [
                'total_investment' => round($investment, 2),
                'share_percentage' => $percentage,
            ]
        );
    }

    /**
     * يربط المساهم بأرض بيع/شراء.
     */
    public function attachToLandParcel(Shareholder $shareholder, LandParcel $parcel, float $investment): LandParcelShareholder
    {
        $percentage = $parcel->shareholderPercentageForInvestment($investment);

        return LandParcelShareholder::query()->updateOrCreate(
            [
                'shareholder_id' => (int) $shareholder->id,
                'land_parcel_id' => (int) $parcel->id,
            ],
            [
                'total_investment' => round($investment, 2),
                'share_percentage' => $percentage,
            ]
        );
    }

    /**
     * @return Collection<int, object{property: Property, percentage: float, allocation: array<string, mixed>}>
     */
    public function propertyParticipationsFor(Shareholder $shareholder, ?int $projectId = null): Collection
    {
        $id = (int) $shareholder->id;
        $name = (string) $shareholder->name;
        $projectIds = $projectId !== null
            ? collect([$projectId])
            : $shareholder->projectMemberships()->pluck('project_id');

        if ($projectIds->isEmpty()) {
            return collect();
        }

        return Property::withoutProjectScope()
            ->with(['area:id,name', 'project:id,name'])
            ->whereIn('project_id', $projectIds)
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
