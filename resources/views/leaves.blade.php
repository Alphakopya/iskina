@extends('app')

@section('content')
    <div class="content">
        <h2>Leave Request</h2>
        <a href="/leaves/new" class="back btn-primary">New Leave Request</a>
        <table>
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="leave-request-body">
                <tr><td colspan="5">Loading...</td></tr>
            </tbody>
        </table>
        <div id="pagination" class="pagination-controls"></div>
    </div>
@endsection

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    window.fetchLeaveRequests = function (page = 1) {
        axios.get(`/my-leaves?page=${page}`)
            .then(response => {
                if (response.data.data) {
                    let requests = response.data.data.data;
                    let lastPage = response.data.data.last_page;
                    let currentPage = response.data.data.current_page;

                    let requestTable = $("#leave-request-body");
                    requestTable.empty();

                    if (requests.length === 0) {
                        requestTable.append("<tr><td colspan='5'>No leave requests found.</td></tr>");
                    } else {
                        requests.forEach(request => {
                            requestTable.append(`
                                <tr>
                                    <td>${request.leave_type}</td>
                                    <td>${request.start_date}</td>
                                    <td>${request.end_date}</td>
                                    <td>${request.reason_leave}</td>
                                    <td>${request.status}</td>
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
                console.error("Error fetching leave requests:", error);
                $("#leave-request-body").html("<tr><td colspan='8'>Error loading leave requests.</td></tr>");
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
                fetchLeaveRequests(page);
            }
        });
    }

    $(document).ready(function () {
        if ($("#leave-request-body").length) {
            fetchLeaveRequests();
        }
    });
</script>
