@extends('app')

@section('content')
    <div class="content">
        <h2>Device Mode</h2>
        <div class="mode-buttons">
            <button class="mode-btn" data-mode="attendance">Attendance</button>
            <button class="mode-btn" data-mode="break">Break</button>
            <button class="mode-btn" data-mode="enroll">Enroll</button>
        </div>

        <h2>Attendance List</h2>
        <div class="filters">
            <div class="form-group">
                <label for="date_filter">Date</label>
                <input type="date" id="date_filter" class="form-control" value="{{ date('Y-m-d') }}">
            </div>
            <div class="form-group">
                <label for="branch_filter">Branch</label>
                <select id="branch_filter" class="form-control">
                    <option value="">All Branches</option>
                </select>
            </div>
            <div class="form-group">
                <label for="search_filter">Search Employee</label>
                <input type="text" id="search_filter" class="form-control" placeholder="Enter employee name or ID">
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Branch</th>
                    <th>Time In</th>
                    <th>Break In</th>
                    <th>Break Out</th>
                    <th>Time Out</th>
                </tr>
            </thead>
            <tbody id="attendance-body">
                <tr><td colspan="7">Loading...</td></tr>
            </tbody>
        </table>
        <div id="pagination" class="pagination-controls"></div>
    </div>

    <style>
        .content { padding: 20px; }
        .mode-buttons { margin-bottom: 20px; }
        .mode-btn { 
            padding: 10px 20px; 
            margin-right: 10px; 
            border: none; 
            cursor: pointer;
            background-color: #ccc;
            color: #000000;
            border-radius: 5px;
            transition: all .3s ease;
        }
        .mode-btn.active { 
            background-color: red; 
            color: white; 
            font-weight: bold;
        }
        .mode-btn:hover { background-color: rgba(200, 0, 0); color: white; }
        .filters { display: flex; gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-control { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            fetchBranches();
            getDeviceMode();
            fetchAttendance();

            $('#date_filter, #branch_filter').on('change', function () {
                fetchAttendance(1); 
            });

            $('#search_filter').on('input', function () {
                fetchAttendance(1);
            });

             // Change Device Mode
             $('.mode-btn').click(function () {
                let mode = $(this).data('mode');
                changeDeviceMode(mode);
            });

            setInterval(function () {
                fetchAttendance();
            }, 3000);
        });
        function getDeviceMode() {
            axios.get('/device-mode')
                .then(response => {
                    let mode = response.data.mode;
                    highlightActiveMode(mode);
                })
                .catch(error => {
                    console.error("Error fetching device mode:", error);
                });
        }
        function changeDeviceMode(mode) {
            axios.post('/device/mode', { mode: mode })
                .then(response => {
                    alert('Device mode updated to ' + mode);
                    getDeviceMode(); // Refresh the mode display
                })
                .catch(error => {
                    console.error("Error updating mode:", error);
                    alert('Failed to update device mode.');
                });
        }

        function highlightActiveMode(mode) {
            $('.mode-btn').removeClass('active');
            $(`.mode-btn[data-mode="${mode}"]`).addClass('active');
        }

        function fetchAttendance(page = 1) {
            const date = $('#date_filter').val();
            const branch = $('#branch_filter').val();
            const search = $('#search_filter').val();

            axios.get(`/api/attendance?page=${page}`, {
                params: { date: date, branch: branch, search: search }
            })
            .then(response => {
                if (response.data.data) {
                    let records = response.data.data.data;
                    let lastPage = response.data.data.last_page;
                    let currentPage = response.data.data.current_page;

                    let attendanceTable = $("#attendance-body");
                    attendanceTable.empty();

                    if (records.length === 0) {
                        attendanceTable.append("<tr><td colspan='7'>No matching records found.</td></tr>");
                    } else {
                        records.forEach(record => {
                            attendanceTable.append(`
                                <tr>
                                    <td>${record.employee_id}</td>
                                    <td>${record.name ? record.name : 'N/A'}</td>
                                    <td>${record.branch ? record.branch : 'N/A'}</td>
                                    <td>${record.time_in || 'N/A'}</td>
                                    <td>${record.break_in ? record.break_in : 'N/A'}</td>
                                    <td>${record.break_out ? record.break_out : 'N/A'}</td>
                                    <td>${record.time_out || 'N/A'}</td>
                                </tr>
                            `);
                        });
                    }

                    renderPagination(currentPage, lastPage);
                } else {
                    console.error("Unexpected API response format:", response.data);
                }
            })
            .catch(error => {
                console.error("Error fetching attendance:", error);
                $("#attendance-body").html("<tr><td colspan='7'>Error loading attendance records.</td></tr>");
            });
        }

        function fetchBranches() {
            axios.get('/branches/filter')
                .then(response => {
                    let branches = response.data.data;
                    $('#branch_filter').empty();
                    $('#branch_filter').append('<option value="">All Branches</option>');
                    branches.forEach(branch => {
                        $('#branch_filter').append(
                            `<option value="${branch.branch_name}">${branch.branch_name}</option>`
                        );
                    });
                })
                .catch(error => console.error("Error fetching branches:", error));
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
                let page = $(this).data("page");
                if (!$(this).hasClass("disabled")) {
                    fetchAttendance(page);
                }
            });
        }
    </script>
@endsection
