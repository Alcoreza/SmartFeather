@extends('layouts.app')

@section('title', 'Manager Management')

@push('styles')
    @vite(['resources/css/manager-shared.css'])
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')

        <main class="manager-main">
            <div class="manager-tasks-topbar">
                <h1 class="manager-tasks-title">Management</h1>
            </div>

            <div class="manager-tasks-divider"></div>

            <section class="manager-task-card">
                <p>Management section content is ready. You can add your cards, forms, or tables here.</p>
            </section>
        </main>
    </div>
@endsection
