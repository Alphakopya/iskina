@extends('app')

@section('content')
    <div class="form">
        <a href="/employee"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M400-80 0-480l400-400 71 71-329 329 329 329-71 71Z"/></svg>Back to Employee List</a>
        <h2>New Employee</h2>
        <form class="form-content" id="employee-form">
            <h3>Personal Information</h3>
            <div class="grid-group">
                <div class="form-group">
                    <label for="employee_id">Employee ID</label>
                    <input type="text" name="employee_id" id="employee_id" class="form-control" required>
                    <small class="text-danger error" id="error-employee_id"></small>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" name="first_name" id="first_name" class="form-control" required>
                    <small class="text-danger error" id="error-first_name"></small>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name" class="form-control" required>
                    <small class="text-danger error" id="error-last_name"></small>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                    <small class="text-danger error" id="error-email"></small>
                </div>
                <div class="form-group">
                    <label for="position_title">Position Title</label>
                    <input type="text" name="position_title" id="position_title" class="form-control" required>
                    <small class="text-danger error" id="error-position_title"></small>
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" name="dob" id="dob" class="form-control" required>
                    <small class="text-danger error" id="error-dob"></small>
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select name="gender" id="gender" class="form-control" required>
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                    <small class="text-danger error" id="error-gender"></small>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" name="address" id="address" class="form-control" required>
                    <small class="text-danger error" id="error-address"></small>
                </div>
            </div>

            <h3>Employee Details</h3>
            <div class="grid-group">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" required>
                    <small class="text-danger error" id="error-start_date"></small>
                </div>
                <div class="form-group">
                    <label for="branch">Branch</label>
                    <select name="branch" id="branch" class="form-control" required>
                        <option value="" selected disabled>Select Branch</option>
                        <!-- Options populated dynamically -->
                    </select>
                    <small class="text-danger error" id="error-branch"></small>
                </div>
                <div class="form-group">
                    <label for="supervisor">Supervisor</label>
                    <input type="text" name="supervisor" id="supervisor" class="form-control" required>
                    <small class="text-danger error" id="error-supervisor"></small>
                </div>
                <div class="form-group">
                    <label for="employee_type">Employee Type</label>
                    <select name="employee_type" id="employee_type" class="form-control" required>
                        <option value="" selected disabled>Select</option>
                        <option value="full_time">Full-time</option>
                        <option value="part_time">Part-time</option>
                        <option value="contract">Contract</option>
                    </select>
                    <small class="text-danger error" id="error-employee_type"></small>
                </div>
                <div class="form-group">
                    <label for="employee_status">Employee Status</label>
                    <select name="employee_status" id="employee_status" class="form-control" required>
                        <option value="" selected disabled>Select</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <small class="text-danger error" id="error-employee_status"></small>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select name="role" id="role" class="form-control" required>
                        <option value="" selected disabled>Select</option>
                        <option value="admin">Admin</option>
                        <option value="employee">Employee</option>
                        <option value="hr">HR</option>
                    </select>
                    <small class="text-danger error" id="error-role"></small>
                </div>
            </div>

            <button type="submit" id="submit-btn" class="btn btn-primary">Add Employee</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            // Load branches dynamically
            axios.get('/get-branches')
                .then(response => {
                    if (response.data.status === 'success' && response.data.data) {
                        const branches = response.data.data;
                        const branchSelect = $('#branch');
                        branches.forEach(branch => {
                            branchSelect.append(`<option value="${branch.branch_name}">${branch.branch_name}</option>`);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading branches:', error);
                    $('#error-branch').text('Failed to load branches.');
                });

            function checkInput() {
                $(".form-control").each(function () {
                    let value = $(this).val();
                    if ($(this).is("input") && value && value.trim() !== "") {
                        $(this).addClass("has-text");
                    } else if ($(this).is("select") && value) {
                        $(this).addClass("has-text");
                    } else {
                        $(this).removeClass("has-text");
                    }
                });
            }

            checkInput();
            $(".form-control").on("input change", function () {
                checkInput();
            });

            $('#employee-form').on('submit', function (event) {
                event.preventDefault();
                $('.error').text('');
                checkInput();
                let formData = {
                    employee_id: $('#employee_id').val(),
                    first_name: $('#first_name').val(),
                    last_name: $('#last_name').val(),
                    branch: $('#branch').val(),
                    email: $('#email').val(),
                    position_title: $('#position_title').val(),
                    date_of_birth: $('#dob').val(),
                    gender: $('#gender').val(),
                    address: $('#address').val(),
                    start_date: $('#start_date').val(),
                    supervisor: $('#supervisor').val(),
                    employee_type: $('#employee_type').val(),
                    employee_status: $('#employee_status').val(),
                    role: $('#role').val(),
                };

                axios.post('/employees', formData)
                    .then(response => {
                        alert('Employee added successfully!');
                        $('#employee-form')[0].reset();
                    })
                    .catch(error => {
                        if (error.response && error.response.data.errors) {
                            let errors = error.response.data.errors;
                            for (let field in errors) {
                                $(`#error-${field}`).text(errors[field][0]);
                            }
                        }
                    });
            });
        });
    </script>
@endsection