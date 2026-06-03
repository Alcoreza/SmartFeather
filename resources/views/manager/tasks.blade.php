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
                    <div class="manager-task-chip pending">Pending</div>

                    <div class="manager-task-header-actions">
                        <div class="manager-task-filter-row">
                            <div class="manager-task-filter-wrap">
                                <select id="taskHouseFilter-pending" class="manager-task-filter-select" data-task-section="pending" data-filter-type="house">
                                    <option value="All houses">All houses</option>
                                </select>
                            </div>
                        </div>

                        <div class="manager-task-filter-row">
                            <div class="manager-task-filter-wrap">
                                <select id="taskPriorityFilter-pending" class="manager-task-filter-select" data-task-section="pending" data-filter-type="priority">
                                    <option value="All priority">All priority</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="manager-task-add-btn" id="openAddTaskModal" aria-label="Add task">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                            <path d="M12 5v14"></path>
                            <path d="M5 12h14"></path>
                        </svg>
                    </button>
                </div>


                <div class="manager-task-card">
                    <div class="manager-task-table-wrap">
                        <table class="manager-task-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Task<br>Assigned</th>
                                    <th>House<br>Number</th>
                                    <th>Pen<br>Number</th>
                                    <th>Detailed<br>Task</th>
                                    <th>Priority</th>
                                    <th>Time<br>Assigned</th>
                                    <th>Finish<br>By</th>
                                </tr>
                            </thead>

                            <tbody id="pendingTasksTable"></tbody>
                        </table>
                    </div>

                    <div class="manager-task-row-pagination" data-task-pagination="pending">
                        <button type="button" class="manager-task-page-btn" data-task-row-prev="pending">
                            Previous
                        </button>
                        <div class="manager-task-page-dots" data-task-row-dots="pending"></div>
                        <button type="button" class="manager-task-page-btn" data-task-row-next="pending">
                            Next
                        </button>
                    </div>
                </div>
            </section>

            <section class="manager-task-section">
                <div class="manager-task-section-head">
                    <div class="manager-task-chip approval">For Approval</div>

                    <div class="manager-task-header-actions">
                        <div class="manager-task-filter-row">
                            <div class="manager-task-filter-wrap">
                                <select id="taskHouseFilter-for_approval" class="manager-task-filter-select" data-task-section="for_approval" data-filter-type="house">
                                    <option value="All houses">All houses</option>
                                </select>
                            </div>
                        </div>

                        <div class="manager-task-filter-row">
                            <div class="manager-task-filter-wrap">
                                <select id="taskPriorityFilter-for_approval" class="manager-task-filter-select" data-task-section="for_approval" data-filter-type="priority">
                                    <option value="All priority">All priority</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="manager-task-card">
                    <div class="manager-task-table-wrap">
                        <table class="manager-task-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Task<br>Assigned</th>
                                    <th>House<br>Number</th>
                                    <th>Pen<br>Number</th>
                                    <th>Detailed<br>Task</th>
                                    <th>Photo</th>
                                    <th>Priority</th>
                                    <th>Time<br>Assigned</th>
                                    <th>Finish<br>By</th>
                                    <th>Mark</th>
                                </tr>
                            </thead>

                            <tbody id="approvalTasksTable"></tbody>
                        </table>
                    </div>

                    <div class="manager-task-row-pagination" data-task-pagination="for_approval">
                        <button type="button" class="manager-task-page-btn" data-task-row-prev="for_approval">
                            Previous
                        </button>
                        <div class="manager-task-page-dots" data-task-row-dots="for_approval"></div>
                        <button type="button" class="manager-task-page-btn" data-task-row-next="for_approval">
                            Next
                        </button>
                    </div>
                </div>
            </section>

            <section class="manager-task-section">
                <div class="manager-task-section-head">
                    <div class="manager-task-chip completed">Completed</div>

                    <div class="manager-task-header-actions">
                        <div class="manager-task-filter-row">
                            <div class="manager-task-filter-wrap">
                                <select id="taskHouseFilter-completed" class="manager-task-filter-select" data-task-section="completed" data-filter-type="house">
                                    <option value="All houses">All houses</option>
                                </select>
                            </div>
                        </div>

                        <div class="manager-task-filter-row">
                            <div class="manager-task-filter-wrap">
                                <select id="taskPriorityFilter-completed" class="manager-task-filter-select" data-task-section="completed" data-filter-type="priority">
                                    <option value="All priority">All priority</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="manager-task-card">

                    <div class="manager-task-table-wrap">
                        <table class="manager-task-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Task<br>Assigned</th>
                                    <th>House<br>Number</th>
                                    <th>Pen<br>Number</th>
                                    <th>Detailed<br>Task</th>
                                    <th>Photo</th>
                                    <th>Priority</th>
                                    <th>Notes</th>
                                    <th>Time<br>Assigned</th>
                                    <th>Finish<br>By</th>
                                    <th>Time<br>Completed</th>
                                </tr>
                            </thead>

                            <tbody id="completedTasksTable"></tbody>
                        </table>
                    </div>

                    <div class="manager-task-row-pagination" data-task-pagination="completed">
                        <button type="button" class="manager-task-page-btn" data-task-row-prev="completed">
                            Previous
                        </button>
                        <div class="manager-task-page-dots" data-task-row-dots="completed"></div>
                        <button type="button" class="manager-task-page-btn" data-task-row-next="completed">
                            Next
                        </button>
                    </div>
                </div>
            </section>
        </main>
    </div>
@endsection
