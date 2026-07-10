<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'must_change_password', 'study_program_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * @return HasOne<Lecturer, $this>
     */
    public function lecturer(): HasOne
    {
        return $this->hasOne(Lecturer::class);
    }

    /**
     * @return HasOne<Student, $this>
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Diisi hanya saat role = kaprodi (PRD §3, revisi v1.2).
     *
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isLecturer(): bool
    {
        return $this->role === Role::Lecturer;
    }

    public function isStudent(): bool
    {
        return $this->role === Role::Student;
    }

    public function isKaprodi(): bool
    {
        return $this->role === Role::Kaprodi;
    }

    /**
     * Nama route dashboard sesuai role (landing pasca-login).
     */
    public function dashboardRoute(): string
    {
        return match ($this->role) {
            Role::Admin => 'admin.dashboard',
            Role::Lecturer => 'lecturer.dashboard',
            Role::Student => 'student.dashboard',
            Role::Kaprodi => 'kaprodi.dashboard',
        };
    }
}
