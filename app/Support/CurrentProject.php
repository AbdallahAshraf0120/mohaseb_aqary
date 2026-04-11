<?php

namespace App\Support;

/**
 * المشروع النشط: من الجلسة في الويب، أو فرض معرّف لطلبات الـ API / السيدر.
 */
class CurrentProject
{
    private ?int $forcedId = null;

    public function force(?int $projectId): void
    {
        $this->forcedId = $projectId;
    }

    public function id(): ?int
    {
        if ($this->forcedId !== null) {
            return $this->forcedId;
        }

        $sid = session('current_project_id');

        return $sid !== null ? (int) $sid : null;
    }
}
