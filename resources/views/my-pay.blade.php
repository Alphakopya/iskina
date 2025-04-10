@extends('app')

@section('content')
    <div class="form">
        <h2>My Payroll History</h2>

        <!-- Payroll List -->
        <div class="form-content payroll-list">
            <h3>Your Payroll Records</h3>
            <table>
                <thead>
                    <tr>
                        <th>Basic Salary</th>
                        <th>Overtime</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Period</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="payroll-body">
                    <tr><td colspan="6">Loading...</td></tr>
                </tbody>
            </table>
            <div id="payroll-pagination" class="pagination-controls"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function () {
            let currentPage = 1;

            // Fetch Payrolls for the logged-in employee
            function fetchPayrolls(page = 1) {
                axios.get(`/my-payrolls?page=${page}`)
                    .then(response => {
                        if (response.data.status === 'success') {
                            const payrolls = response.data.data.data;
                            const lastPage = response.data.data.last_page;
                            const currentPage = response.data.data.current_page;

                            const payrollTable = $("#payroll-body");
                            payrollTable.empty();

                            if (payrolls.length === 0) {
                                payrollTable.append("<tr><td colspan='6'>No payroll records found.</td></tr>");
                            } else {
                                payrolls.forEach(payroll => {
                                    // Format start_date and end_date to mm-dd-year
                                    const startDate = new Date(payroll.start_date);
                                    const endDate = new Date(payroll.end_date);
                                    const formattedStartDate = `${(startDate.getMonth() + 1).toString().padStart(2, '0')}-${startDate.getDate().toString().padStart(2, '0')}-${startDate.getFullYear()}`;
                                    const formattedEndDate = `${(endDate.getMonth() + 1).toString().padStart(2, '0')}-${endDate.getDate().toString().padStart(2, '0')}-${endDate.getFullYear()}`;

                                    payrollTable.append(`
                                        <tr>
                                            <td>P${parseFloat(payroll.basic_salary).toFixed(2)}</td>
                                            <td>P${parseFloat(payroll.overtime_pay).toFixed(2)}</td>
                                            <td>P${parseFloat(payroll.deductions).toFixed(2)}</td>
                                            <td>P${parseFloat(payroll.net_salary).toFixed(2)}</td>
                                            <td>${formattedStartDate} to ${formattedEndDate}</td>
                                            <td>${payroll.status}</td>
                                        </tr>
                                    `);
                                });
                            }
                            renderPayrollPagination(currentPage, lastPage);
                        } else {
                            $("#payroll-body").html("<tr><td colspan='6'>Error: ${response.data.message}</td></tr>");
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching payrolls:', error);
                        $("#payroll-body").html("<tr><td colspan='6'>Error loading payrolls.</td></tr>");
                    });
            }

            // Payroll Pagination
            function renderPayrollPagination(currentPage, lastPage) {
                const paginationWrapper = $("#payroll-pagination");
                paginationWrapper.empty();

                paginationWrapper.append(currentPage > 1
                    ? `<button class="pagination-link" data-page="${currentPage - 1}">Previous</button>`
                    : `<button class="pagination-link disabled" disabled>Previous</button>`);

                for (let i = Math.max(1, currentPage - 1); i <= Math.min(lastPage, currentPage + 1); i++) {
                    const activeClass = i === currentPage ? 'active' : '';
                    paginationWrapper.append(`<button class="pagination-link ${activeClass}" data-page="${i}">${i}</button>`);
                }

                paginationWrapper.append(currentPage < lastPage
                    ? `<button class="pagination-link" data-page="${currentPage + 1}">Next</button>`
                    : `<button class="pagination-link disabled" disabled>Next</button>`);

                $(".pagination-link").click(function () {
                    if (!$(this).hasClass("disabled")) {
                        fetchPayrolls($(this).data("page"));
                    }
                });
            }

            // Initial fetch
            fetchPayrolls();
        });
    </script>

    <style>
        .form { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .form a { display: inline-flex; align-items: center; margin-bottom: 20px; text-decoration: none; color: #000; }
        .form a svg { margin-right: 5px; }
        .form-content { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h3 { margin-top: 20px; margin-bottom: 10px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .pagination-controls { margin-top: 20px; text-align: center; }
        .pagination-link { padding: 5px 10px; margin: 0 2px; border: 1px solid #ccc; background-color: #fff; cursor: pointer; }
        .pagination-link.active { background-color: #007bff; color: white; }
        .pagination-link.disabled { background-color: #e9ecef; cursor: not-allowed; }
    </style>
@endsection