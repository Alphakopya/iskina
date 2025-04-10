@extends('app')

@section('content')
    <div class="form">
        <h2>Update Fingerprint</h2>

        <!-- Step 1: Confirm Deletion -->
        <div id="step1" class="step">
            <h3>Step 1: Confirm Fingerprint Deletion</h3>
            <div class="form-content">
                <div class="form-group">
                    <label>Selected Employee</label>
                    <p id="selected-employee-name" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label>Fingerprint ID</label>
                    <p id="fingerprint-id" class="text-muted">{{ request()->query('fingerprint_id') }}</p>
                </div>
                <div class="form-group">
                    <label>Confirmation</label>
                    <p>Are you sure you want to delete the existing fingerprint? This will allow you to enroll a new one.</p>
                </div>
                <div class="buttons">
                    <button type="button" id="confirm-delete" class="btn btn-danger">Delete Fingerprint</button>
                    <button type="button" id="cancel" class="btn btn-secondary">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Step 2: Fingerprint Registration -->
        <div id="step2" class="step" style="display: none;">
            <h3>Step 2: Register New Fingerprint</h3>
            <div class="form-content">
                <div class="form-group">
                    <label>Selected Employee</label>
                    <p id="selected-employee-name-step2" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label>Assigned Fingerprint ID</label>
                    <p id="fingerprint-id-step2" class="text-muted"></p>
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
                <span class="close">×</span>
                <h3>Enrollment Feedback</h3>
                <p id="modal-message"></p>
            </div>
        </div>
    </div>

    <style>
        .form { padding: 20px; }
        .btn-primary { padding: 8px 16px; background-color: rgb(200, 0, 0); color: white; border-radius: 4px; margin: 5px; }
        .btn-danger { padding: 8px 16px; background-color: #dc3545; color: white; border-radius: 4px; margin: 5px; }
        .btn-secondary { padding: 8px 16px; background-color: #6c757d; color: white; border-radius: 4px; margin: 5px; }
        .form-group { margin-bottom: 15px; }
        .buttons { display: flex; justify-content: center; gap: 10px; margin-top: 20px; }
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
            let fingerprintId = '{{ request()->query('fingerprint_id') }}';
            let pollingInterval = null;
            let seenStatuses = new Set();

            // Load initial data
            axios.get(`/api/fingerprints?fingerprint_id=${fingerprintId}`)
                .then(response => {
                    let fingerprint = response.data.data.data[0];
                    selectedEmployee = fingerprint.employee_id;
                    axios.get(`/api/employees/${selectedEmployee}`)
                        .then(response => {
                            $('#selected-employee-name').text(response.data.data.first_name + ' ' + response.data.data.last_name);
                            $('#selected-employee-name-step2').text(response.data.data.first_name + ' ' + response.data.data.last_name);
                            $('#fingerprint-id-step2').text(fingerprintId);
                        });
                });

            $('#confirm-delete').click(function () {
                if (confirm('Are you sure you want to delete this fingerprint? This will reset it for re-enrollment.')) {
                    axios.post('/fingerprint/delete', { employee_id: selectedEmployee })
                        .then(response => {
                            console.log('Fingerprint deleted:', response.data);
                            $('#step1').hide();
                            $('#step2').show();
                            seenStatuses.clear();
                            axios.post('/fingerprint/register', { employee_id: selectedEmployee })
                                .then(() => {
                                    console.log('Registration initiated');
                                    pollEnrollmentStatus(fingerprintId);
                                })
                                .catch(error => {
                                    console.error('Error initiating registration:', error);
                                    alert('Error initiating registration: ' + (error.response?.data?.message || 'Unknown error'));
                                });
                        })
                        .catch(error => {
                            console.error('Error deleting fingerprint:', error);
                            alert('Error deleting fingerprint: ' + (error.response?.data?.message || 'Unknown error'));
                        });
                }
            });

            $('#cancel').click(function () {
                window.location.href = '/fingerprint/list';
            });

            $('#back-to-step1').click(function () {
                if (selectedEmployee) {
                    axios.post('/fingerprint/unselect', { employee_id: selectedEmployee })
                        .then(() => {
                            console.log('Employee unselected, fingerprint_select set to 0');
                            selectedEmployee = null;
                            $('#step2').hide();
                            $('#step1').show();
                            if (pollingInterval) {
                                clearInterval(pollingInterval);
                                pollingInterval = null;
                            }
                            seenStatuses.clear();
                            hideModal();
                        })
                        .catch(error => {
                            console.error('Error unselecting employee:', error);
                            alert('Error unselecting employee: ' + (error.response?.data?.message || 'Unknown error'));
                        });
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
                }, 10);
                setTimeout(() => {
                    console.log('Triggering hideModal');
                    hideModal();
                }, 1000);
            }

            function hideModal() {
                console.log('Hiding modal');
                $('#enrollment-modal').removeClass('active');
                setTimeout(() => {
                    $('#enrollment-modal').css('display', 'none');
                    console.log('Modal fully hidden');
                }, 300);
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
                                seenStatuses.add(message);
                                showModal(message);
                            } else if (!message) {
                                console.log('No message received in response');
                            } else {
                                console.log('Message already shown:', message);
                            }

                            if (status === 'success') {
                                console.log('Success detected, stopping poll after popup');
                                clearInterval(pollingInterval);
                                pollingInterval = null;
                                setTimeout(() => window.location.href = '/fingerprint/list', 1300);
                            } else if (status === 'error') {
                                console.log('Error detected, stopping poll');
                                clearInterval(pollingInterval);
                                pollingInterval = null;
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
                }, 1500);
            }
        });
    </script>
@endsection