@php $editing = isset($assignment) && $assignment->exists; @endphp

<form method="POST"
    action="{{ $editing ? route('admin.course-class-assignments.update', $assignment) : route('admin.course-class-assignments.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="space-y-4">
        <div>
            <x-input-label for="course_id" :value="'Mata Kuliah'" />
            <x-select id="course_id" name="course_id" class="mt-1" required>
                <option value="">— pilih mata kuliah —</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}"
                        @selected((int) old('course_id', $assignment->course_id ?? 0) === $course->id)>
                        {{ $course->code }} — {{ $course->name }} ({{ $course->studyProgram->code }}, sem {{ $course->semester }})
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('course_id')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="lecturer_id" :value="'Dosen'" />
            <x-select id="lecturer_id" name="lecturer_id" class="mt-1" required>
                <option value="">— pilih dosen —</option>
                @foreach ($lecturers as $lecturer)
                    <option value="{{ $lecturer->id }}"
                        @selected((int) old('lecturer_id', $assignment->lecturer_id ?? 0) === $lecturer->id)>
                        {{ $lecturer->name }} ({{ $lecturer->nip }})
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('lecturer_id')" class="mt-1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="class_group_id" :value="'Kelas'" />
                <x-select id="class_group_id" name="class_group_id" class="mt-1" required>
                    <option value="">— pilih kelas —</option>
                    @foreach ($classGroups as $class)
                        <option value="{{ $class->id }}"
                            @selected((int) old('class_group_id', $assignment->class_group_id ?? 0) === $class->id)>
                            {{ $class->class_code }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('class_group_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="evaluation_period_id" :value="'Periode'" />
                <x-select id="evaluation_period_id" name="evaluation_period_id" class="mt-1" required>
                    <option value="">— pilih periode —</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}"
                            @selected((int) old('evaluation_period_id', $assignment->evaluation_period_id ?? 0) === $period->id)>
                            {{ $period->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('evaluation_period_id')" class="mt-1" />
            </div>
        </div>

        <p class="text-xs text-muted">Team teaching: untuk 2 dosen pada MK+kelas yang sama, buat penugasan terpisah dengan dosen berbeda.</p>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Penugasan' }}</x-button>
        <x-button variant="secondary" :href="route('admin.course-class-assignments.index')">Batal</x-button>
    </div>
</form>