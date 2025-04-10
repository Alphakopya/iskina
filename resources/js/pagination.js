function renderPagination(currentPage, lastPage, paginationWrapper, onPageClick) {
    const startPage = Math.max(1, currentPage - 1);
    const endPage = Math.min(lastPage, currentPage + 1);

    paginationWrapper.innerHTML = "";

    paginationWrapper.innerHTML += currentPage > 1
        ? `<button class="pagination-link" data-page="${currentPage - 1}">Previous</button>`
        : `<button class="pagination-link disabled" disabled>Previous</button>`;

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 'active' : '';
        paginationWrapper.innerHTML += `
            <button class="pagination-link ${activeClass}" data-page="${i}">${i}</button>
        `;
    }

    paginationWrapper.innerHTML += currentPage < lastPage
        ? `<button class="pagination-link" data-page="${currentPage + 1}">Next</button>`
        : `<button class="pagination-link disabled" disabled>Next</button>`;

    // Add event listeners for pagination buttons
    paginationWrapper.querySelectorAll(".pagination-link").forEach(button => {
        button.addEventListener("click", function () {
            if (!this.disabled) {
                onPageClick(parseInt(this.dataset.page));
            }
        });
    });
}

export { renderPagination };
