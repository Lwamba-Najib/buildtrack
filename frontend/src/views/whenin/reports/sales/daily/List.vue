<script setup>
import { onMounted, ref, reactive, watch, nextTick } from "vue";
import { RouterLink } from "vue-router";
import { useStore } from 'vuex';
import { useCustomUtils } from "@/utils/customUtils";
import { PaginationSizes, PaginationSizeOptions } from "@/enums/paginationSizes";
import axios from "@/axios";
import LoadingIndicator from "../../../singles/SpinnerGrow.vue";
import { useMenuAccess } from "@/permissions"; // Adjust the path as needed
// Use the menu access composable
const { menuAccess } = useMenuAccess();
const {
	showFilterForms,
	toggleFilterForms,
	hideFilterForms,
	parseDate,
} = useCustomUtils();
// Access Vuex store
const store = useStore();
const isLoading = ref(false);
const searchQuery = ref("");
const searchExecuted = ref(false);
const sales = ref([]);
const lastSoldDate = ref("");  
const totalSales = ref("0"); 
const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 });
const paginationSize = ref(PaginationSizes.SMALL);
const paginationSizeOptions = PaginationSizeOptions;
const filterDate = ref(""); // Bind the date
const alerts = reactive({
	success: "",
	error: "",
});
// Helper function to retrieve token
const getToken = () => {
	const token = localStorage.getItem("token");
	if (!token) throw new Error("No token found");
	return token;
};

// Centralized error handling function
const handleError = (error, alertField = "error") => {
	alerts[alertField] = error.response?.data?.message || "An error occurred. Please try again later.";
	console.error("API Error:", error);
};


const fetchReportDailySales = async (page = 1) => {
    isLoading.value = true; // Start loading
    try {
        // Retrieve the token from local storage
        const token = getToken();

        const response = await axios.get("/reportdailysaleslist", {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the Authorization header
            },
            params: {
                pagination_size: paginationSize.value,
                page: page,
                search: searchQuery.value,
                Date: filterDate.value,
            },
        });

        if (response.status === 200) {
            // Check if response.data.data exists (your paginated data)
            sales.value = response.data.data?.data || [];
			// Set last sold date and total sales
            lastSoldDate.value = response.data.last_sold_date;
            totalSales.value = response.data.total_sales;

            // Update the pagination object
            pagination.value = {
                currentPage: response.data.data.current_page, // Access current_page from the root level
                lastPage: response.data.data.last_page,       // Access last_page from the root level
                total: response.data.data.total,              // Access total from the root level
            };
        } else {
            console.error("Error fetching report daily sale logs:", response.statusText);
        }
    } catch (error) {
        handleError(error); // Handle error using centralized error handle
    } finally {
        isLoading.value = false; // Stop loading
    }
};
const exportFile = async (fileType) => {
	// Set loading state to true while the file is being prepared
	isLoading.value = true;

	// Define URL endpoints for different file types
	const urls = {
		xlsx: "/reportdailysalesxlsx",
		csv: "/reportdailysalescsv",
	};

	try {
		// Retrieve the authentication token from local storage
		const token = getToken();

		// Make an HTTP GET request to fetch the file
		const response = await axios.get(urls[fileType], {
			headers: { Authorization: `Bearer ${token}` },
			responseType: "blob", // Set response type to 'blob' to handle file downloads
            params: {
                Date: filterDate.value // Add this line to pass the selected date
            }
		});

		// Generate filename with the current date
		const today = new Date().toISOString().split("T")[0].replace(/-/g, "_"); // Format: yyyy_mm_dd
		const filename = `${today}_reportdailysales.${fileType}`;

		// Extract filename from Content-Disposition header if available
		const disposition = response.headers["content-disposition"];
		const filenameMatch = disposition
			? disposition.match(/filename="([^"]*)"/)
			: null;
		const finalFilename = filenameMatch ? filenameMatch[1] : filename;

		// Create a URL for the blob and trigger a download
		const urlBlob = window.URL.createObjectURL(new Blob([response.data]));
		const link = document.createElement("a");
		link.href = urlBlob;
		link.download = finalFilename; // Set the file name for download
		document.body.appendChild(link);
		link.click(); // Programmatically click the link to trigger download
		link.remove(); // Remove the link from the DOM
		window.URL.revokeObjectURL(urlBlob); // Clean up the object URL
	} catch (error) {
		// Log any errors that occur during the file export
		console.error(`Error exporting ${fileType} file:`, error);
	} finally {
		// Reset loading state after the file has been processed
		isLoading.value = false;
	}
};

// Methods to trigger export
const exportXlsx = () => exportFile("xlsx");
const exportCsv = () => exportFile("csv");

// Initial data fetch
onMounted(() => {
	new Podtable("#table", {
		keepCell: [9],
	});
	fetchReportDailySales();
});

// Search function
const search = () => {
	searchExecuted.value = true;
	fetchReportDailySales();
};

// Clear search and reset
const clearSearch = () => {
	searchQuery.value = "";
	searchExecuted.value = false;
	fetchReportDailySales();
};

// Reset filters and hide filter forms
const resetFiltersAndHide = () => {
	filterDate.value = "";
	$(".datepicker-one").val(filterDate.value);
	hideFilterForms();
	fetchReportDailySales(); // Refetch data without filters
};

// Apply filters
const applyFilters = () => {
	fetchReportDailySales();
};

// Handle pagination button click
const handlePaginationClick = (page) => {
	if (page > 0 && page <= pagination.value.lastPage) {
		fetchReportDailySales(page);
	}
};

// Watch for pagination size changes and refetch data
watch(paginationSize, () => {
	fetchReportDailySales();
});

// Initialize Date Pickers with parseDate function for date formatting
const initializeDatePickers = () => {
	// Start Date Picker without pre-filling
	$(".datepicker-one").daterangepicker(
		{
			singleDatePicker: true,
			autoUpdateInput: false, // Prevents auto-filling with a date
			locale: { format: "YYYY-MM-DD" },
		},
		function (start) {
			filterDate.value = parseDate(start.format("YYYY-MM-DD"));
			$(".datepicker-one").val(filterDate.value); // Updates input field on selection
		}
	);
};
// Modify the toggle function to include initializeSelect2 as a callback
const handleToggleFilterForms = () => {
	toggleFilterForms(async () => {
		await nextTick(); // Wait for DOM update
		initializeDatePickers();
	});
};
</script>

<template>
	<section>
		<!-- App hero header starts -->
		<div class="app-hero-header d-flex align-items-center">
			<!-- Breadcrumb start -->
			<ol class="breadcrumb">
				<li class="breadcrumb-item">
					<i class="bi bi-house lh-1 pe-3 me-3 border-end border-dark"></i>
					<RouterLink to="/home" class="text-decoration-none">Home</RouterLink>
				</li>
				<li class="breadcrumb-item">
					<RouterLink to="/reportdailysaleslist" class="text-decoration-none"
						>Sales</RouterLink
					>
				</li>
				<li class="breadcrumb-item text-secondary" aria-current="page">List</li>
			</ol>
			<!-- Breadcrumb end -->
		</div>
		<!-- App Hero header ends -->

		<!-- App body starts -->
		<div class="app-body">
			<!-- Row start -->
			<div
				class="row"
				v-if="
					menuAccess.reportDailySalesFilter ||
					menuAccess.reportDailySalesExport
				"
			>
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-body p-2">
							<div class="d-flex justify-content-end my-1 my-lg-0">
								<div class="d-flex flex-row gap-2">
									<!-- Updated Filter button to toggle visibility of filter forms -->
									<button
										class="btn btn-sm btn-info"
										v-if="menuAccess.reportDailySalesFilter"
										@click="handleToggleFilterForms"
									>
										<i class="fa fa-sliders"></i> Filter
									</button>
									<div class="d-flex" v-if="menuAccess.reportDailySalesExport">
										<div class="dropdown">
											<button
												type="button"
												class="btn btn-success btn-sm dropdown-toggle"
												data-bs-toggle="dropdown"
											>
												<i class="fa fa-download"></i>
												Export
											</button>
											<ul
												class="dropdown-menu dropdown-menu-end"
												style="right: 0; left: auto"
											>
												<li>
													<a
														class="dropdown-item"
														href="#"
														@click.prevent="exportXlsx"
														>XLSX</a
													>
												</li>
												<div class="dropdown-divider"></div>
												<li>
													<a
														class="dropdown-item"
														href="#"
														@click.prevent="exportCsv"
														>CSV</a
													>
												</li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Row end -->
			<!-- Row start -->
			<div v-if="showFilterForms" class="row">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-body">
							<!-- Row start -->
							<div class="row gx-3">
								<!-- date filter -->
								<div class="col-lg-12 col-sm-4 col-12">
									<div class="mb-3">
										<label for="filterDate" class="form-label"
											>Date</label
										>
										<div class="input-group">
											<input
												type="text"
												class="form-control datepicker-one"
												placeholder="YYYY-MM-DD"
											/>
											<span class="input-group-text">
												<i class="bi bi-calendar4"></i>
											</span>
										</div>
									</div>
								</div>
							</div>
							<!-- Row end -->
						</div>
						<div class="card-footer">
							<div
								class="d-flex justify-content-between align-items-center my-2 my-lg-0"
							>
								<!-- Cancel and Submit buttons -->
								<button
									type="button"
									class="btn btn-sm btn-danger"
									@click="resetFiltersAndHide"
								>
									<i class="fa fa-times"></i> Cancel
								</button>
								<button
									type="button"
									class="btn btn-sm btn-success"
									@click="applyFilters"
								>
									<i class="fa fa-send"></i> Submit
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Row end -->
			<!-- Row start -->
			<div class="row gx-3">
				<div class="col-xl-3 col-sm-6 col-12">
					<div class="card mb-3">
						<div class="card-body">
							<strong class="d-flex align-items-center justify-content-between">
								Date
								<span class="text-default">{{ lastSoldDate }}</span>
							</strong>
							<hr>
							<strong class="d-flex align-items-center justify-content-between">
								Total Sales (UGX)
								<span class="text-default">{{ Number(totalSales).toLocaleString() || 0 }}</span>
							</strong>
						</div>
					</div>
				</div>
			</div>
			<!-- Row end -->
			<!-- Row start -->
			<div class="row gx-3">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-header">
							<div
								class="d-flex justify-content-between align-items-center my-2 my-lg-0"
							>
								<div class="form-inline">
									<!-- Dropdown to select pagination size -->
									<select
										name="paginationSize"
										class="form-select form-select-sm"
										v-model="paginationSize"
										@change="fetchReportDailySales"
									>
										<option
											v-for="size in paginationSizeOptions"
											:key="size"
											:value="size"
										>
											{{ size }}
										</option>
									</select>
								</div>
								<div class="input-group mb-0 filter">
									<input
										name="searchQuery"
										type="text"
										class="form-control form-control-sm"
										placeholder="Search"
										v-model="searchQuery"
									/>
									<div class="input-group-append">
										<button
											class="btn btn-primary btn-sm"
											type="button"
											@click="search"
										>
											<i class="fa fa-search"></i>
										</button>
										<button
											class="btn btn-secondary btn-sm"
											type="button"
											@click="clearSearch"
											v-if="searchExecuted"
										>
											<i class="fa fa-times"></i>
										</button>
									</div>
								</div>
							</div>
						</div>
						<div class="card-body">
							<!-- Success Alert -->
							<div
								v-if="alerts.success"
								class="alert border border-success alert-dismissible fade show text-success"
								role="alert"
							>
								{{ alerts.success }}
								<button
									type="button"
									class="btn-close"
									data-bs-dismiss="alert"
									aria-label="Close"
								></button>
							</div>

							<!-- Error Alert -->
							<div
								v-if="alerts.error"
								class="alert border border-danger alert-dismissible fade show text-danger"
								role="alert"
							>
								{{ alerts.error }}
								<button
									type="button"
									class="btn-close"
									data-bs-dismiss="alert"
									aria-label="Close"
								></button>
							</div>
							<!-- Table Container with relative positioning -->
							<div class="position-relative">
								<!-- Display the LoadingIndicator component -->
								<LoadingIndicator :isLoading="isLoading" />
								<table
									id="table"
									class="table align-middle table-hover m-0"
								>
									<thead>
										<tr>
											<th scope="col">#</th>
											<th scope="col">PRODUCT</th>
											<th scope="col">BRAND</th>
											<th scope="col">MEASUREMENT</th>
											<th scope="col">BATCH NO</th>
											<th scope="col">QTY</th>
											<th scope="col">UNIT PRICE</th>
											<th scope="col">DISCOUNT</th>
											<th scope="col">TOTAL AMOUNT</th>
											<th scope="col">ISSUED BY</th>
											<th scope="col">DATE</th>
											<th scope="col" class="control-column"></th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(log, index) in sales" :key="index">
											<th scope="row">
												{{ (pagination.currentPage - 1) * paginationSize + index + 1 }}
											</th>
											<td>{{ log.product.name || "N/A" }}</td>
											<td>{{ log.brand.name || "N/A" }}</td>
											<td>{{ log.measurement.name || "N/A" }}</td>
											<td>{{ log.batch_number || "N/A" }}</td>
											<td class="text-end">{{ Number(log.quantity).toLocaleString() || 0 }}</td>
											<td class="text-end">{{ Number(log.unit_price).toLocaleString() || 0 }}</td>
											<td class="text-end">{{ Number(log.sale.discount).toLocaleString() || 0 }}%</td>
											<td class="text-end">{{ Number(log.total_price).toLocaleString() || 0 }}</td>
											<td>{{ log.user ? log.user.name : "N/A" }}</td>
											<td>{{ parseDate(log.created_at) || "N/A" }}</td>
											<td class="control-column"></td>
										</tr>
										<tr v-if="!isLoading && sales.length === 0">
											<th colspan="12" class="text-center">
												No records found.
											</th>
										</tr>
									</tbody>
								</table>
								<!-- Pagination start -->
								<div
									v-if="pagination.total > 0"
									class="d-flex justify-content-between mt-2"
								>
									<div>
										{{
											`Showing ${
												pagination.currentPage > 1 ? (pagination.currentPage - 1) * paginationSize + 1 : 1
											} to ${Math.min(
												pagination.currentPage * paginationSize,
												pagination.total
											)} of ${pagination.total} results`
										}}
									</div>
									<nav aria-label="Page navigation example">
										<ul class="pagination">
											<li
												class="page-item"
												v-if="pagination.currentPage > 1"
											>
												<button
													class="page-link btn-sm"
													@click="handlePaginationClick(1)"
												>
													&laquo;&laquo;
												</button>
											</li>
											<li
												class="page-item"
												v-if="pagination.currentPage > 1"
											>
												<button
													class="page-link btn-sm"
													@click="handlePaginationClick(pagination.currentPage - 1)"
												>
													&laquo;
												</button>
											</li>

											<!-- Display up to five numbered buttons with an interval of 5 -->
											<template v-if="pagination.lastPage > 1">
												<template
													v-for="pageNumber in Math.min(pagination.lastPage, pagination.currentPage + 4)"
												>
													<li
														:key="pageNumber"
														class="page-item"
														:class="{
															active:
																pageNumber === pagination.currentPage,
														}"
														v-if="
															pageNumber >= pagination.currentPage && pageNumber <= pagination.currentPage + 3"
													>
														<button
															class="page-link btn-sm"
															@click="handlePaginationClick(pageNumber)"
														>
															{{ pageNumber }}
														</button>
													</li>
												</template>
											</template>

											<li
												class="page-item"
												v-if="pagination.currentPage < pagination.lastPage"
											>
												<button
													class="page-link btn-sm"
													@click="handlePaginationClick(pagination.currentPage + 1)"
												>
													&raquo;
												</button>
											</li>
											<li
												class="page-item"
												v-if="pagination.currentPage < pagination.lastPage"
											>
												<button
													class="page-link btn-sm"
													@click="handlePaginationClick(pagination.lastPage)"
												>
													&raquo;&raquo;
												</button>
											</li>
										</ul>
									</nav>
								</div>
								<!-- Pagination end -->
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Row end -->
		</div>
		<!-- App body ends -->
	</section>
</template>

<style scoped></style>
