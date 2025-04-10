@extends('app')

@section('content')
    <div class="content">
        <h2>Branch List</h2>
        <a href="/branch/new" class="back btn-primary">New Branch</a>
        <table>
            <thead>
                <tr>
                    <th>Branch ID</th>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Contact Number</th>
                    <th>Branch Manager</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="branch-body">
                <tr><td colspan="6">Loading...</td></tr>
            </tbody>
        </table>
        <div id="pagination" class="pagination-controls"></div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    window.fetchBranches = function (page = 1) {
        axios.get(`/api/branches?page=${page}`)
            .then(response => {
                if (response.data.data) {
                    let branches = response.data.data.data;
                    let lastPage = response.data.data.last_page;
                    let currentPage = response.data.data.current_page;

                    let branchTable = $("#branch-body");
                    branchTable.empty();

                    if (branches.length === 0) {
                        branchTable.append("<tr><td colspan='6'>No branches found.</td></tr>");
                    } else {
                        branches.forEach(branch => {
                            branchTable.append(`
                                <tr>
                                    <td>${branch.branch_id}</td>
                                    <td>${branch.branch_name}</td>
                                    <td>${branch.location}</td>
                                    <td>${branch.contact_number}</td>
                                    <td>${branch.branch_manager}</td>
                                    <td>
                                        <a href="/branch-update/${branch.id}"><img src="{{ asset('images/edit.svg' )}}" alt="Edit" /></a>
                                        <a onclick="deleteBranch(${branch.id})">Delete</a>
                                    </td>
                                </tr>
                            `);
                        });
                    }

                    renderPagination(currentPage, lastPage, fetchBranches);
                } else {
                    console.error("Unexpected API response format:", response.data);
                }
            })
            .catch(error => {
                console.error("Error fetching branches:", error);
                $("#branch-body").html("<tr><td colspan='6'>Error loading branches.</td></tr>");
            });
    };

    function renderPagination(currentPage, lastPage, onPageClick) {
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
            let page = $(this).data("page");
            if (!$(this).hasClass("disabled")) {
                onPageClick(page);
            }
        });
    }

    function deleteBranch(id) {
        if (confirm("Are you sure you want to delete this branch?")) {
            axios.delete(`/api/branches/${id}`)
                .then(response => {
                    alert("Branch deleted successfully!");
                    fetchBranches();
                })
                .catch(error => {
                    console.error("Error deleting branch:", error);
                    alert("Failed to delete branch.");
                });
        }
    }

    $(document).ready(function () {
        if ($("#branch-body").length) {
            fetchBranches();
        }
    });
</script>

@endsection
