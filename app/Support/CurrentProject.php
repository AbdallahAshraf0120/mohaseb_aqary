<?php

namespace App\Support;

/**
 * المشروع النشط: من الجلسة في الويب، أو فرض معرّف لطلبات الـ API / السيدر.
 */
class CurrentProject
{
    private bool $forced = false;

    private ?int $forcedId = null;

    public function force(?int $projectId): void
    {
        $this->forced = true;
        $this->forcedId = $projectId;
    }

    public function clearForce(): void
    {
        $this->forced = false;
        $this->forcedId = null;
    }

    public function id(): ?int
    {
        if ($this->forced) {
            return $this->forcedId;
        }

        $sid = session('current_project_id');

        return $sid !== null ? (int) $sid : null;
    }
}
