// public/js/bus-stops.js
function busStopSearch() {
    return {
        query: '',
        expanded: false,
        sort_column: 'name', // default sort
        sort_order: 'asc',
        stops: { data: [], current_page: 1, last_page: 1 },
        collapsedColumns: [
            { key: 'name', label: 'Name' },
            { key: 'location', label: 'Location' },
        ],
        fullColumns: [
            { key: 'name', label: 'Name' },
            { key: 'location', label: 'Location' },
            { key: 'routes', label: 'Routes' },
            { key: 'status', label: 'Status' },
        ],

        init() {
            this.fetchStops(1);
        },

        fetchStops(page = 1) {
            // Example: Fetch data via API (replace with your route)
            fetch(`/api/bus-stops?page=${page}&q=${this.query}&sort=${this.sort_column}&order=${this.sort_order}`)
                .then(res => res.json())
                .then(data => {
                    this.stops = data;
                });
        },

        sortByColumn(column) {
            if (this.sort_column === column) {
                this.sort_order = this.sort_order === 'asc' ? 'desc' : 'asc';
            } else {
                this.sort_column = column;
                this.sort_order = 'asc';
            }
            this.fetchStops(this.stops.current_page);
        },

        goToPage(page) {
            if (page >= 1 && page <= this.stops.last_page) {
                this.fetchStops(page);
            }
        },

        paginationWindow() {
            let pages = [];
            const total = this.stops.last_page;
            const current = this.stops.current_page;
            const start = Math.max(1, current - 2);
            const end = Math.min(total, current + 2);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },
    };
}
