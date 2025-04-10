@extends('app')

@section('content')
    <div class="form">
        <a href="/schedules/list"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M400-80 0-480l400-400 71 71-329 329 329 329-71 71Z"/></svg>Back to Schedule List</a>
        <h2>Update Schedule</h2>
        
        <!-- Step 1: Employee Selection -->
        <div id="step1" class="step">
            <h3>Step 1: Select Employees</h3>
            <form class="form-content" id="employee-selection-form">
                <div class="grid-group">
                    <div class="form-group">
                        <label for="branch_filter">Branch</label>
                        <select name="branch_filter" id="branch_filter" class="form-control">
                            <option value="" selected>All Branches</option>
                            <!-- Branches populated dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="employee_search">Search Employee</label>
                        <input type="text" name="employee_search" id="employee_search" class="form-control" placeholder="Enter name or ID">
                    </div>
                </div>
                <div class="form-group">
                    <label>Employees</label>
                    <div id="employee-checkboxes" class="checkbox-container">
                        <!-- Checkboxes populated dynamically -->
                    </div>
                    <small class="text-danger error" id="error-selected_employees"></small>
                </div>
                <div id="pagination" class="pagination-wrapper" style="display: none;"></div>
                <button type="button" id="next-to-step2" class="btn btn-primary">Next</button>
            </form>
        </div>

        <!-- Step 2: Schedule Days -->
        <div id="step2" class="step" style="display: none;">
            <h3>Step 2: Update Schedule Days</h3>
            <form class="form-content" id="schedule-form">
                <div class="grid-group">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" class="form-control" value="{{ $schedule->start_date }}" required>
                        <small class="text-danger error" id="error-start_date"></small>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" class="form-control" value="{{ $schedule->end_date }}" required>
                        <small class="text-danger error" id="error-end_date"></small>
                    </div>
                </div>
                <div class="form-group">
                    <label>Schedule Days (Click to cycle: Work → Off → Holiday → Work)</label>
                    <small class="text-muted">Light Green = Work Day, Light Red = Day Off (only one), Light Yellow = Holiday, Gray = Empty</small>
                    <div class="calendar-header">
                        <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
                    </div>
                    <div id="schedule-days-boxes" class="calendar-grid"></div>
                    <br>
                    <small class="text-danger error" id="error-schedule_days"></small>
                </div>
                <div class="buttons">
                    <button type="button" id="back-to-step1" class="btn btn-secondary">Back</button>
                    <button type="submit" id="submit-schedule" class="btn btn-primary">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .form { padding: 20px; }
        .btn-primary { display: inline-block; padding: 8px 16px; background-color: rgb(200, 0, 0); color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn-secondary { display: inline-block; padding: 8px 16px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .grid-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .checkbox-container { max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 4px; }
        .checkbox-container label { display: block; margin-bottom: 5px; }
        small { margin-bottom: 20px; }
        .buttons { display: flex; justify-content: center; margin-top: 20px; }
        .pagination-wrapper { margin-top: 10px; text-align: center; }
        .pagination-link { padding: 5px 10px; margin: 0 5px; border: 1px solid #ccc; background-color: #fff; cursor: pointer; }
        .pagination-link.active { background-color: #007bff; color: white; border-color: #007bff; }
        .pagination-link.disabled { background-color: #e9ecef; cursor: not-allowed; }
        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: bold;
            background-color: #f2f2f2;
            border-bottom: 1px solid #ddd;
            margin-bottom: 5px;
        }
        .calendar-header div {
            padding: 10px;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            width: 100%;
        }
        .calendar-box {
            height: 100px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
            padding: 5px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-size: 12px;
            cursor: pointer;
            background-color: #d4edda; /* Default to work */
        }
        .calendar-box.empty {
            background-color: #e9ecef;
            cursor: default;
        }
        .calendar-box .date {
            font-size: 16px;
            font-weight: bold;
        }
        .calendar-box .month {
            font-size: 12px;
            color: #666;
        }
        .calendar-box .status {
            font-size: 11px;
            margin-top: 5px;
        }
        .calendar-box.work { background-color: #d4edda; } /* Light green */
        .calendar-box.off { background-color: #f8d7da; } /* Light red */
        .calendar-box.holiday { background-color: #fff3cd; } /* Light yellow */
        .calendar-box:hover:not(.empty) { border-color: #dc3545; }
        @media (max-width: 768px) { 
            .calendar-header, .calendar-grid { grid-template-columns: repeat(5, 1fr); }
            .calendar-box { height: 80px; font-size: 10px; }
            .calendar-box .date { font-size: 14px; }
            .calendar-box .month { font-size: 10px; }
            .calendar-box .status { font-size: 9px; }
        }
        @media (max-width: 480px) { 
            .calendar-header, .calendar-grid { grid-template-columns: repeat(3, 1fr); }
            .calendar-box { height: 70px; font-size: 9px; }
            .calendar-box .date { font-size: 12px; }
            .calendar-box .month { font-size: 9px; }
            .calendar-box .status { font-size: 8px; }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            let selectedEmployees = ["{{ $schedule->employee_id }}"];
            let currentPage = 1;
            let scheduleDays = {
                work: @json($schedule->work_days),
                off: "{{ $schedule->day_off }}",
                holiday: @json($schedule->holidays)
            };

            const today = new Date('2025-03-24');
            const todayStr = today.toISOString().split('T')[0];
            $('#start_date').attr('min', todayStr);
            $('#end_date').attr('min', todayStr);

            function checkInput() {
                $(".form-control").each(function () {
                    let value = $(this).val();
                    if ($(this).is("input") && value && value.trim() !== "") {
                        $(this).addClass("has-text");
                    } else if ($(this).is("select") && value && value.length > 0) {
                        $(this).addClass("has-text");
                    } else {
                        $(this).removeClass("has-text");
                    }
                });
            }

            checkInput();
            $(".form-control").on("input change", checkInput);

            function fetchBranches() {
                axios.get('/branches/filter')
                    .then(response => {
                        let branches = response.data.data;
                        $('#branch_filter').empty();
                        $('#branch_filter').append('<option value="" selected>All Branches</option>');
                        branches.forEach(branch => {
                            $('#branch_filter').append(
                                `<option value="${branch.branch_name}">${branch.branch_name}</option>`
                            );
                        });
                    })
                    .catch(error => {
                        console.error("Error fetching branches:", error);
                    });
            }

            function renderPagination(currentPage, lastPage) {
                const paginationWrapper = $("#pagination");
                paginationWrapper.empty();

                paginationWrapper.append(currentPage > 1
                    ? `<button class="pagination-link" data-page="${currentPage - 1}">Previous</button>`
                    : `<button class="pagination-link disabled" disabled>Previous</button>`);

                for (let i = Math.max(1, currentPage - 1); i <= Math.min(lastPage, currentPage + 1); i++) {
                    const activeClass = i === currentPage ? 'active' : '';
                    paginationWrapper.append(`
                        <button class="pagination-link ${activeClass}" data-page="${i}">${i}</button>
                    `);
                }

                paginationWrapper.append(currentPage < lastPage
                    ? `<button class="pagination-link" data-page="${currentPage + 1}">Next</button>`
                    : `<button class="pagination-link disabled" disabled>Next</button>`);

                $(".pagination-link").click(function () {
                    if (!$(this).hasClass("disabled")) {
                        currentPage = $(this).data("page");
                        fetchEmployees(currentPage);
                    }
                });
            }

            function fetchEmployees(page = 1) {
                let searchQuery = $('#employee_search').val().trim();
                let branch = $('#branch_filter').val();

                let url = branch ? '/employees/by-branch' : '/api/employees';
                let params = branch ? { branch: branch, search: searchQuery } : { search: searchQuery, page: page };

                axios.get(url, { params })
                    .then(response => {
                        let employees = branch ? response.data.data : response.data.data.data;
                        $('#employee-checkboxes').empty();

                        if (employees && employees.length > 0) {
                            employees.forEach(emp => {
                                const isChecked = selectedEmployees.includes(emp.employee_id) ? 'checked' : '';
                                $('#employee-checkboxes').append(`
                                    <label>
                                        <input type="checkbox" 
                                               name="selected_employees[]" 
                                               value="${emp.employee_id}"
                                               class="employee-checkbox" ${isChecked}>
                                        ${emp.first_name} ${emp.last_name} (${emp.branch})
                                    </label>
                                `);
                            });
                        } else {
                            $('#employee-checkboxes').append('<p>No employees found.</p>');
                        }

                        $('#employee-checkboxes').off('change').on('change', '.employee-checkbox', function() {
                            selectedEmployees = $('.employee-checkbox:checked').map(function() {
                                return this.value;
                            }).get();
                        });

                        if (!branch && response.data.data.last_page) {
                            renderPagination(page, response.data.data.last_page);
                            $('#pagination').show();
                        } else {
                            $('#pagination').hide();
                        }
                    })
                    .catch(error => {
                        console.error("Error fetching employees:", error);
                        $('#employee-checkboxes').empty().append('<p>Error loading employees.</p>');
                        $('#pagination').hide();
                    });
            }

            function generateDayBoxes(startDate, endDate) {
                if (!startDate || !endDate || startDate > endDate) {
                    $('#schedule-days-boxes').empty();
                    return;
                }

                const start = new Date(startDate);
                const end = new Date(endDate);
                const dayBoxes = $('#schedule-days-boxes');
                dayBoxes.empty();

                const daysDiff = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
                const startDay = start.getDay() === 0 ? 6 : start.getDay() - 1; // Adjust to Mon=0, Sun=6

                // Add empty boxes before start date
                for (let i = 0; i < startDay; i++) {
                    dayBoxes.append(`<div class="calendar-box empty"></div>`);
                }

                // Add selectable days
                for (let i = 0; i < daysDiff; i++) {
                    const date = new Date(start);
                    date.setDate(start.getDate() + i);
                    const dateStr = date.toISOString().split('T')[0];
                    const dayNum = date.getDate();
                    const monthName = date.toLocaleString('default', { month: 'short' });
                    let className = 'work';
                    let status = 'Work';
                    if (scheduleDays.off === dateStr) {
                        className = 'off';
                        status = 'Day Off';
                    } else if (scheduleDays.holiday.includes(dateStr)) {
                        className = 'holiday';
                        status = 'Holiday';
                    } else if (!scheduleDays.work.includes(dateStr) && !scheduleDays.holiday.includes(dateStr) && scheduleDays.off !== dateStr) {
                        scheduleDays.work.push(dateStr);
                    }
                    dayBoxes.append(`
                        <div class="calendar-box ${className}" data-date="${dateStr}">
                            <div class="date">${dayNum}</div>
                            <div class="month">${monthName}</div>
                            <div class="status">${status}</div>
                        </div>
                    `);
                }

                $('#schedule-days-boxes .calendar-box:not(.empty)').off('click').on('click', function() {
                    const date = $(this).data('date');
                    const currentState = $(this).hasClass('work') ? 'work' :
                                        $(this).hasClass('off') ? 'off' :
                                        $(this).hasClass('holiday') ? 'holiday' : 'work';

                    $(this).removeClass('work off holiday');
                    if (currentState === 'work') {
                        scheduleDays.work = scheduleDays.work.filter(d => d !== date);
                    } else if (currentState === 'off') {
                        scheduleDays.off = '';
                    } else if (currentState === 'holiday') {
                        scheduleDays.holiday = scheduleDays.holiday.filter(d => d !== date);
                    }

                    switch (currentState) {
                        case 'work':
                            const previousOff = $('#schedule-days-boxes .calendar-box.off');
                            if (previousOff.length) {
                                const previousDate = previousOff.data('date');
                                previousOff.removeClass('off').addClass('work');
                                previousOff.find('.status').text('Work');
                                scheduleDays.work.push(previousDate);
                            }
                            $(this).addClass('off');
                            $(this).find('.status').text('Day Off');
                            scheduleDays.off = date;
                            break;
                        case 'off':
                            $(this).addClass('holiday');
                            $(this).find('.status').text('Holiday');
                            scheduleDays.holiday.push(date);
                            break;
                        case 'holiday':
                            $(this).addClass('work');
                            $(this).find('.status').text('Work');
                            scheduleDays.work.push(date);
                            break;
                    }
                });
            }

            generateDayBoxes("{{ $schedule->start_date }}", "{{ $schedule->end_date }}");
            $('#start_date, #end_date').on('change', function() {
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();
                generateDayBoxes(startDate, endDate);
            });

            fetchBranches();
            fetchEmployees();

            $('#employee_search, #branch_filter').on('input change', function() {
                currentPage = 1;
                fetchEmployees();
            });

            $('#next-to-step2').click(function () {
                selectedEmployees = $('.employee-checkbox:checked').map(function() {
                    return this.value;
                }).get();

                if (!selectedEmployees || selectedEmployees.length === 0) {
                    $('#error-selected_employees').text('Please select at least one employee.');
                    return;
                }
                $('#step1').hide();
                $('#step2').show();
            });

            $('#back-to-step1').click(function () {
                $('#step2').hide();
                $('#step1').show();
            });

            $('#schedule-form').on('submit', function (event) {
                event.preventDefault();
                $('.error').text('');

                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();

                if (!startDate) {
                    $('#error-start_date').text('Please select a start date.');
                    return;
                }
                if (!endDate) {
                    $('#error-end_date').text('Please select an end date.');
                    return;
                }
                if (new Date(startDate) > new Date(endDate)) {
                    $('#error-end_date').text('End date must be after start date.');
                    return;
                }
                if (scheduleDays.work.length === 0) {
                    $('#error-schedule_days').text('Please select at least one work day.');
                    return;
                }
                if (!scheduleDays.off) {
                    $('#error-schedule_days').text('Please select one day off.');
                    return;
                }

                let formData = {
                    employees: selectedEmployees,
                    start_date: startDate,
                    end_date: endDate,
                    work_days: scheduleDays.work,
                    day_off: scheduleDays.off,
                    holidays: scheduleDays.holiday
                };

                axios.put(`/api/schedules/{{ $schedule->id }}`, formData)
                    .then(response => {
                        alert('Schedule updated successfully!');
                        window.location.href = '/schedules';
                    })
                    .catch(error => {
                        if (error.response && error.response.data.errors) {
                            let errors = error.response.data.errors;
                            for (let field in errors) {
                                $(`#error-${field}`).text(errors[field][0]);
                            }
                        } else {
                            alert('An error occurred while updating the schedule.');
                        }
                    });
            });
        });
    </script>
@endsection