@extends('app')

@section('content')
    <div class="form">
        <h2>My Profile</h2>
        <form class="form-content" id="profile-form">
            @csrf

            <h3>Personal Information</h3>
            <div class="grid-group">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                    <small class="text-danger error" id="error-first_name"></small>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                    <small class="text-danger error" id="error-last_name"></small>
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                    <small class="text-danger error" id="error-date_of_birth"></small>
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select class="form-control" id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                    <small class="text-danger error" id="error-gender"></small>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" class="form-control" id="address" name="address">
                    <small class="text-danger error" id="error-address"></small>
                </div>
            </div>

            <h3>Security</h3>
            <div class="grid-group">
                <div class="form-group">
                    <label for="current_password">Current Password (required to save changes)</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                    <small class="text-danger error" id="error-current_password"></small>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <span id="status-message" class="status-message"></span>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function () {
            // Fetch current user profile data
            axios.get('/profile/show')
                .then(response => {
                    if (response.data.status === 'success') {
                        const data = response.data.data;
                        console.log(data); // Log the data for debugging
                        $('#first_name').val(data.first_name || '');
                        $('#last_name').val(data.last_name || '');
                        $('#date_of_birth').val(data.date_of_birth || '');
                        $('#gender').val(data.gender || '');
                        $('#address').val(data.address || '');
                        checkInput(); // Check initial input states
                    }
                })
                .catch(error => {
                    console.error('Error fetching profile:', error);
                    $('#status-message').text('Error loading profile data').css('color', 'red');
                });

            // Check input for styling
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

            $(".form-control").on("input change", function () {
                checkInput();
            });

            // Handle form submission
            $('#profile-form').submit(function (e) {
                e.preventDefault();
                $('.error').text('');
                const formData = {
                    first_name: $('#first_name').val(),
                    last_name: $('#last_name').val(),
                    date_of_birth: $('#date_of_birth').val(),
                    gender: $('#gender').val(),
                    address: $('#address').val(),
                    current_password: $('#current_password').val(),
                    _token: $('input[name="_token"]').val()
                };

                $('#status-message').text('Saving...').css('color', 'blue');
                
                axios.post('/profile/update', formData)
                    .then(response => {
                        if (response.data.status === 'success') {
                            $('#status-message').text('Profile updated successfully').css('color', 'green');
                            $('#current_password').val('');
                            checkInput();
                        }
                    })
                    .catch(error => {
                        console.error('Error updating profile:', error);
                        if (error.response && error.response.data.errors) {
                            let errors = error.response.data.errors;
                            for (let field in errors) {
                                $(`#error-${field}`).text(errors[field][0]);
                            }
                        } else {
                            $('#status-message').text(error.response?.data?.message || 'Error updating profile').css('color', 'red');
                        }
                    });
            });
        });
    </script>

    <style>
        .form {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .form a {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            text-decoration: none;
            color: #000;
        }
        .form a svg {
            margin-right: 5px;
        }
        .form-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .grid-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #007bff;
            outline: none;
        }
        .form-control.has-text {
            border-color: #28a745;
        }
        .text-danger {
            color: #dc3545;
            font-size: 0.8em;
        }
        .btn-primary {
            background-color: rgb(200, 0, 0);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            max-width: 200px;
        }
        .btn-primary:hover {
            background-color: rgb(180, 0, 0);
        }
        .status-message {
            margin-left: 10px;
            font-size: 14px;
        }
        h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            color: #333;
        }
    </style>
@endsection