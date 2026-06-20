@extends('layouts.app')

@section('title', 'Manager Tasks')

@push('styles')
    @vite([
        'resources/css/manager-shared.css',
        'resources/css/manager-tasks.css'
    ])
@endpush

@push('scripts')
    @vite('resources/js/manager-tasks.js')
@endpush

@section('content')
    <div class="manager-shell">
        @include('includes.manager-sidebar')
        @include('includes.manager-profile-modal')
        @include('includes.manager-tasks-modals')

        <main class="manager-main">
            <div class="manager-tasks-topbar">
                <h1 class="manager-tasks-title">Tasks</h1>

                <div class="manager-tasks-topbar-actions">
                </div>
            </div>


            <div class="manager-tasks-divider"></div>

            <section class="manager-task-section">
                <div class="manager-task-section-head">
                    <div class="manager-task-status-buttons" aria-label="Task status">
                        <button type="button" class="manager-task-status-btn is-selected" data-task-status="pending">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <circle cx="12" cy="12" r="8"></circle>
                                <path d="M12 8v4l3 2"></path>
                            </svg>
                            Pending
                        </button>
                        <button type="button" class="manager-task-status-btn" data-task-status="for_approval">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M9 11l2 2 4-5"></path>
                                <path d="M21 12a9 9 0 1 1-3.3-7"></path>
                            </svg>
                            For Approval
                        </button>
                        <button type="button" class="manager-task-status-btn" data-task-status="completed">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M20 6L9 17l-5-5"></path>
                            </svg>
                            Completed
                        </button>
                    </div>

                    <div class="manager-task-header-actions">
                        <div class="manager-task-filter-row">
                            <div class="manager-task-filter-wrap">
                                <select id="taskHouseFilter" class="manager-task-filter-select" data-filter-type="house" aria-label="Filter tasks by house">
                                    <option value="All houses">All houses</option>
                                </select>
                            </div>
                        </div>

                        <div class="manager-task-filter-row">
                            <div class="manager-task-filter-wrap">
                                <select id="taskPriorityFilter" class="manager-task-filter-select" data-filter-type="priority" aria-label="Filter tasks by priority">
                                    <option value="All priority">All priority</option>
                                </select>
                            </div>

                            <button type="button" class="manager-task-add-btn" id="openAddTaskModal" aria-label="Add task">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                    <path d="M12 5v14"></path>
                                    <path d="M5 12h14"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>


                <div class="manager-task-card">
                    <div class="manager-task-table-wrap">
                        <table class="manager-task-table">
                            <thead id="managerTasksTableHead"></thead>

                            <tbody id="managerTasksTableBody"></tbody>
                        </table>
                    </div>

                    <div class="manager-task-row-pagination" data-task-pagination>
                        <button type="button" class="manager-task-page-btn" data-task-row-prev>
                            Previous
                        </button>
                        <div class="manager-task-page-dots" data-task-row-dots></div>
                        <button type="button" class="manager-task-page-btn" data-task-row-next>
                            Next
                        </button>
                    </div>
                </div>
            </section>
        </main>
    </div>
@endsection
