<script setup>
import { onMounted, ref, reactive, watch } from "vue";
import { RouterLink } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";
import { PaginationSizes, PaginationSizeOptions } from "@/enums/paginationSizes";
import axios from "@/axios";
import LoadingIndicator from "../../singles/SpinnerGrow.vue";

const { parseDate } = useCustomUtils();
const isLoading = ref(false);
const searchQuery = ref("");
const searchExecuted = ref(false);
const categories = ref([]);
const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 });
const paginationSize = ref(PaginationSizes.SMALL);
const paginationSizeOptions = PaginationSizeOptions;
const alerts = reactive({ success: "", error: "" });

// Helper function to retrieve authentication token
const getToken = () => {
	const token = localStorage.getItem("token");
	if (!token) throw new Error("No token found"); // Throws error if no token is found
	return token;
};

// Centralized error handling function to display API errors
const handleError = (error, alertField = "error") => {
	alerts[alertField] = error.response?.data?.message || "An error occurred. Please try again later.";
	console.error("API Error:", error);
};

// Function to delete a category with confirmation
const deleteCategory = async (categoryId) => {
	const confirmed = window.confirm("Are you sure you want to delete this category?");
	if (confirmed) {
		alerts.success = "";
		alerts.error = "";
		isLoading.value = true;
		try {
			const token = getToken(); // Retrieve authentication token

			const response = await axios.delete(`/categorydelete/${categoryId}`, {
				headers: { Authorization: `Bearer ${token}` },
			});

			if (response.data.success) {
				fetchCategories();
				alerts.success = "Category deleted successfully!";
			} else {
				throw new Error(response.data.message);
			}
		} catch (error) {
			handleError(error); // Use centralized error handler for API errors
		} finally {
			isLoading.value = false;
		}
	}
};

// Track selected categories for mass delete
const selectedCategories = ref([]);

// Toggle category selection
const toggleCategorySelection = (categoryId) => {
	if (selectedCategories.value.includes(categoryId)) {
		selectedCategories.value = selectedCategories.value.filter((id) => id !== categoryId);
	} else {
		selectedCategories.value.push(categoryId);
	}
};

// Toggle all categories selection
const toggleAllCategories = (event) => {
	if (event.target.checked) {
		selectedCategories.value = categories.value.map((category) => category.id);
	} else {
		selectedCategories.value = [];
	}
};

// Mass delete function
const deleteSelectedCategories = async () => {
	const confirmed = window.confirm(
		`Are you sure you want to delete ${selectedCategories.value.length} category(s)?`
	);
	if (confirmed && selectedCategories.value.length > 0) {
		alerts.success = "";
		alerts.error = "";
		isLoading.value = true;

		try {
			const token = getToken(); // Retrieve authentication token

			const response = await axios.delete(`/categoriesdelete`, {
				headers: { Authorization: `Bearer ${token}` },
				data: { category_ids: selectedCategories.value },
			});

			if (response.data.success) {
				fetchCategories();
				alerts.success = `${selectedCategories.value.length} category(s) deleted successfully!`;
				selectedCategories.value = [];
			} else {
				throw new Error(response.data.message);
			}
		} catch (error) {
			handleError(error); // Use centralized error handler for API errors
		} finally {
			isLoading.value = false;
		}
	}
};
// Fetch categories function
const fetchCategories = async (page = 1) => {
	isLoading.value = true; // Start loading
	try {
		const token = getToken(); // Retrieve authentication token

		// Make sure the token exists before making the request
		if (!token) {
			throw new Error("No token found");
		}
		const response = await axios.get("/categorylist", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
			params: {
				pagination_size: paginationSize.value,
				page: page,
				search: searchQuery.value,
			},
		});

		if (response.status === 200) {
			categories.value = response.data.data;
			pagination.value = {
				currentPage: response.data.current_page,
				lastPage: response.data.last_page,
				total: response.data.total,
			};
		} else {
			console.error("Error fetching category logs:", response.statusText);
		}
	} catch (error) {
		handleError(error); // Use centralized error handler for API errors
	} finally {
		isLoading.value = false; // Stop loading
	}
};

// Initial data fetch
onMounted(() => {
	new Podtable("#table", { keepCell: [4] });
	fetchCategories();
});

// Search function
const search = () => {
	searchExecuted.value = true;
	fetchCategories();
};

// Clear search and reset
const clearSearch = () => {
	searchQuery.value = "";
	searchExecuted.value = false;
	fetchCategories();
};

// Handle pagination button click
const handlePaginationClick = (page) => {
	if (page > 0 && page <= pagination.value.lastPage) {
		fetchCategories(page);
	}
};

// Watch for pagination size changes and refetch data
watch(paginationSize, () => {
	fetchCategories();
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
					<RouterLink to="/categorylist" class="text-decoration-none"
						>Categories</RouterLink
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
									<RouterLink
										to="/categorycreate"
										class="btn btn-sm btn-primary"
										><i class="fa fa-plus"></i>
										Create
									</RouterLink>
									<!-- Mass Delete Button -->
									<!-- Mass Delete Button -->
									<button
										v-if="selectedCategories.length > 0"
										class="btn btn-danger btn-sm"
										@click="deleteSelectedCategories"
									>
										<i class="fa fa-trash"></i> Delete
									</button>
								</div>
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
										@change="fetchCategories"
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
											<th scope="col">
												<!-- Header checkbox to select/unselect all -->
												<input
													type="checkbox"
													@change="toggleAllCategories($event)"
												/>
											</th>
											<th scope="col">#</th>
											<th scope="col">NAME</th>
											<th scope="col">CREATED BY</th>
											<th scope="col">DATE</th>
											<th scope="col">ACTIONS</th>
											<th scope="col" class="control-column"></th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(log, index) in categories" :key="index">
											<td>
												<input
													type="checkbox"
													:value="log.id"
													@change="toggleCategorySelection(log.id)"
													:checked="
														selectedCategories.includes(log.id)
													"
												/>
											</td>
											<th scope="row">
												{{
													(pagination.currentPage - 1) *
														paginationSize +
													index +
													1
												}}
											</th>
											<td>{{ log.name }}</td>
											<td>
												{{ log.user ? log.user.name : "N/A" }}
											</td>
											<td>{{ parseDate(log.created_at) }}</td>
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
																		name:
																			'CategoryUpdate',
																		params: {
																			id: log.id,
																		},
																	}"
																>
																	Edit</RouterLink
																>
															</li>
															<div
																class="dropdown-divider"
															></div>
															<li>
																<a
																	class="dropdown-item"
																	href="#"
																	@click.prevent="
																		deleteCategory(log.id)
																	"
																	>Delete</a
																>
															</li>
														</ul>
													</div>
												</div>
											</td>
											<td class="control-column"></td>
										</tr>
										<tr v-if="!isLoading && categories.length === 0">
											<th colspan="7" class="text-center">
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
