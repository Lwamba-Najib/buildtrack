<script setup>
import { onMounted, ref, reactive, watch, nextTick } from "vue";
import { RouterLink } from "vue-router";
import { useStore } from 'vuex';
import { useCustomUtils } from "@/utils/customUtils";
import { PaginationSizes, PaginationSizeOptions } from "@/enums/paginationSizes";
import axios from "@/axios";
import LoadingIndicator from "../../singles/SpinnerGrow.vue";
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
const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 });
const paginationSize = ref(PaginationSizes.SMALL);
const paginationSizeOptions = PaginationSizeOptions;
const filterStartDate = ref(""); // Bind the start date
const filterEndDate = ref(""); // Bind the end date
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


const fetchSales = async (page = 1) => {
    isLoading.value = true; // Start loading
    try {
        // Retrieve the token from local storage
        const token = getToken();

        const response = await axios.get("/saleslist", {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the Authorization header
            },
            params: {
                pagination_size: paginationSize.value,
                page: page,
                search: searchQuery.value,
                startDate: filterStartDate.value,
                endDate: filterEndDate.value,
            },
        });

        if (response.status === 200) {
            // Assign the data to sales
            sales.value = response.data.data;

            // Update the pagination object
            pagination.value = {
                currentPage: response.data.current_page, // Access current_page from the root level
                lastPage: response.data.last_page,       // Access last_page from the root level
                total: response.data.total,              // Access total from the root level
            };
        } else {
            console.error("Error fetching sale logs:", response.statusText);
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
		xlsx: "/salexlsx",
		csv: "/salecsv",
	};

	try {
		// Retrieve the authentication token from local storage
		const token = getToken();

		// Make an HTTP GET request to fetch the file
		const response = await axios.get(urls[fileType], {
			headers: { Authorization: `Bearer ${token}` },
			responseType: "blob", // Set response type to 'blob' to handle file downloads
		});

		// Generate filename with the current date
		const today = new Date().toISOString().split("T")[0].replace(/-/g, "_"); // Format: yyyy_mm_dd
		const filename = `${today}_sales.${fileType}`;

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
	fetchSales();
});

// Search function
const search = () => {
	searchExecuted.value = true;
	fetchSales();
};

// Clear search and reset
const clearSearch = () => {
	searchQuery.value = "";
	searchExecuted.value = false;
	fetchSales();
};

// Reset filters and hide filter forms
const resetFiltersAndHide = () => {
	filterStartDate.value = "";
	filterEndDate.value = "";
	$(".datepicker-start").val(filterStartDate.value);
	$(".datepicker-end").val(filterStartDate.value);
	hideFilterForms();
	fetchSales(); // Refetch data without filters
};

// Apply filters
const applyFilters = () => {
	fetchSales();
};

// Handle pagination button click
const handlePaginationClick = (page) => {
	if (page > 0 && page <= pagination.value.lastPage) {
		fetchSales(page);
	}
};

// Watch for pagination size changes and refetch data
watch(paginationSize, () => {
	fetchSales();
});

// Initialize Date Pickers with parseDate function for date formatting
const initializeDatePickers = () => {
	// Start Date Picker without pre-filling
	$(".datepicker-start").daterangepicker(
		{
			singleDatePicker: true,
			autoUpdateInput: false, // Prevents auto-filling with a date
			locale: { format: "YYYY-MM-DD" },
		},
		function (start) {
			filterStartDate.value = parseDate(start.format("YYYY-MM-DD"));
			$(".datepicker-start").val(filterStartDate.value); // Updates input field on selection
		}
	);

	// End Date Picker without pre-filling
	$(".datepicker-end").daterangepicker(
		{
			singleDatePicker: true,
			autoUpdateInput: false, // Prevents auto-filling with a date
			locale: { format: "YYYY-MM-DD" },
		},
		function (end) {
			filterEndDate.value = parseDate(end.format("YYYY-MM-DD"));
			$(".datepicker-end").val(filterEndDate.value); // Updates input field on selection
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
					<RouterLink to="/saleslist" class="text-decoration-none"
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
					menuAccess.salesFilter ||
					menuAccess.salesExport
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
										v-if="menuAccess.salesFilter"
										@click="handleToggleFilterForms"
									>
										<i class="fa fa-sliders"></i> Filter
									</button>
									<div class="d-flex" v-if="menuAccess.salesExport">
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
								<!-- Startdate filter -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label for="filterStartDate" class="form-label"
											>Start Date</label
										>
										<div class="input-group">
											<input
												type="text"
												class="form-control datepicker-start"
												placeholder="YYYY-MM-DD"
											/>
											<span class="input-group-text">
												<i class="bi bi-calendar4"></i>
											</span>
										</div>
									</div>
								</div>
								<!-- Enddate filter -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label for="filterEndDate" class="form-label"
											>End Date</label
										>
										<div class="input-group">
											<input
												type="text"
												class="form-control datepicker-end"
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
										@change="fetchSales"
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
											<th scope="col">BATCH NUMBER</th>
											<th scope="col">CUSTOMER NAME</th>
											<th scope="col">PHONE NUMBER</th>
											<th scope="col">DISCOUNT</th>
											<th scope="col">TOTAL AMOUNT</th>
											<th scope="col">PAYMENT METHOD</th>
											<th scope="col">CREATED BY</th>
											<th scope="col">DATE</th>
											<th scope="col">ACTIONS</th>
											<th scope="col" class="control-column"></th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(log, index) in sales" :key="index">
											<th scope="row">
												{{ (pagination.currentPage - 1) * paginationSize + index + 1 }}
											</th>
											<td>{{ log.batch_number || "N/A" }}</td>
											<td>{{ log.customer_name || "N/A" }}</td>
											<td>{{ log.customer_phone || "N/A" }}</td>
											<td>{{ Number(log.discount).toLocaleString() || 0 }}</td>
											<td>{{ Number(log.total_amount).toLocaleString() || 0 }}</td>
											<td>{{ log.payment_method || "N/A" }}</td>
											<td>{{ log.user ? log.user.name : "N/A" }}</td>
											<td>{{ parseDate(log.created_at) || "N/A" }}</td>
											<td>
												<div class="d-flex">
													<div class="dropdown">
														<button
															type="button"
															class="btn btn-success btn-sm dropdown-toggle"
															data-bs-toggle="dropdown"
														>
															Actions
														</button>
														<ul
															class="dropdown-menu dropdown-menu-end"
															style="right: 0; left: auto"
														>
															<li>
																<RouterLink
																	class="dropdown-item"
																	:to="{
																		name: 'SalesShow',
																		params: {
																			id: log.id,
																		},
																	}"
																	>View
																</RouterLink>
															</li>
														</ul>
													</div>
												</div>
											</td>
											<td class="control-column"></td>
										</tr>
										<tr v-if="sales.length === 0">
											<th colspan="10" class="text-center">
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
