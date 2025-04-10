@extends('app')

@section('content')
    <div class="form">
        <a href="/leaves"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M400-80 0-480l400-400 71 71-329 329 329 329-71 71Z"/></svg>Back to Leave & Request List</a>
        <h2>Leave & Recovery Request</h2>
        <form class="form-content" id="leaves-form">
            <div class="grid-group">
                <div class="form-group">
                    <label for="leave_type">Leave Type</label>
                    <select name="leave_type" id="leave_type" class="form-control" required>
                        <option value="" selected disabled>Select</option>
                        <option value="Sick Leave">Sick Leave</option>
                        <option value="Vacation">Vacation</option>
                        <option value="Maternity">Maternity</option>
                        <option value="Paternity ">Paternity</option>
                    </select>
                    <small class="text-danger error" id="error-leave_type"></small>
                </div>
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" required>
                    <small class="text-danger error" id="error-start_date"></small>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" required>
                    <small class="text-danger error" id="error-end_date"></small>
                </div>
            </div>
            <div class="form-group">
                <label for="reason_leave">Reason for leave</label>
                <textarea name="reason_leave" id="reason_leave" class="form-control" rows="5" required></textarea>
                <small class="text-danger error" id="error-reason_leave"></small>
            </div>
            <button type="submit" id="submit-btn" class="btn btn-primary">Add Request</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            function checkInput() {
                $(".form-control").each(function () {
                    let value = $(this).val();

                    // Ensure value exists before calling .trim()
                    if ($(this).is("input") && value && value.trim() !== "") {
                        $(this).addClass("has-text");
                    } else if ($(this).is("select") && value) {
                        $(this).addClass("has-text");
                    } else if ($(this).is("textarea") && value && value.trim() !== "") {
                        $(this).addClass("has-text");
                    } else {
                        $(this).removeClass("has-text");
                    }
                });
            }

            // Check on page load
            checkInput();

            let employee = {}; // Store employee data

            // Fetch Employee Info
            axios.get('/employee-info')
                .then(response => {
                    employee = response.data.employee;

                    $('#leaves-form').on('submit', function (event) {
                        event.preventDefault(); // Prevent page reload

                        $('.error').text(''); // Clear previous errors

                        if (!employee.employee_id) {
                            alert("Employee information is not loaded yet. Please try again.");
                            return;
                        }

                        let formData = {
                            employee_id: employee.employee_id,
                            leave_type: $('#leave_type').val(),
                            start_date: $('#start_date').val(),
                            end_date: $('#end_date').val(),
                            reason_leave: $('#reason_leave').val(),
                        };

                        axios.post('/api/leaves', formData)
                            .then(response => {
                                alert('Leave Request added successfully!');
                                $(".form-control").removeClass("has-text"); // Reset input styles
                                $('#leaves-form')[0].reset(); // Reset form fields
                            })
                            .catch(error => {
                                console.log(error.response.data);
                                if (error.response && error.response.data.errors) {
                                    let errors = error.response.data.errors;
                                    for (let field in errors) {
                                        $(`#error-${field}`).text(errors[field][0]); // Show first error message
                                    }
                                }
                            });
                    });
                })
                .catch(error => {
                    console.error("Error fetching employee details:", error);
                });
            // Check when user types or selects an option
            $(".form-control").on("input change", function () {
                checkInput();
            });
        });
    </script>
@endsection
