@extends('app')

@section('content')
    <div class="content">
        <h2>Today's Attendance</h2>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Branch</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Break In</th>
                    <th>Break Out</th>
                    <th>Time Out</th>
                </tr>
            </thead>
            <tbody id="today-attendance-body">
                <tr><td colspan="5">Loading...</td></tr>
            </tbody>
        </table>

        <h2>Attendance History</h2>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Branch</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Break In</th>
                    <th>Break Out</th>
                    <th>Time Out</th>
                </tr>
            </thead>
            <tbody id="history-attendance-body">
                <tr><td colspan="5">Loading...</td></tr>
            </tbody>
        </table>

        <div id="pagination" class="pagination-controls"></div>
    </div>

    <style>
        .content { padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Fetch attendance records for today and history
        window.fetchAttendance = function (page = 1) {
            const date = $('#date_filter').val();
            const branch = $('#branch_filter').val();
            const search = $('#search_filter').val(); // Get search query

            // Show loading message
            $("#today-attendance-body").html("<tr><td colspan='5'>Loading...</td></tr>");
            $("#history-attendance-body").html("<tr><td colspan='5'>Loading...</td></tr>");

            axios.get(`/my-attendance/list?page=${page}`, {
                params: { date: date, branch: branch, search: search }
            })
            .then(response => {
                if (response.data) {
                    const todayAttendance = response.data.todayAttendance;
                    const historyAttendance = response.data.historyAttendance;
                    const historyPagination = response.data.historyPagination;

                    // Handle Today's Attendance
                    let todayTable = $("#today-attendance-body");
                    todayTable.empty();

                    if (todayAttendance.length === 0) {
                        todayTable.append("<tr><td colspan='5'>No attendance for today.</td></tr>");
                    } else {
                        todayAttendance.forEach(record => {
                            todayTable.append(`
                                <tr>
                                    <td>${record.employee_id}</td>
                                    <td>${record.name ? record.name : 'N/A'}</td>
                                    <td>${record.branch ? record.branch : 'N/A'}</td>
                                    <td>${record.date || 'N/A'}</td>
                                    <td>${record.time_in || 'N/A'}</td>
                                    <td>${record.break_in || 'N/A'}</td>
                                    <td>${record.break_out || 'N/A'}</td>
                                    <td>${record.time_out || 'N/A'}</td>
                                </tr>
                            `);
                        });
                    }

                    // Handle Attendance History
                    let historyTable = $("#history-attendance-body");
                    historyTable.empty();

                    if (historyAttendance.length === 0) {
                        historyTable.append("<tr><td colspan='5'>No historical attendance records.</td></tr>");
                    } else {
                        historyAttendance.forEach(record => {
                            historyTable.append(`
                                <tr>
                                    <td>${record.employee_id}</td>
                                    <td>${record.name ? record.name : 'N/A'}</td>
                                    <td>${record.branch ? record.branch : 'N/A'}</td>
                                    <td>${record.date || 'N/A'}</td>
                                    <td>${record.time_in || 'N/A'}</td>
                                    <td>${record.break_in || 'N/A'}</td>
                                    <td>${record.break_out || 'N/A'}</td>
                                    <td>${record.time_out || 'N/A'}</td>
                                </tr>
                            `);
                        });
                    }

                    // Render pagination for history attendance
                    renderPagination(historyPagination.current_page, historyPagination.last_page);
                } else {
                    console.error("Unexpected API response format:", response.data);
                }
            })
            .catch(error => {
                console.error("Error fetching attendance:", error);
                $("#today-attendance-body").html("<tr><td colspan='5'>Error loading today's attendance.</td></tr>");
                $("#history-attendance-body").html("<tr><td colspan='5'>Error loading historical attendance.</td></tr>");
            });
        };

        // Pagination rendering
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

        $(document).ready(function () {
            fetchAttendance();
            $('#date_filter, #branch_filter').on('change', function () {
                fetchAttendance(1); // Reset to page 1 on filter change
            });
        });
    </script>
@endsection
