@extends('layouts.app')

@section('title', 'School Dashboard')

@section('content')
<div class="card">
    <h2>School Dashboard</h2>
    <p style="color: #7f8c8d;">Welcome, {{ auth()->user()->school->name_en }}</p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
    <div class="card">
        <h3>School Information</h3>
        <p><strong>Name (EN):</strong> {{ $school->name_en }}</p>
        <p><strong>Name (AR):</strong> {{ $school->name_ar }}</p>
        <p><strong>Created:</strong> {{ $school->created_at->format('Y-m-d') }}</p>
    </div>

    <div class="card">
        <h3>Statistics</h3>
        <p><strong>Total Students:</strong> {{ $studentsCount }}</p>
        <p><strong>Available Subjects:</strong> {{ $materialsCount }}</p>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <h3>Available Subjects</h3>
    <table>
        <thead>
            <tr>
                <th>Subject (EN)</th>
                <th>Subject (AR)</th>
                <th>Sections</th>
            </tr>
        </thead>
        <tbody>
            @forelse($materials as $material)
                <tr>
                    <td>{{ $material->name_en }}</td>
                    <td>{{ $material->name_ar }}</td>
                    <td>{{ $material->sections_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">No subjects available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="card" style="margin-top: 20px;">
    <h3>Students</h3>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Joined</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr>
                    <td>{{ $student->username }}</td>
                    <td>{{ $student->full_name ?? 'N/A' }}</td>
                    <td>{{ $student->email ?? 'N/A' }}</td>
                    <td>{{ $student->created_at->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No students found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
