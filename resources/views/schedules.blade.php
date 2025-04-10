@extends('app')

@section('content')
    <div class="content">
        <h2>My Schedule Details</h2>

        <div class="schedule-details" id="schedule-details">
            <!-- Employee details will be populated via JavaScript -->
        </div>

        <h3>My Schedule Calendar</h3>
        <div class="calendar" id="calendar-container">
            <!-- Calendar will be populated via JavaScript -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function fetchMySchedules() {
            axios.get('/my-schedules')
                .then(response => {
                    if (response.data.status === 'success' && response.data.data.length > 0) {
                        const schedules = response.data.data;
                        const latestSchedule = schedules[0]; // Assuming we show the most recent schedule

                        // Populate employee details
                        let employeeDetails = `
                            <p><strong>Employee ID:</strong> ${latestSchedule.employee_id}</p>
                        `;
                        if (latestSchedule.employee) {
                            employeeDetails += `
                                <p><strong>Employee Name:</strong> ${latestSchedule.employee.first_name} ${latestSchedule.employee.last_name}</p>
                                <p><strong>Branch:</strong> ${latestSchedule.employee.branch}</p>
                            `;
                        }
                        $('#schedule-details').html(employeeDetails);

                        // Generate calendar
                        generateCalendar(latestSchedule);
                    } else {
                        $('#schedule-details').html('<p>No schedules found for you.</p>');
                        $('#calendar-container').html('<p>No schedule data available.</p>');
                    }
                })
                .catch(error => {
                    console.error('Error fetching schedules:', error);
                    $('#schedule-details').html('<p>Error loading schedule details.</p>');
                    $('#calendar-container').html('<p>Error loading calendar.</p>');
                });
        }

        function generateCalendar(schedule) {
            const start = new Date(schedule.start_date);
            const end = new Date(schedule.end_date);
            end.setDate(end.getDate() + 1); // Include end date

            const days = [];
            let currentDate = new Date(start);
            while (currentDate < end) {
                days.push(new Date(currentDate));
                currentDate.setDate(currentDate.getDate() + 1);
            }

            const firstDayOfWeek = (start.getDay() + 6) % 7; // Convert Sunday (0) to 6, Monday (1) to 0
            const totalDays = days.length;
            const grid = Array(max(35, Math.ceil((totalDays + firstDayOfWeek) / 7) * 7)).fill(null);
            days.forEach((date, index) => {
                grid[firstDayOfWeek + index] = date;
            });

            let calendarHtml = `
                <div class="calendar-grid">
                    <div class="calendar-header">
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                        <div>Sun</div>
                    </div>
            `;

            const weeks = [];
            for (let i = 0; i < grid.length; i += 7) {
                weeks.push(grid.slice(i, i + 7));
            }

            weeks.forEach(week => {
                calendarHtml += '<div class="calendar-row">';
                week.forEach(date => {
                    let dateStr = date ? date.toISOString().split('T')[0] : null;
                    let status = dateStr ? 'Unassigned' : '';
                    let className = dateStr ? 'unassigned' : 'empty';

                    if (dateStr) {
                        if (schedule.work_days && schedule.work_days.includes(dateStr)) {
                            status = 'Work';
                            className = 'work';
                        } else if (schedule.day_off === dateStr) {
                            status = 'Day Off';
                            className = 'day-off';
                        } else if (schedule.holidays && schedule.holidays.includes(dateStr)) {
                            status = 'Holiday';
                            className = 'holiday';
                        }
                    }

                    calendarHtml += `
                        <div class="calendar-box ${className}">
                            ${date ? `
                                <div class="date">${date.getDate()}</div>
                                <div class="month">${date.toLocaleString('default', { month: 'short' })}</div>
                                <div class="status">${status}</div>
                            ` : ''}
                        </div>
                    `;
                });
                calendarHtml += '</div>';
            });

            calendarHtml += '</div>';
            $('#calendar-container').html(calendarHtml);
        }

        $(document).ready(function () {
            fetchMySchedules();
        });

        function max(a, b) {
            return a > b ? a : b;
        }
    </script>

    <style>
        .content { padding: 20px; }
        .btn-primary { 
            display: inline-block; 
            padding: 8px 16px; 
            background-color: rgb(200, 0, 0); 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            margin-bottom: 20px; 
        }
        .schedule-details { margin-bottom: 20px; }
        p { margin: 5px 0; }
        .calendar-grid { width: 100%; }
        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: bold;
            background-color: #f2f2f2;
            border-bottom: 1px solid #ddd;
            margin-bottom: 5px;
        }
        .calendar-header div { padding: 10px; }
        .calendar-row {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-bottom: 5px;
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
        }
        .calendar-box.empty { background-color: #e9ecef; }
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
        .work { background-color: #d4edda; }
        .day-off { background-color: #f8d7da; }
        .holiday { background-color: #fff3cd; }
        .unassigned { background-color: #f8f9fa; }
    </style>
@endsection