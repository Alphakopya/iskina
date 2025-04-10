@extends('app')

@section('content')
    <div class="content">
        <h2>Schedule Details for {{ $schedule->employee ? $schedule->employee->first_name . ' ' . $schedule->employee->last_name : 'Employee ' . $schedule->employee_id }}</h2>
        <a href="/schedules/list" class="btn btn-primary">Back to Schedules</a>

        <div class="schedule-details">
            <p><strong>Employee ID:</strong> {{ $schedule->employee_id }}</p>
            @if ($schedule->employee)
                <p><strong>Employee Name:</strong> {{ $schedule->employee->first_name }} {{ $schedule->employee->last_name }}</p>
                <p><strong>Branch:</strong> {{ $schedule->employee->branch }}</p>
            @endif

        </div>

        <h3>Schedule Calendar</h3>
        <div class="calendar">
            @php
                $start = new DateTime($schedule->start_date);
                $end = new DateTime($schedule->end_date);
                $interval = new DateInterval('P1D');
                $dateRange = new DatePeriod($start, $interval, $end->modify('+1 day')); // Include end date
                $days = iterator_to_array($dateRange);
                $totalDays = count($days);
                $firstDayOfWeek = (int)$start->format('N') - 1; // 0 (Mon) to 6 (Sun)
                $grid = array_fill(0, max(35, ceil(($totalDays + $firstDayOfWeek) / 7) * 7), null); // Up to 5 rows or enough to fit
                foreach ($days as $index => $date) {
                    $grid[$firstDayOfWeek + $index] = $date;
                }
            @endphp

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
                @foreach (array_chunk($grid, 7) as $week)
                    <div class="calendar-row">
                        @foreach ($week as $date)
                            @php
                                $dateStr = $date ? $date->format('Y-m-d') : null;
                                $status = $dateStr ? 'Unassigned' : '';
                                $class = $dateStr ? 'unassigned' : 'empty';
                                if ($dateStr) {
                                    if (in_array($dateStr, $schedule->work_days)) {
                                        $status = 'Work';
                                        $class = 'work';
                                    } elseif ($dateStr === $schedule->day_off) {
                                        $status = 'Day Off';
                                        $class = 'day-off';
                                    } elseif (in_array($dateStr, $schedule->holidays)) {
                                        $status = 'Holiday';
                                        $class = 'holiday';
                                    }
                                }
                            @endphp
                            <div class="calendar-box {{ $class }}">
                                @if ($date)
                                    <div class="date">{{ $date->format('d') }}</div>
                                    <div class="month">{{ $date->format('M') }}</div>
                                    <div class="status">{{ $status }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .content { padding: 20px; }
        .btn-primary { display: inline-block; padding: 8px 16px; background-color: rgb(200, 0, 0); color: white; text-decoration: none; border-radius: 4px; margin-bottom: 20px; }
        .schedule-details { margin-bottom: 20px; }
        p {
            margin: 5px 0;
        }
        .calendar-grid {
            width: 100%;
        }
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
        .calendar-row {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-bottom: 5px;
        }
        .calendar-box {
            height: 100px; /* Increased height to fit month */
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
        .calendar-box.empty {
            background-color: #e9ecef;
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
        .work { background-color: #d4edda; } /* Light green */
        .day-off { background-color: #f8d7da; } /* Light red */
        .holiday { background-color: #fff3cd; } /* Light yellow */
        .unassigned { background-color: #f8f9fa; } /* Light gray */
    </style>
@endsection