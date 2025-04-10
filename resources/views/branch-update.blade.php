@extends('app')

@section('content')
    <div class="form">
        <a href="/branch"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M400-80 0-480l400-400 71 71-329 329 329 329-71 71Z"/></svg>Back to Branch List</a>
        <h2>Update Branch</h2>
        <form id="branch-form">
            <div class="grid-group">
                <div class="form-group">
                    <label for="branch_id">Branch ID</label>
                    <input type="text" name="branch_id" id="branch_id" class="form-control" required>
                    <small class="text-danger error" id="error-branch_id"></small>
                </div>
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

            <button type="submit" id="submit-btn" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            const branchId = window.location.pathname.split("/").pop();

            if (branchId) {
                fetchBranchDetails(branchId);
            }

            function fetchBranchDetails(id) {
                axios.get(`/api/branches/${id}`)
                    .then(response => {
                        let branch = response.data.data;
                        
                        // Populate form fields with branch data
                        $('#branch_id').val(branch.branch_id);
                        $('#branch_name').val(branch.branch_name);
                        $('#location').val(branch.location);
                        $('#contact_number').val(branch.contact_number);
                        $('#manager').val(branch.branch_manager);
                    })
                    .catch(error => {
                        console.error("Error fetching branch details:", error);
                    });
            }

            $('#branch-form').on('submit', function (event) {
                event.preventDefault();
                $('.error').text('');

                let formData = {
                    branch_id: $('#branch_id').val(),
                    branch_name: $('#branch_name').val(),
                    location: $('#location').val(),
                    contact_number: $('#contact_number').val(),
                    branch_manager: $('#manager').val(),
                };

                axios.put(`/api/branches/${branchId}`, formData)
                    .then(response => {
                        alert('Branch updated successfully!');
                        window.location.href = "/branch"; // Redirect to list page
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
