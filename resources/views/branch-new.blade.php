@extends('app')

@section('content')
    <div class="form">
        <a href="/branch"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M400-80 0-480l400-400 71 71-329 329 329 329-71 71Z"/></svg>Back to Branch List</a>
        <h2>New Branch</h2>
        <form class="form-content" id="branch-form">
            <div class="grid-group">
                <div class="form-group">
                    <label for="branch_name">Branch Name</label>
                    <input type="text" name="branch_name" id="branch_name" class="form-control" required>
                    <small class="text-danger error" id="error-branch_name"></small>
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" name="location" id="location" class="form-control" required>
                    <small class="text-danger error" id="error-location"></small>
                </div>
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="text" name="contact_number" id="contact_number" class="form-control" required>
                    <small class="text-danger error" id="error-contact_number"></small>
                </div>
                <div class="form-group">
                    <label for="manager">Branch Manager</label>
                    <input type="text" name="manager" id="manager" class="form-control" required>
                    <small class="text-danger error" id="error-manager"></small>
                </div>
            </div>

            <button type="submit" id="submit-btn" class="btn btn-primary">Add Branch</button>
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
                    } else {
                        $(this).removeClass("has-text");
                    }
                });
            }

            // Check on page load
            checkInput();

            // Check when user types or selects an option
            $(".form-control").on("input change", function () {
                checkInput();
            });
            $('#branch-form').on('submit', function (event) {
                event.preventDefault(); // Prevent page reload

                $('.error').text(''); // Clear previous errors

                let formData = {
                    branch_id: $('#branch_id').val(),
                    branch_name: $('#branch_name').val(),
                    location: $('#location').val(),
                    contact_number: $('#contact_number').val(),
                    branch_manager: $('#manager').val(),
                };

                axios.post('/api/branches', formData)
                    .then(response => {
                        alert('Branch added successfully!');
                        checkInput()
                        $('#branch-form')[0].reset(); // Reset form fields
                    })
                    .catch(error => {
                        if (error.response && error.response.data.errors) {
                            let errors = error.response.data.errors;
                            for (let field in errors) {
                                $(`#error-${field}`).text(errors[field][0]); // Show first error message
                            }
                        }
                    });
            });
        });
    </script>
@endsection
