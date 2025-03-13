import { onMounted, ref, onBeforeUnmount } from "vue";
import moment from "moment";

// Create a function to handle custom initialization and styling
export function useCustomUtils() {
    // Reactive variable for controlling the visibility of the filter forms
    const showFilterForms = ref(false);

    // Function to parse date in different formats
    const parseDate = (dateStr) => {
        const formats = ["YYYY-MM-DD", "YYYY.MM.DD", "DD/MM/YYYY", moment.ISO_8601];

        // Use moment.utc to handle the UTC date correctly
        const parsedDate = moment.utc(dateStr, formats, true); // Parse in UTC mode

        // Format it back to "YYYY-MM-DD" without adjusting to the local time zone
        return parsedDate.isValid()
            ? parsedDate.format("YYYY-MM-DD") // Format in UTC
            : "Invalid Date";
    };

    // Method to toggle the visibility of the filter forms
    const toggleFilterForms = (callback) => {
        showFilterForms.value = !showFilterForms.value;
        if (callback) callback(); // Execute the callback if provided
    };

    // Method to hide the visibility of the filter forms
    const hideFilterForms = () => {
        showFilterForms.value = false;
    };

    // Arrays to store tooltip and popover instances for cleanup
    let tooltipInstances = [];
    let popoverInstances = [];

    // onMounted lifecycle hook to run the table initialization and styling code after component is mounted
    onMounted(() => {
        // Tooltip initialization
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipInstances = tooltipTriggerList.map(
            (tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl)
        );

        // Popover initialization
        const popoverTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="popover"]')
        );
        popoverInstances = popoverTriggerList.map(
            (popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl)
        );

        // Get all tables on the page
        const tables = document.querySelectorAll("table");

        // Loop through each table
        tables.forEach((table) => {
            // Find all th elements in the current table
            const headers = table.querySelectorAll("thead th");

            // Loop through th elements to find the ACTIONS column
            headers.forEach((th, index) => {
                if (th.textContent.trim() === "ACTIONS") {
                    // Apply styles to the ACTIONS column
                    applyColumnStyles(table, index + 1); // index + 1 because nth-child is 1-based
                }
            });
        });

        // Function to apply styles to the specified column index
        function applyColumnStyles(table, columnIndex) {
            const css = `
                table#${table.id} th:nth-child(${columnIndex}),
                table#${table.id} td:nth-child(${columnIndex}) {
                    width: 80px;
                }
            `;
            const style = document.createElement("style");
            style.textContent = css;
            document.head.appendChild(style);
        }
    });

    // onBeforeUnmount lifecycle hook to clean up tooltips and popovers when the component is destroyed
    onBeforeUnmount(() => {
        // Dispose of all tooltip instances
        tooltipInstances.forEach((instance) => instance.dispose());
        
        // Dispose of all popover instances
        popoverInstances.forEach((instance) => instance.dispose());
    });

    // Return the reactive variables and methods to be used in components
    return {
        showFilterForms,
        toggleFilterForms,
        hideFilterForms,
        parseDate,
    };
}
