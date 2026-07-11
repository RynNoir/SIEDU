@props(['header' => null])

@php
    $navItems = [
        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'pattern' => 'admin.dashboard', 'icon' => 'dashboard'],
        ['route' => 'admin.study-programs.index', 'label' => 'Program Studi', 'pattern' => 'admin.study-programs.*', 'icon' => 'study-program'],
        ['route' => 'admin.class-groups.index', 'label' => 'Kelas', 'pattern' => 'admin.class-groups.*', 'icon' => 'class-group'],
        ['route' => 'admin.courses.index', 'label' => 'Mata Kuliah', 'pattern' => 'admin.courses.*', 'icon' => 'course'],
        ['route' => 'admin.lecturers.index', 'label' => 'Dosen', 'pattern' => 'admin.lecturers.*', 'icon' => 'lecturer'],
        ['route' => 'admin.students.index', 'label' => 'Mahasiswa', 'pattern' => 'admin.students.*', 'icon' => 'student'],
        ['route' => 'admin.evaluation-periods.index', 'label' => 'Periode Evaluasi', 'pattern' => 'admin.evaluation-periods.*', 'icon' => 'period'],
        ['route' => 'admin.evaluation-questions.index', 'label' => 'Pertanyaan', 'pattern' => 'admin.evaluation-questions.*', 'icon' => 'question'],
        ['route' => 'admin.course-class-assignments.index', 'label' => 'Penugasan Dosen', 'pattern' => 'admin.course-class-assignments.*', 'icon' => 'assignment'],
        ['route' => 'admin.class-promotion.index', 'label' => 'Promosi Kelas', 'pattern' => 'admin.class-promotion.*', 'icon' => 'promotion'],
    ];
@endphp

<x-app-shell title-suffix="Admin" role-label="Admin" :nav-items="$navItems"
    :home-route="route('admin.dashboard')" :header="$header">
    {{ $slot }}
</x-app-shell>
