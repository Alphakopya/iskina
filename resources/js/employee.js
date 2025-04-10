import { renderPagination } from "./pagination.js";

$(document).ready(function () {
    let currentPage = 1;
    let lastPage = 1;

    function fetchEmployees(page = 1) {
        axios.get(`/api/employees?page=${page}`)
            .then(response => {
                if (response.data.status === "success") {
                    let employees = response.data.data.data;
                    lastPage = response.data.data.last_page;
                    currentPage = response.data.data.current_page;

                    let employeeTable = $("#employee-body");
                    employeeTable.empty();

                    if (employees.length === 0) {
                        employeeTable.append("<tr><td colspan='4'>No employees found.</td></tr>");
                    } else {
                        employees.forEach(employee => {
                            employeeTable.append(`
                                <tr>
                                    <td>${employee.id}</td>
                                    <td>${employee.first_name} ${employee.last_name}</td>
                                    <td>${employee.department}</td>
                                    <td>${employee.position_title}</td>
                                </tr>
                            `);
                        });
                    }

                    renderPagination(currentPage, lastPage, document.getElementById("pagination"), fetchEmployees);
                } else {
                    console.error("Unexpected API response format:", response.data);
                }
            })
            .catch(error => {
                console.error("Error fetching employees:", error);
                $("#employee-body").html("<tr><td colspan='4'>Error loading employees.</td></tr>");
            });
    }

    fetchEmployees();
});
