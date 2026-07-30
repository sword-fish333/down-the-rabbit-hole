<?php

namespace App\Services\Admin;

use App\Models\LearningMode;
use App\Services\ValidationService;

/**
 * Business rules for the learning-mode CRUD. HTTP-free: the controller
 * validates input, calls one of these, and maps the ValidationService result.
 */
class LearningModeService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ValidationService
    {
        $validation = new ValidationService;

        $mode = LearningMode::create($data);
        $this->syncDefault($mode, (bool) ($data['is_default'] ?? false));

        return $validation->addValidatedItems(['mode' => $mode]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(LearningMode $mode, array $data): ValidationService
    {
        $validation = new ValidationService;

        $mode->update($data);
        $this->syncDefault($mode, (bool) ($data['is_default'] ?? false));

        return $validation->addValidatedItems(['mode' => $mode->refresh()]);
    }

    /**
     * Modes are referenced by holes, so deletion is guarded: an in-use mode is
     * disabled instead, which stops new descents from picking it without
     * orphaning the ones already running.
     */
    public function delete(LearningMode $mode): ValidationService
    {
        $validation = new ValidationService;

        if ($mode->conversations()->exists()) {
            return $validation->errorEncountered(__('admin/backend.learning-modes.in-use'));
        }

        $mode->delete();

        return $validation->successfulCheck();
    }

    public function toggle(LearningMode $mode): ValidationService
    {
        $validation = new ValidationService;

        if ($mode->enabled && $mode->is_default) {
            return $validation->errorEncountered(__('admin/backend.learning-modes.cannot-disable-default'));
        }

        $mode->update(['enabled' => ! $mode->enabled]);

        return $validation->addValidatedItems(['mode' => $mode]);
    }

    /**
     * Exactly one mode is preselected for new holes at any time.
     */
    private function syncDefault(LearningMode $mode, bool $wantsDefault): void
    {
        if ($wantsDefault) {
            $mode->promoteToDefault();
        }
    }
}
