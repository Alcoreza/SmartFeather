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
                    <button class="manager-profile" type="button" id="openProfileModal" aria-label="Open profile">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.8-3.8 5-5.5 8-5.5S18.2 16.2 20 20"></path>
                        </svg>
                    </button>
                </div>
            </div>


            <div class="manager-tasks-divider"></div>

            <section class="manager-task-section">
                <div class="manager-task-section-head">
                    <div class="manager-task-chip pending">Pending</div>

                    <div class="manager-task-header-actions">
                        <div class="manager-task-filter-row">
                            <label class="manager-task-filter-label" for="taskHouseFilter-pending">House</label>
                            <div class="manager-task-filter-wrap">
                                <select id="taskHouseFilter-pending" class="manager-task-filter-select" data-task-section="pending" data-filter-type="house">
                                    <option value="All">All houses</option>
                                </select>
                            </div>
                        </div>

                        <div class="manager-task-filter-row">
                            <label class="manager-task-filter-label" for="taskPriorityFilter-pending">Priority</label>
                            <div class="manager-task-filter-wrap">
                                <select id="taskPriorityFilter-pending" class="manager-task-filter-select" data-task-section="pending" data-filter-type="priority">
                                    <option value="All">All priorities</option>
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
                </div>
            </section>

            <section class="manager-task-section">
                <div class="manager-task-section-head">
                    <div class="manager-task-chip approval">For Approval</div>

                    <div class="manager-task-header-actions">
                        <div class="manager-task-filter-row">
                            <label class="manager-task-filter-label" for="taskHouseFilter-for_approval">House</label>
                            <div class="manager-task-filter-wrap">
                                <select id="taskHouseFilter-for_approval" class="manager-task-filter-select" data-task-section="for_approval" data-filter-type="house">
                                    <option value="All">All houses</option>
                                </select>
                            </div>
                        </div>

                        <div class="manager-task-filter-row">
                            <label class="manager-task-filter-label" for="taskPriorityFilter-for_approval">Priority</label>
                            <div class="manager-task-filter-wrap">
                                <select id="taskPriorityFilter-for_approval" class="manager-task-filter-select" data-task-section="for_approval" data-filter-type="priority">
                                    <option value="All">All priorities</option>
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
                </div>
            </section>

            <section class="manager-task-section">
                <div class="manager-task-section-head">
                    <div class="manager-task-chip completed">Completed</div>

                    <div class="manager-task-header-actions">
                        <div class="manager-task-filter-row">
                            <label class="manager-task-filter-label" for="taskHouseFilter-completed">House</label>
                            <div class="manager-task-filter-wrap">
                                <select id="taskHouseFilter-completed" class="manager-task-filter-select" data-task-section="completed" data-filter-type="house">
                                    <option value="All">All houses</option>
                                </select>
                            </div>
                        </div>

                        <div class="manager-task-filter-row">
                            <label class="manager-task-filter-label" for="taskPriorityFilter-completed">Priority</label>
                            <div class="manager-task-filter-wrap">
                                <select id="taskPriorityFilter-completed" class="manager-task-filter-select" data-task-section="completed" data-filter-type="priority">
                                    <option value="All">All priorities</option>
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
                </div>
            </section>
        </main>
    </div>
@endsection