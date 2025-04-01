<script setup>
import { onMounted, ref, watch, nextTick } from "vue";
import { RouterLink } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";
import { PaginationSizes, PaginationSizeOptions } from "@/enums/paginationSizes";
import axios from "@/axios";
import LoadingIndicator from "../../singles/SpinnerGrow.vue";

const {
	showFilterForms,
	toggleFilterForms,
	hideFilterForms,
	parseDate,
} = useCustomUtils();

const types = ref([]);
const activities = ref([]);
const startDate = ref([]);
const endDate = ref([]);
const isLoading = ref(false);
const searchQuery = ref("");
const searchExecuted = ref(false);
const applicationlogs = ref([]);
const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 });
const paginationSize = ref(PaginationSizes.SMALL);
const paginationSizeOptions = PaginationSizeOptions;
const filterType = ref("");
const filterActivity = ref("");
const filterStartDate = ref(""); // Bind the start date
const filterEndDate = ref(""); // Bind the end date
const fetchApplicationLogColumns = async () => {
	try {
		// Retrieve the token from local storage
		const token = localStorage.getItem("token");

		// Make sure the token exists before making the request
		if (!token) {
			throw new Error("No token found");
		}

		// Fetch activities from the API
		const response = await axios.get("/applicationlogcolumns", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
		});

		if (response.status === 200) {
			types.value = response.data.types; // Store the fetched atypes
			activities.value = response.data.activities; // Store the fetched activities
		} else {
			console.error("Error fetching columns:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching columns:", error);
	}
};
const fetchApplicationLogs = async (page = 1) => {
	isLoading.value = true; // Start loading
	try {
		// Retrieve the token from local storage
		const token = localStorage.getItem("token");

		// Make sure the token exists before making the request
		if (!token) {
			throw new Error("No token found");
		}
		const response = await axios.get("/applicationloglist", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
			params: {
				pagination_size: paginationSize.value,
				page: page,
				search: searchQuery.value,
				type: filterType.value,
				activity: filterActivity.value,
				startDate: filterStartDate.value,
				endDate: filterEndDate.value,
			},
		});

		if (response.status === 200) {
			applicationlogs.value = response.data.data;
			pagination.value = {
				currentPage: response.data.current_page,
				lastPage: response.data.last_page,
				total: response.data.total,
			};
		} else {
			console.error("Error fetching application logs:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching application logs:", error);
	} finally {
		isLoading.value = false; // Stop loading
	}
};

// Initial data fetch
onMounted(() => {
	new Podtable("#table", {
		keepCell: [7],
	});
	fetchApplicationLogs();
	fetchApplicationLogColumns();
});

// Search function
const search = () => {
	searchExecuted.value = true;
	fetchApplicationLogs();
};

// Clear search and reset
const clearSearch = () => {
	searchQuery.value = "";
	searchExecuted.value = false;
	fetchApplicationLogs();
};

// Reset filters and hide filter forms
const resetFiltersAndHide = () => {
	filterType.value = "";
	filterActivity.value = "";
	filterStartDate.value = "";
	filterEndDate.value = "";
	$(".datepicker-start").val(filterStartDate.value);
	$(".datepicker-end").val(filterStartDate.value);
	hideFilterForms();
	fetchApplicationLogs(); // Refetch data without filters
};

// Apply filters
const applyFilters = () => {
	fetchApplicationLogs();
};

// Handle pagination button click
const handlePaginationClick = (page) => {
	if (page > 0 && page <= pagination.value.lastPage) {
		fetchApplicationLogs(page);
	}
};

// Watch for pagination size changes and refetch data
watch(paginationSize, () => {
	fetchApplicationLogs();
});
// Initialize Select2 on all select fields
const initializeSelect2 = () => {
	$(function () {
		// Apply Select2 to all select elements
		$(".select")
			.select2({
				allowClear: true,
			})
			.on("change", function () {
				const fieldName = $(this).attr("id"); // Get the ID of the select field
				switch (fieldName) {
					case "filterType":
						filterType.value = $(this).val(); // Sync selection
						break;
					case "filterActivity":
						filterActivity.value = $(this).val(); // Sync selection
						break;
					// Add more cases for other select elements if needed
				}
			});
	});
};
// Initialize Date Pickers with parseDate function for date formatting
/* const initializeDatePickers = () => {
    // Start Date Picker
    $('.datepicker-start').daterangepicker({
        singleDatePicker: true,
        startDate: moment().startOf('hour'),
        locale: { format: 'YYYY-MM-DD' } // Change to desired format
    }, function (start) {
        filterStartDate.value = parseDate(start.format('YYYY-MM-DD'));
    });
    // End Date Picker
    $('.datepicker-end').daterangepicker({
        singleDatePicker: true,
        startDate: moment().startOf("hour"),
        locale: { format: 'YYYY-MM-DD' } // Change to desired format
    }, function (end) {
        filterEndDate.value = parseDate(end.format('YYYY-MM-DD'));
    });
}; */
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

// Modify the toggle function to include initializeSelect2 and initializeDatePickers as callbacks
const handleToggleFilterForms = () => {
	toggleFilterForms(async () => {
		await nextTick(); // Wait for DOM update
		initializeSelect2();
		initializeDatePickers();
	});
};
// Watch for types and activities changes to re-initialize Select2
watch([types, activities], () => {
	initializeSelect2();
});
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
					<RouterLink to="/rolelist" class="text-decoration-none"
						>Application Logs</RouterLink
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
			<div class="row">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-body p-2">
							<div class="d-flex justify-content-end my-1 my-lg-0">
								<div class="d-flex flex-row gap-2">
									<!-- Updated Filter button to toggle visibility of filter forms -->
									<button
										class="btn btn-sm btn-info"
										@click="handleToggleFilterForms"
									>
										<i class="fa fa-sliders"></i> Filter
									</button>
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
							<div class="row gx-3">
								<!-- Type filter -->
								<div class="col-lg-3 col-sm-4 col-12">
									<div class="mb-3">
										<label for="filterType" class="form-label"
											>Type</label
										>
										<select
											id="filterType"
											name="filterType"
											class="form-select select"
											v-model="filterType"
										>
											<option value="" disabled>Select type</option>
											<option
												v-for="type in types"
												:key="type.type"
												:value="type.type"
											>
												{{ type.type }}
											</option>
										</select>
									</div>
								</div>
								<!-- Activity filter -->
								<div class="col-lg-3 col-sm-4 col-12">
									<div class="mb-3">
										<label for="filterActivity" class="form-label"
											>Activity</label
										>
										<select
											id="filterActivity"
											name="filterActivity"
											class="form-select select"
											v-model="filterActivity"
										>
											<option value="" disabled>
												Select activity
											</option>
											<option
												v-for="activity in activities"
												:key="activity.activity"
												:value="activity.activity"
											>
												{{ activity.activity }}
											</option>
										</select>
									</div>
								</div>
								<!-- Startdate filter -->
								<div class="col-lg-3 col-sm-4 col-12">
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
								<div class="col-lg-3 col-sm-4 col-12">
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
										@change="fetchApplicationLogs"
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
											<th scope="col">TYPE</th>
											<th scope="col">ACTIVITY</th>
											<th scope="col">BROWSER</th>
											<th scope="col">IP ADDRESS</th>
											<th scope="col">USER</th>
											<th scope="col">DATE</th>
											<th scope="col">ACTIONS</th>
											<th scope="col" class="control-column"></th>
										</tr>
									</thead>
									<tbody>
										<tr
											v-for="(log, index) in applicationlogs"
											:key="index"
										>
											<th scope="row">
												{{
													(pagination.currentPage - 1) *
														paginationSize +
													index +
													1
												}}
											</th>
											<td>{{ log.type }}</td>
											<td>{{ log.activity }}</td>
											<td>{{ log.browser }}</td>
											<td>{{ log.ip }}</td>
											<td>
												{{ log.user ? log.user.name : "N/A" }}
											</td>
											<td>{{ log.formatted_created_at }}</td>
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
																	:to="{
																		name:
																			'ApplicationLogShow',
																		params: {
																			id: log.id,
																		},
																	}"
																	class="dropdown-item"
																	>View</RouterLink
																>
															</li>
														</ul>
													</div>
												</div>
											</td>
											<td class="control-column"></td>
										</tr>
										<tr v-if="applicationlogs.length === 0">
											<th colspan="8" class="text-center">
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
												pagination.currentPage > 1
													? (pagination.currentPage - 1) *
															paginationSize +
													  1
													: 1
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
													@click="
														handlePaginationClick(
															pagination.currentPage - 1
														)
													"
												>
													&laquo;
												</button>
											</li>

											<!-- Display up to five numbered buttons with an interval of 5 -->
											<template v-if="pagination.lastPage > 1">
												<template
													v-for="pageNumber in Math.min(
														pagination.lastPage,
														pagination.currentPage + 4
													)"
												>
													<li
														:key="pageNumber"
														class="page-item"
														:class="{
															active:
																pageNumber ===
																pagination.currentPage,
														}"
														v-if="
															pageNumber >=
																pagination.currentPage &&
															pageNumber <=
																pagination.currentPage + 3
														"
													>
														<button
															class="page-link btn-sm"
															@click="
																handlePaginationClick(
																	pageNumber
																)
															"
														>
															{{ pageNumber }}
														</button>
													</li>
												</template>
											</template>

											<li
												class="page-item"
												v-if="
													pagination.currentPage <
													pagination.lastPage
												"
											>
												<button
													class="page-link btn-sm"
													@click="
														handlePaginationClick(
															pagination.currentPage + 1
														)
													"
												>
													&raquo;
												</button>
											</li>
											<li
												class="page-item"
												v-if="
													pagination.currentPage <
													pagination.lastPage
												"
											>
												<button
													class="page-link btn-sm"
													@click="
														handlePaginationClick(
															pagination.lastPage
														)
													"
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
