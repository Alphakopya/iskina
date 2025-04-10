@extends('app')

@section('content')
    <div class="form">
        <h2>Payroll Management</h2>

        <!-- Step 1: Employee Selection -->
        <div id="step1" class="step">
            <h3>Step 1: Select Employees</h3>
            <form class="form-content" id="employee-selection-form">
                <div class="grid-group">
                    <div class="form-group">
                        <label for="branch_filter">Branch</label>
                        <select name="branch_filter" id="branch_filter" class="form-control">
                            <option value="" selected>All Branches</option>
                            <!-- Populated dynamically -->
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

        <!-- Step 2: Payroll Details -->
        <div id="step2" class="step" style="display: none;">
            <h3>Step 2: Add Payroll Details</h3>
            <form class="form-content" id="create-payroll-form">
                @csrf
                <div class="grid-group">
                    <div class="form-group">
                        <label>Selected Employees</label>
                        <div id="selected-employees-list"></div>
                    </div>
                    <div class="form-group">
                        <label for="basic_salary">Basic Salary</label>
                        <input type="number" step="0.01" class="form-control" id="basic_salary" name="basic_salary" required>
                        <small class="text-danger error" id="error-basic_salary"></small>
                    </div>
                    <div class="form-group">
                        <label for="overtime_pay">Overtime Pay</label>
                        <input type="number" step="0.01" class="form-control" id="overtime_pay" name="overtime_pay">
                        <small class="text-danger error" id="error-overtime_pay"></small>
                    </div>
                    <div class="form-group">
                        <label for="deductions">Deductions</label>
                        <input type="number" step="0.01" class="form-control" id="deductions" name="deductions">
                        <small class="text-danger error" id="error-deductions"></small>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                        <small class="text-danger error" id="error-start_date"></small>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                        <small class="text-danger error" id="error-end_date"></small>
                    </div>
                </div>
                <div class="buttons">
                    <button type="button" id="back-to-step1" class="btn btn-secondary">Back</button>
                    <button type="submit" class="btn btn-primary">Add Payroll</button>
                </div>
                <span id="create-status" class="status-message"></span>
            </form>
        </div>

        <!-- Payroll List -->
        <div class="form-content payroll-list">
            <h3>Payroll Records</h3>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Basic Salary</th>
                        <th>Overtime</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="payroll-body">
                    <tr><td colspan="8">Loading...</td></tr>
                </tbody>
            </table>
            <div id="payroll-pagination" class="pagination-controls"></div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="edit-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">×</span>
            <h3>Edit Payroll</h3>
            <form id="edit-payroll-form">
                @csrf
                <input type="hidden" id="edit-id" name="id">
                <div class="grid-group">
                    <div class="form-group">
                        <label for="edit-employee_id">Employee ID</label>
                        <input type="text" class="form-control" id="edit-employee_id" name="employee_id" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit-basic_salary">Basic Salary</label>
                        <input type="number" step="0.01" class="form-control" id="edit-basic_salary" name="basic_salary" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-overtime_pay">Overtime Pay</label>
                        <input type="number" step="0.01" class="form-control" id="edit-overtime_pay" name="overtime_pay">
                    </div>
                    <div class="form-group">
                        <label for="edit-deductions">Deductions</label>
                        <input type="number" step="0.01" class="form-control" id="edit-deductions" name="deductions">
                    </div>
                    <div class="form-group">
                        <label for="edit-start_date">Start Date</label>
                        <input type="date" class="form-control" id="edit-start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-end_date">End Date</label>
                        <input type="date" class="form-control" id="edit-end_date" name="end_date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-status">Status</label>
                        <select class="form-control" id="edit-status" name="status">
                            <option value="pending">Pending</option>
                            <option value="processed">Processed</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <span id="edit-status" class="status-message"></span>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function () {
            let selectedEmployees = [];
            let currentPage = 1;

            // Fetch Branches
            function fetchBranches() {
                axios.get('/branches/filter') // Assuming this endpoint exists
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
                    .catch(error => console.error("Error fetching branches:", error));
            }

            // Render Pagination for Employees
            function renderEmployeePagination(currentPage, lastPage) {
                const paginationWrapper = $("#pagination");
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
                        currentPage = $(this).data("page");
                        fetchEmployees(currentPage);
                    }
                });
            }

            // Fetch Employees
            function fetchEmployees(page = 1) {
                let searchQuery = $('#employee_search').val().trim();
                let branch = $('#branch_filter').val();

                let url = branch ? '/employees/by-branch' : '/employees';
                let params = { search: searchQuery };
                if (branch) params.branch = branch;
                else params.page = page;

                axios.get(url, { params })
                    .then(response => {
                        let employees = response.data.data.data || response.data.data;
                        let lastPage = response.data.data ? response.data.data.last_page : 1;

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
                                        ${emp.first_name} ${emp.last_name} (${emp.employee_id})
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

                        if (!branch) {
                            renderEmployeePagination(page, lastPage);
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

            // Step Navigation
            $('#next-to-step2').click(function () {
                selectedEmployees = $('.employee-checkbox:checked').map(function() {
                    return this.value;
                }).get();

                if (!selectedEmployees || selectedEmployees.length === 0) {
                    $('#error-selected_employees').text('Please select at least one employee.');
                    return;
                }

                $('#selected-employees-list').empty();
                selectedEmployees.forEach(id => {
                    const emp = $('.employee-checkbox[value="' + id + '"]').parent().text().trim();
                    $('#selected-employees-list').append(`<p>${emp}</p>`);
                });

                $('#step1').hide();
                $('#step2').show();
            });

            $('#back-to-step1').click(function () {
                $('#step2').hide();
                $('#step1').show();
            });

            // Create Payroll
            $('#create-payroll-form').submit(function (e) {
                e.preventDefault();
                $('.error').text('');
                const formData = {
                    employees: selectedEmployees,
                    start_date: $('#start_date').val(),
                    end_date: $('#end_date').val(),
                    _token: $('input[name="_token"]').val()
                };

                $('#create-status').text('Saving...').css('color', 'blue');
                axios.post('/payrolls/batch', formData)
                    .then(response => {
                        $('#create-status').text('Payroll added successfully').css('color', 'green');
                        $('#create-payroll-form')[0].reset();
                        $('#step2').hide();
                        $('#step1').show();
                        selectedEmployees = [];
                        fetchPayrolls();
                    })
                    .catch(error => {
                        if (error.response && error.response.data.errors) {
                            let errors = error.response.data.errors;
                            for (let field in errors) {
                                $(`#error-${field}`).text(errors[field][0]);
                            }
                        } else {
                            $('#create-status').text('Error adding payroll').css('color', 'red');
                        }
                    });
            });

            // Fetch Payrolls
            // Fetch Payrolls
            window.fetchPayrolls = function (page = 1) {
                axios.get(`/payrolls/list?page=${page}`)
                    .then(response => {
                        if (response.data.status === 'success') {
                            const payrolls = response.data.data.data;
                            const lastPage = response.data.data.last_page;
                            const currentPage = response.data.data.current_page;

                            const payrollTable = $("#payroll-body");
                            payrollTable.empty();

                            if (payrolls.length === 0) {
                                payrollTable.append("<tr><td colspan='8'>No payrolls found.</td></tr>");
                            } else {
                                payrolls.forEach(payroll => {
                                    // Format start_date and end_date to mm-dd-year
                                    const startDate = new Date(payroll.start_date);
                                    const endDate = new Date(payroll.end_date);

                                    const formattedStartDate = `${(startDate.getMonth() + 1).toString().padStart(2, '0')}-${startDate.getDate().toString().padStart(2, '0')}-${startDate.getFullYear()}`;
                                    const formattedEndDate = `${(endDate.getMonth() + 1).toString().padStart(2, '0')}-${endDate.getDate().toString().padStart(2, '0')}-${endDate.getFullYear()}`;

                                    payrollTable.append(`
                                        <tr>
                                            <td>${payroll.employee ? payroll.employee.first_name + ' ' + payroll.employee.last_name : payroll.employee_id}</td>
                                            <td>P${payroll.basic_salary}</td>
                                            <td>P${payroll.overtime_pay}</td>
                                            <td>P${payroll.deductions}</td>
                                            <td>P${payroll.net_salary}</td>
                                            <td>${formattedStartDate} to ${formattedEndDate}</td>
                                            <td>${payroll.status}</td>
                                            <td>
                                                <button class="btn btn-warning edit-btn" data-id="${payroll.id}">Edit</button>
                                                <button class="btn btn-danger delete-btn" data-id="${payroll.id}">Delete</button>
                                            </td>
                                        </tr>
                                    `);
                                });
                            }
                            renderPayrollPagination(currentPage, lastPage);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching payrolls:', error);
                        $("#payroll-body").html("<tr><td colspan='8'>Error loading payrolls.</td></tr>");
                    });
            };

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

            // Edit Payroll
            $(document).on('click', '.edit-btn', function () {
                const id = $(this).data('id');
                axios.get(`/payrolls/${id}`)
                    .then(response => {
                        const payroll = response.data.data;

                        // Parse the dates
                        const startDate = new Date(payroll.start_date);
                        const endDate = new Date(payroll.end_date);

                        // Format dates for the date input (YYYY-MM-DD)
                        const formattedStartDateForInput = `${startDate.getFullYear()}-${(startDate.getMonth() + 1).toString().padStart(2, '0')}-${startDate.getDate().toString().padStart(2, '0')}`;
                        const formattedEndDateForInput = `${endDate.getFullYear()}-${(endDate.getMonth() + 1).toString().padStart(2, '0')}-${endDate.getDate().toString().padStart(2, '0')}`;

                        // Populate the edit modal fields
                        $('#edit-id').val(payroll.id);
                        $('#edit-employee_id').val(payroll.employee_id);
                        $('#edit-basic_salary').val(payroll.basic_salary);
                        $('#edit-overtime_pay').val(payroll.overtime_pay);
                        $('#edit-deductions').val(payroll.deductions);
                        $('#edit-start_date').val(formattedStartDateForInput);
                        $('#edit-end_date').val(formattedEndDateForInput);
                        $('#edit-status').val(payroll.status);
                        $('#edit-modal').show();
                    })
                    .catch(error => console.error('Error fetching payroll:', error));
            });

            $('#edit-payroll-form').submit(function (e) {
    e.preventDefault();
    const id = $('#edit-id').val();
    const formData = {
        basic_salary: parseFloat($('#edit-basic_salary').val()) || 0,
        overtime_pay: parseFloat($('#edit-overtime_pay').val()) || 0,
        deductions: parseFloat($('#edit-deductions').val()) || 0,
        start_date: $('#edit-start_date').val(),
        end_date: $('#edit-end_date').val(),
        status: $('#edit-status').val(),
        _token: $('input[name="_token"]').val()
    };

    // Log the data being sent for debugging
    console.log('Submitting update for payroll:', formData);

    $('#edit-status').text('Saving...').css('color', 'blue');
    axios.put(`/payrolls/${id}`, formData)
        .then(response => {
            $('#edit-status').text('Payroll updated successfully').css('color', 'green');
            $('#edit-modal').hide();
            fetchPayrolls();
        })
        .catch(error => {
            console.error('Error updating payroll:', error);
            if (error.response && error.response.data.errors) {
                $('#edit-status').text('Validation error: ' + JSON.stringify(error.response.data.errors)).css('color', 'red');
            } else {
                $('#edit-status').text('Error updating payroll').css('color', 'red');
            }
        });
});

            // Close Modal
            $('.close').click(function () {
                $('#edit-modal').hide();
            });

            // Delete Payroll
            $(document).on('click', '.delete-btn', function () {
                if (confirm('Are you sure you want to delete this payroll?')) {
                    const id = $(this).data('id');
                    axios.delete(`/payrolls/${id}`, {
                        headers: {
                            'X-CSRF-TOKEN': $('input[name="_token"]').val()
                        }
                    })
                        .then(response => {
                            fetchPayrolls();
                        })
                        .catch(error => console.error('Error deleting payroll:', error));
                }
            });

            fetchBranches();
            fetchEmployees();
            fetchPayrolls();

            $('#employee_search, #branch_filter').on('input change', function() {
                currentPage = 1;
                fetchEmployees();
            });
        });
    </script>

    <style>
        .form { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .form a { display: inline-flex; align-items: center; margin-bottom: 20px; text-decoration: none; color: #000; }
        .form a svg { margin-right: 5px; }
        .form-content { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .grid-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .text-danger { color: #dc3545; font-size: 0.8em; }
        .btn-primary { background-color: rgb(200, 0, 0); color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; max-width: 200px; }
        .btn-primary:hover { background-color: rgb(180, 0, 0); }
        .btn-secondary { background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; max-width: 200px; }
        .btn-warning { background-color: #ffc107; color: #000; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-danger { background-color: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; }
        .status-message { margin-left: 10px; font-size: 14px; }
        h3 { margin-top: 20px; margin-bottom: 10px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .pagination-controls, .pagination-wrapper { margin-top: 20px; text-align: center; }
        .pagination-link { padding: 5px 10px; margin: 0 2px; border: 1px solid #ccc; background-color: #fff; cursor: pointer; }
        .pagination-link.active { background-color: #007bff; color: white; }
        .pagination-link.disabled { background-color: #e9ecef; cursor: not-allowed; }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 20px; border-radius: 8px; width: 80%; max-width: 600px; }
        .close { float: right; font-size: 20px; cursor: pointer; }
        .checkbox-container { max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 4px; }
        .checkbox-container label { display: block; margin-bottom: 5px; }
        .buttons { display: flex; justify-content: center; gap: 10px; margin-top: 20px; }
    </style>
@endsection