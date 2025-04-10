@extends('app')

@section('content')
    <div class="content">
        <h2>Employee List</h2>
        <a href="/employee/new" class="back btn-primary">New Employee</a>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Position Title</th>
                    <th>Branch</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Position</th>
                    <th>Action</th> <!-- Added Action Column -->
                </tr>
            </thead>
            <tbody id="employee-body">
                <tr><td colspan="8">Loading...</td></tr>
            </tbody>
        </table>
        <div id="pagination" class="pagination-controls"></div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
   window.fetchEmployees = function (page = 1) {
        axios.get(`/api/employees?page=${page}`)
            .then(response => {
                if (response.data.data) {
                    let employees = response.data.data.data;
                    let lastPage = response.data.data.last_page;
                    let currentPage = response.data.data.current_page;

                    let employeeTable = $("#employee-body");
                    employeeTable.empty();

                    if (employees.length === 0) {
                        employeeTable.append("<tr><td colspan='7'>No employees found.</td></tr>");
                    } else {
                        employees.forEach(employee => {
                            employeeTable.append(`
                                <tr>
                                    <td>${employee.employee_id}</td>
                                    <td>${employee.first_name} ${employee.last_name}</td>
                                    <td>${employee.position_title}</td>
                                    <td>${employee.branch}</td>
                                    <td>${employee.employee_type}</td>
                                    <td>${employee.employee_status}</td>
                                    <td>${employee.position_title}</td>
                                    <td>
                                        <a href="/employee-update/${employee.id}" class="btn btn-warning"><img src="{{ asset('images/edit.svg' )}}" alt="Edit" /></a>
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
                console.error("Error fetching employees:", error);
                $("#employee-body").html("<tr><td colspan='7'>Error loading employees.</td></tr>");
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
                fetchEmployees(page);
            }
        });
    }

    $(document).ready(function () {
        if ($("#employee-body").length) {
            fetchEmployees();
        }
    });
</script>
@endsection
