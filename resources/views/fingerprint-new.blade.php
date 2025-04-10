@extends('app')

@section('content')
    <div class="form">
        <a href="/fingerprint/list"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M400-80 0-480l400-400 71 71-329 329 329 329-71 71Z"/></svg>Back to Fingerprint List</a>
        <h2>Register New Fingerprint</h2>

        <!-- Step 1: Employee Selection -->
        <div id="step1" class="step">
            <h3>Step 1: Select Employee</h3>
            <form class="form-content" id="employee-selection-form">
                <div class="grid-group">
                    <div class="form-group">
                        <label for="branch_filter">Branch</label>
                        <select name="branch_filter" id="branch_filter" class="form-control">
                            <option value="" selected>All Branches</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="employee_search">Search Employee</label>
                        <input type="text" name="employee_search" id="employee_search" class="form-control" placeholder="Enter name or ID">
                    </div>
                </div>
                <div class="form-group">
                    <label>Employees (Select One)</label>
                    <div id="employee-radios" class="radio-container">
                        <!-- Radio buttons populated dynamically -->
                    </div>
                    <small class="text-danger error" id="error-selected_employee"></small>
                </div>
                <div id="pagination" class="pagination-wrapper" style="display: none;"></div>
                <button type="button" id="register-btn" class="btn btn-primary">Register</button>
            </form>
        </div>

        <!-- Step 2: Fingerprint Registration Instructions -->
        <div id="step2" class="step" style="display: none;">
            <h3>Step 2: Register Fingerprint</h3>
            <div class="form-content">
                <div class="form-group">
                    <label>Selected Employee</label>
                    <p id="selected-employee-name" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label>Assigned Fingerprint ID</label>
                    <p id="fingerprint-id" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label>Instructions</label>
                    <p>Place the employee's finger on the scanner. The device is in Enroll Mode.</p>
                    <small class="text-muted">Status updates will appear as popups once per step.</small>
                </div>
                <div class="buttons">
                    <button type="button" id="back-to-step1" class="btn btn-secondary">Back</button>
                </div>
            </div>
        </div>

        <!-- Modal for Feedback -->
        <div id="enrollment-modal" class="modal">
            <div class="modal-content">
                <h3>Enrollment Feedback</h3>
                <p id="modal-message"></p>
            </div>
        </div>
    </div>

    <style>
        .form { padding: 20px; }
        .btn-primary { padding: 8px 16px; background-color: rgb(200, 0, 0); color: white; border-radius: 4px; margin: 5px; }
        .btn-secondary { padding: 8px 16px; background-color: #6c757d; color: white; border-radius: 4px; margin: 5px; }
        .grid-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .radio-container { max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 4px; }
        .radio-container label { display: block; margin-bottom: 5px; }
        .buttons { display: flex; justify-content: center; margin-top: 20px; }
        .pagination-wrapper { margin-top: 10px; text-align: center; }
        .pagination-link { padding: 5px 10px; margin: 0 5px; border: 1px solid #ccc; background-color: #fff; cursor: pointer; }
        .pagination-link.active { background-color: #007bff; color: white; border-color: #007bff; }
        .pagination-link.disabled { background-color: #e9ecef; cursor: not-allowed; }
        .modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;
            display: none; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease-in-out;
        }
        .modal.active {
            display: flex; opacity: 1;
        }
        .modal-content {
            background: white; padding: 20px; border-radius: 5px; width: 300px; text-align: center;
            transform: scale(0.8); transition: transform 0.3s ease-in-out;
        }
        .modal.active .modal-content {
            transform: scale(1);
        }
        .close { float: right; font-size: 20px; cursor: pointer; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            let selectedEmployee = null;
            let currentPage = 1;
            let pollingInterval = null;
            let seenStatuses = new Set(); // Track displayed statuses

            function fetchBranches() {
                axios.get('/branches/filter')
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

            function renderPagination(currentPage, lastPage) {
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

            function fetchEmployees(page = 1) {
                let searchQuery = $('#employee_search').val().trim();
                let branch = $('#branch_filter').val();
                let url = '/fingerprint';
                let params = { search: searchQuery, page: page, branch: branch };

                axios.get(url, { params })
                    .then(response => {
                        console.log('Raw API Response:', response);
                        let fingerprints = response.data.data.data;
                        console.log(fingerprints);
                        if (!Array.isArray(fingerprints)) {
                            console.warn('Fingerprints is not an array:', fingerprints);
                            fingerprints = [];
                        }
                        console.log('Extracted Fingerprints:', fingerprints);

                        let employees = fingerprints.filter(f => f.add_fingerid === 0);
                        $('#employee-radios').empty();
                        if (employees.length > 0) {
                            employees.forEach(emp => {
                                const isChecked = selectedEmployee === emp.employee_id ? 'checked' : '';
                                $('#employee-radios').append(`
                                    <label>
                                        <input type="radio" 
                                               name="selected_employee" 
                                               value="${emp.employee_id}"
                                               class="employee-radio" ${isChecked}>
                                        ${emp.name} (${emp.branch ?? 'N/A'})
                                    </label>
                                `);
                            });
                        } else {
                            $('#employee-radios').append('<p>No employees in enroll mode found.</p>');
                        }

                        $('#employee-radios').off('change').on('change', '.employee-radio', function() {
                            selectedEmployee = $(this).val();
                        });

                        renderPagination(page, response.data.data.last_page);
                        $('#pagination').show();
                    })
                    .catch(error => {
                        console.error("Error fetching fingerprints:", error.response || error);
                        $('#employee-radios').empty().append('<p>Error loading employees.</p>');
                    });
            }

            fetchBranches();
            fetchEmployees();

            function initializeEnrollMode() {
                axios.get('/api/fingerprints', { params: { mode: 'enroll' } })
                    .then(response => {
                        let fingerprints = response.data.data.data;
                        if (fingerprints.length > 0) {
                            selectedEmployee = fingerprints[0].employee_id;
                            axios.post('/fingerprint/register', { employee_id: selectedEmployee })
                                .then(() => console.log('Enrollment mode activated on page load'));
                        }
                    });
            }
            initializeEnrollMode();

            $('#employee_search, #branch_filter').on('input change', function() {
                currentPage = 1;
                fetchEmployees();
            });

            $('#register-btn').click(function () {
                selectedEmployee = $('.employee-radio:checked').val();
                if (!selectedEmployee) {
                    $('#error-selected_employee').text('Please select an employee.');
                    return;
                }
                axios.post('/fingerprint/select', { employee_id: selectedEmployee })
                    .then(() => {
                        axios.get(`/fingerprint/employees/${selectedEmployee}`)
                            .then(response => {
                                const employeeData = response.data.data;
                                const fingerprint = response.data.fingerprint;
                                $('#selected-employee-name').text(employeeData.first_name + ' ' + employeeData.last_name);
                                
                                
                                if (fingerprint) {
                                    $('#fingerprint-id').text(fingerprint.fingerprint_id);
                                    $('#step1').hide();
                                    $('#step2').show();
                                    axios.post('/fingerprint/register', { employee_id: selectedEmployee })
                                        .then(() => {
                                            console.log('Registration initiated');
                                            seenStatuses.clear(); // Reset seen statuses for new enrollment
                                            pollEnrollmentStatus(fingerprint.fingerprint_id);
                                        })
                                        .catch(error => {
                                            console.error('Registration error:', error);
                                            alert('Error initiating fingerprint registration: ' + (error.response?.data?.message || 'Unknown error'));
                                        });
                                } else {
                                    alert('No fingerprint found for this employee');
                                }
                            })
                            .catch(error => {
                                alert('Error fetching employee data: ' + (error.response?.data?.message || 'Unknown error'));
                            });
                    })
                    .catch(error => {
                        alert('Error selecting employee: ' + (error.response?.data?.message || 'Unknown error'));
                    });
            });

            $('#back-to-step1').click(function () {
                if (selectedEmployee) {
                    axios.post('/fingerprint/unselect', { employee_id: selectedEmployee })
                        .then(() => {
                            console.log('Employee unselected, fingerprint_select set to 0');
                            selectedEmployee = null;
                            $('.employee-radio').prop('checked', false);
                            $('#step2').hide();
                            $('#step1').show();
                            if (pollingInterval) {
                                clearInterval(pollingInterval);
                                pollingInterval = null;
                            }
                            seenStatuses.clear(); // Clear seen statuses when going back
                            hideModal();
                        })
                        .catch(error => {
                            console.error('Error unselecting employee:', error);
                            alert('Error unselecting employee: ' + (error.response?.data?.message || 'Unknown error'));
                        });
                } else {
                    $('#step2').hide();
                    $('#step1').show();
                    if (pollingInterval) {
                        clearInterval(pollingInterval);
                        pollingInterval = null;
                    }
                    seenStatuses.clear();
                    hideModal();
                }
            });

            function showModal(message) {
                console.log('Showing modal with message:', message);
                $('#modal-message').text(message);
                $('#enrollment-modal').css('display', 'flex');
                $('#enrollment-modal').removeClass('active');
                setTimeout(() => {
                    console.log('Adding active class to modal');
                    $('#enrollment-modal').addClass('active');
                }, 10); // Fade in
                setTimeout(() => {
                    console.log('Triggering hideModal');
                    hideModal();
                }, 2000); // Hide after 1 second
            }

            function hideModal() {
                console.log('Hiding modal');
                $('#enrollment-modal').removeClass('active');
                setTimeout(() => {
                    $('#enrollment-modal').css('display', 'none');
                    console.log('Modal fully hidden');
                }, 300); // Wait for fade-out
            }

            $('.close').click(function() {
                hideModal();
            });

            function pollEnrollmentStatus(fingerprintId) {
                console.log('Starting polling for fingerprint ID:', fingerprintId);
                pollingInterval = setInterval(() => {
                    axios.get(`/fingerprint/status?fingerprint_id=${fingerprintId}`)
                        .then(response => {
                            const { status, message } = response.data;
                            console.log('Poll response:', { status, message, timestamp: new Date().toISOString() });

                            if (message && !seenStatuses.has(message)) {
                                seenStatuses.add(message); // Mark this message as seen
                                showModal(message); // Show popup for 1 second
                            } else if (!message) {
                                console.log('No message received in response');
                            } else {
                                console.log('Message already shown:', message);
                            }

                            if (status === 'success') {
                                console.log('Success detected, stopping poll after popup');
                                clearInterval(pollingInterval);
                                pollingInterval = null; 
                                setTimeout(() => window.location.href = '/fingerprint/new', 1300); // Redirect after popup
                            } else if (status === 'error') {
                                console.log('Continuing poll for status:', status);
                            } else {
                                console.log('Continuing poll for status:', status);
                            }
                        })
                        .catch(error => {
                            console.error('Polling error:', error.response ? error.response.data : error);
                            clearInterval(pollingInterval);
                            pollingInterval = null;
                            hideModal();
                        });
                }, 1500); // Poll every 1.5 seconds
            }
        });
    </script>
@endsection