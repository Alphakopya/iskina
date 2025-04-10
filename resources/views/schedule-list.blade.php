@extends('app')

@section('content')
    <div class="content">
        <h2>Employee Schedules</h2>
        <a href="/schedules/new" class="back btn-primary">New Schedule</a>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="schedule-body">
                <tr><td colspan="8">Loading...</td></tr>
            </tbody>
        </table>
        <div id="pagination" class="pagination-controls"></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    window.fetchSchedules = function (page = 1) {
        axios.get(`/api/schedules?page=${page}`)
            .then(response => {
                if (response.data.data) {
                    let schedules = response.data.data.data;
                    let lastPage = response.data.data.last_page;
                    let currentPage = response.data.data.current_page;

                    let scheduleTable = $("#schedule-body");
                    scheduleTable.empty();

                    if (schedules.length === 0) {
                        scheduleTable.append("<tr><td colspan='7'>No schedules found.</td></tr>");
                    } else {
                        schedules.forEach(schedule => {
                            scheduleTable.append(`
                                <tr>
                                    <td>${schedule.employee_id}</td>
                                    <td>${schedule.start_date}</td>
                                    <td>${schedule.end_date}</td>
                                    <td>
                                            <a href="/schedule-update/${schedule.id}" class="btn btn-warning">
                                                <img src="{{ asset('images/edit.svg') }}" alt="Edit" />
                                            </a>
                                            <a href="/schedule-view/${schedule.id}" class="btn btn-info">
                                                <img src="{{ asset('images/view.svg') }}" alt="View" />
                                            </a>
                                        </td>
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
                console.error("Error fetching schedules:", error);
                $("#schedule-body").html("<tr><td colspan='7'>Error loading schedules.</td></tr>");
            });
    };

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
                fetchSchedules(page);
            }
        });
    }

    $(document).ready(function () {
        if ($("#schedule-body").length) {
            fetchSchedules();
        }
    });
</script>

@endsection
