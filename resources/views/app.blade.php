<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Iskina Pares')</title>

    @vite(['resources/scss/app.scss', 'resources/scss/table.scss', 'resources/scss/form.scss'])
</head>
<body>
    <main>
        <div class="sidebar" id="sidebar">
            <!-- Admin/Manager Links -->
            <!-- My Leaves (Only for Employee Role) -->
            @if (Auth::check() && Auth::user()->role === 'admin' || Auth::user()->role === 'manager' || Auth::user()->role === 'HR')
            <a href="{{ url('/') }}" class="ajax-link" data-url="{{ url('/') }}">Dashboard</a>
            <a href="{{ route('attendance.list') }}" class="ajax-link" data-url="{{ route('attendance.list') }}">Attendance</a>
            <a href="{{ route('fingerprint.list') }}" class="ajax-link" data-url="{{ route('fingerprint.list') }}">Fingerprint</a>
            <a href="{{ route('schedules.list') }}" class="ajax-link" data-url="{{ route('attendance.list') }}">Scheduling</a>
            <a href="{{ route('payrolls') }}" class="ajax-link" data-url="{{ route('payrolls') }}">Payroll</a>
            <a href="{{ route('employee.list') }}" class="ajax-link" data-url="{{ route('employee.list') }}"><span>Employees</span></a>
            <a href="{{ route('branch.list') }}" class="ajax-link" data-url="{{ route('branch.list') }}"><span>Branches</span></a>
            <a href="{{ route('leaves.list') }}" class="ajax-link" data-url="{{ route('leaves.list') }}"><span>Leave & Recovery</span></a>
            @elseif(Auth::check() && Auth::user()->role === 'employee')
            <!-- Employee Links -->
            <a href="{{ route('profile') }}" class="ajax-link" data-url="{{ route('profile') }}"><span>Profile</span></a>
            <a href="{{ route('my.pay') }}" class="ajax-link" data-url="{{ route('my.pay') }}"><span>My Pay</span></a>
            <a href="{{ route('my.attendance') }}" class="ajax-link" data-url="{{ route('my.attendance') }}"><span>My Attendance</span></a>
            <a href="{{ route('schedules') }}" class="ajax-link" data-url="{{ route('schedules') }}"><span>My Schedules</span></a>
                <a href="{{ route('my.leaves') }}" class="ajax-link" data-url="{{ route('my.leaves') }}"><span>My Leaves</span></a>
            @endif

            <a id="logout-btn">Logout</a>
        </div>

        <!-- Content will be dynamically updated here -->
        <div id="content">
            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function () {
            // Existing logout and dropdown code
            $("#logout-btn").click(function () {
                axios.post("{{ route('logout') }}")
                    .then(response => {
                        window.location.href = response.data.redirect;
                    });
            });

            $(".dropdown-toggle").click(function () {
                let menu = $(this).next(".dropdown-menu");
                $(".dropdown-menu").not(menu).slideUp();
                menu.slideToggle();
            });
        });
    </script>
</body>
</html>