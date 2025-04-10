@extends('app')

@section('content')
    <div class="content">
        <h2>Fingerprints</h2>
        <a href="/fingerprint/new" class="back btn-primary">New Fingerprint</a>
        <table>
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th>Fingerprint ID</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="fingerprint-body">
                <tr><td colspan="5">Loading...</td></tr>
            </tbody>
        </table>
        <div id="pagination" class="pagination-controls"></div>
    </div>

    <style>
        .content { padding: 20px; }
        .btn-primary { padding: 8px 16px; background-color: rgb(200, 0, 0); color: white; border-radius: 4px; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        .btn-warning { padding: 5px 10px; border-radius: 4px; display: inline-block; }
        .btn-warning img { width: 16px; height: 16px; }
        .pagination-controls { margin-top: 20px; text-align: center; }
        .pagination-link { padding: 10px 15px; margin: 0 5px; border: 1px solid #000000; background-color: #fff; cursor: pointer; }
        .pagination-link.active { background-color: rgb(200, 0, 0); color: white;}
        .pagination-link.disabled { background-color: #e9ecef; cursor: not-allowed; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        window.fetchFingerprints = function (page = 1) {
            axios.get(`/api/fingerprints?page=${page}&mode=attendance`)
                .then(response => {
                    if (response.data.data) {
                        let fingerprints = response.data.data.data;
                        let lastPage = response.data.data.last_page;
                        let currentPage = response.data.data.current_page;

                        let fingerprintTable = $("#fingerprint-body");
                        fingerprintTable.empty();

                        if (fingerprints.length === 0) {
                            fingerprintTable.append("<tr><td colspan='5'>No fingerprints found in attendance mode.</td></tr>");
                        } else {
                            fingerprints.forEach(fingerprint => {
                                fingerprintTable.append(`
                                    <tr>
                                        <td>${fingerprint.employee_id}</td>
                                        <td>${fingerprint.employee ? fingerprint.employee.first_name + ' ' + fingerprint.employee.last_name : 'N/A'}</td>
                                        <td>${fingerprint.fingerprint_id || 'Not Assigned'}</td>
                                        <td>
                                            <a href="#" class="btn btn-warning edit-btn" data-fingerprint-id="${fingerprint.fingerprint_id}">
                                                <img src="{{ asset('images/edit.svg') }}" alt="Edit" />
                                            </a>
                                        </td>
                                    </tr>
                                `);
                            });
                        }

                        renderPagination(currentPage, lastPage);

                        // Attach edit button handlers
                        $('.edit-btn').off('click').on('click', function (e) {
                            e.preventDefault();
                            const fingerprintId = $(this).data('fingerprint-id');
                            axios.post('/fingerprint/select-for-edit', { fingerprint_id: fingerprintId })
                                .then(response => {
                                    console.log('Fingerprint selected for edit:', response.data);
                                    window.location.href = '/fingerprint/edit?fingerprint_id=' + fingerprintId;
                                })
                                .catch(error => {
                                    console.error('Error selecting fingerprint:', error);
                                    alert('Error selecting fingerprint: ' + (error.response?.data?.message || 'Unknown error'));
                                });
                        });
                    } else {
                        console.error("Unexpected API response format:", response.data);
                    }
                })
                .catch(error => {
                    console.error("Error fetching fingerprints:", error);
                    $("#fingerprint-body").html("<tr><td colspan='5'>Error loading fingerprints.</td></tr>");
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
                    fetchFingerprints(page);
                }
            });
        }

        $(document).ready(function () {
            if ($("#fingerprint-body").length) {
                fetchFingerprints();
            }
        });
    </script>
@endsection