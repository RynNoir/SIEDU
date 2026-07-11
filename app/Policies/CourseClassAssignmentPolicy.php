<?php

namespace App\Policies;

use App\Models\CourseClassAssignment;
use App\Models\User;

class CourseClassAssignmentPolicy
{
    /**
     * Dosen hanya boleh melihat assignment miliknya sendiri (PRD §6.4).
     */
    public function view(User $user, CourseClassAssignment $assignment): bool
    {
        if ($user->isLecturer()) {
            return $assignment->lecturer_id === $user->lecturer?->id;
        }

        if ($user->isKaprodi()) {
            return $assignment->classGroup->study_program_id === $user->study_program_id;
        }

        return false;
    }
}
