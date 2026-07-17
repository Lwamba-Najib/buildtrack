<script setup>
import { computed, onMounted, ref, reactive, watch } from "vue";
import { RouterLink } from "vue-router";
import { useStore } from 'vuex';
import { useCustomUtils } from "@/utils/customUtils";
import { PaginationSizes, PaginationSizeOptions } from "@/enums/paginationSizes";
import axios from "@/axios";
import LoadingIndicator from "../singles/SpinnerGrow.vue";
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
const gender = ref([]);
const roles = ref([]);
const isLoading = ref(false);
const searchQuery = ref("");
const searchExecuted = ref(false);
const users = ref([]);
const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 });
const paginationSize = ref(PaginationSizes.SMALL);
const paginationSizeOptions = PaginationSizeOptions;
const filterGender = ref("");
const filterUserType = ref("");
const filterRole = ref("");
const alerts = reactive({
	success: "",
	error: "",
});
const fetchGender = async () => {
	try {
		// Retrieve the token from local storage
		const token = localStorage.getItem("token");

		// Make sure the token exists before making the request
		if (!token) {
			throw new Error("No token found");
		}

		// Fetch activities from the API
		const response = await axios.get("/usergender", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
		});

		if (response.status === 200) {
			gender.value = response.data; // Store the fetched gender
		} else {
			console.error("Error fetching gender:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching gender:", error);
	}
};
const fetchRoles = async () => {
	try {
		// Retrieve the token from local storage
		const token = localStorage.getItem("token");

		// Make sure the token exists before making the request
		if (!token) {
			throw new Error("No token found");
		}

		// Fetch activities from the API
		const response = await axios.get("/userroles", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
		});

		if (response.status === 200) {
			roles.value = response.data; // Store the fetched roles
		} else {
			console.error("Error fetching roles:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching roles:", error);
	}
};
// Function to delete a user with confirmation
const deleteUser = async (userId) => {
	const confirmed = window.confirm("Are you sure you want to delete this user?");
	if (confirmed) {
		alerts.success = "";
		alerts.error = "";
		isLoading.value = true; // Start loading
		try {
			// Retrieve the token from local storage
			const token = localStorage.getItem("token");
			if (!token) {
				throw new Error("No token found");
			}

			// Make delete request to backend
			const response = await axios.delete(`/userdelete/${userId}`, {
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			});

			if (response.data.success) {
				// Refresh users list after deletion
				fetchUsers();
				alerts.success = "User deleted successfully!";
			}
		} catch (error) {
			console.error("Error deleting user:", error);
			alerts.error =
				response.data.message || "An error occurred while deleting the user.";
		} finally {
			isLoading.value = false; // Stop loading
		}
	}
};
// Track selected users for mass delete
const selectedUsers = ref([]);

// Toggle user selection
const toggleUserSelection = (userId) => {
	if (selectedUsers.value.includes(userId)) {
		selectedUsers.value = selectedUsers.value.filter((id) => id !== userId);
	} else {
		selectedUsers.value.push(userId);
	}
};

// Toggle all users selection
const toggleAllUsers = (event) => {
	if (event.target.checked) {
		selectedUsers.value = users.value.map((user) => user.id);
	} else {
		selectedUsers.value = [];
	}
};

// Mass delete function
const deleteSelectedUsers = async () => {
	const confirmed = window.confirm(
		`Are you sure you want to delete ${selectedUsers.value.length} user(s)?`
	);
	if (confirmed && selectedUsers.value.length > 0) {
		alerts.success = "";
		alerts.error = "";
		isLoading.value = true; // Start loading

		try {
			const token = localStorage.getItem("token");
			if (!token) {
				throw new Error("No token found");
			}

			// Updated API URL and request body key
			const response = await axios.delete(`/usersdelete`, {
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
				data: {
					user_ids: selectedUsers.value, // Update to match Laravel's expected key 'user_ids'
				},
			});

			if (response.data.success) {
				// Refresh users list after deletion
				fetchUsers();
				alerts.success = `${selectedUsers.value.length} user(s) deleted successfully!`;
				selectedUsers.value = []; // Clear selected users after deletion
			}
		} catch (error) {
			console.error("Error deleting users:", error);
			alerts.error = "An error occurred while deleting the selected users.";
		} finally {
			isLoading.value = false; // Stop loading
		}
	}
};

// Function to lock/unlock a user with confirmation
const lockUnlockUser = async (userId,status) => {
	const action = status == 1 ? "unlock" : "lock"; // Determine action text
	const confirmed = window.confirm(`Are you sure you want to ${action} this user?`);
	if (confirmed) {
		alerts.success = "";
		alerts.error = "";
		isLoading.value = true; // Start loading
		try {
			// Retrieve the token from local storage
			const token = localStorage.getItem("token");
			if (!token) {
				throw new Error("No token found");
			}

			// Make lock/unlock request to backend
			const response = await axios.put(`/userlockunlock/${userId}`,
			{}, // Empty object for POST data
			{
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			});

			if (response.data.success) {
				// Refresh users list after lock/unlock
				fetchUsers();
				alerts.success = `User ${action}ed successfully!`;
			}
		} catch (error) {
			console.error(`Error ${action}ing user:`, error);
			alerts.error =
				response.data.message || `An error occurred while ${action}ing the user.`;
		} finally {
			isLoading.value = false; // Stop loading
		}
	}
};

const fetchUsers = async (page = 1) => {
	isLoading.value = true; // Start loading
	try {
		// Retrieve the token from local storage
		const token = localStorage.getItem("token");

		// Make sure the token exists before making the request
		if (!token) {
			throw new Error("No token found");
		}
		const response = await axios.get("/userlist", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
			params: {
				pagination_size: paginationSize.value,
				page: page,
				search: searchQuery.value,
				gender: filterGender.value,
				role: filterRole.value,
			},
		});

		if (response.status === 200) {
			users.value = response.data.data;
			pagination.value = {
				currentPage: response.data.current_page,
				lastPage: response.data.last_page,
				total: response.data.total,
			};
		} else {
			console.error("Error fetching user logs:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching user logs:", error);
	} finally {
		isLoading.value = false; // Stop loading
	}
};
const exportFile = async (fileType) => {
	// Set loading state to true while the file is being prepared
	isLoading.value = true;

	// Define URL endpoints for different file types
	const urls = {
		xlsx: "/userxlsx",
		csv: "/usercsv",
	};

	try {
		// Retrieve the authentication token from local storage
		const token = localStorage.getItem("token");
		if (!token) throw new Error("No token found");

		// Make an HTTP GET request to fetch the file
		const response = await axios.get(urls[fileType], {
			headers: { Authorization: `Bearer ${token}` },
			responseType: "blob", // Set response type to 'blob' to handle file downloads
		});

		// Generate filename with the current date
		const today = new Date().toISOString().split("T")[0].replace(/-/g, "_"); // Format: yyyy_mm_dd
		const filename = `${today}_users.${fileType}`;

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
	fetchUsers();
	fetchGender();
	fetchRoles();
});

// Search function
const search = () => {
	searchExecuted.value = true;
	fetchUsers();
};

// Clear search and reset
const clearSearch = () => {
	searchQuery.value = "";
	searchExecuted.value = false;
	fetchUsers();
};

// Reset filters and hide filter forms
const resetFiltersAndHide = () => {
	filterGender.value = "";
	filterRole.value = "";
	hideFilterForms();
	fetchUsers(); // Refetch data without filters
};

// Apply filters
const applyFilters = () => {
	fetchUsers();
};

// Handle pagination button click
const handlePaginationClick = (page) => {
	if (page > 0 && page <= pagination.value.lastPage) {
		fetchUsers(page);
	}
};

// Watch for pagination size changes and refetch data
watch(paginationSize, () => {
	fetchUsers();
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
				case "filterGender":
					filterGender.value = $(this).val(); // Sync selection
					break;
				case "filterRole":
					filterRole.value = $(this).val(); // Sync selection
					break;
				// Add more cases for other select elements if needed
			}
		})
		// Handle the unselecting event to prevent undefined access
		.on("select2:unselecting", function (e) {
			//console.log("Clearing select field:", $(this).attr("id"));
			// Optionally prevent the clearing action (e.preventDefault())
		})
        // Autofocus on the search field when dropdown opens
		.on("select2:open", function () {
			setTimeout(() => {
				let searchField = document.querySelector(".select2-container--open .select2-search__field");
				if (searchField) {
					searchField.focus();
				}
			}, 50); // Slight delay to ensure input is available
		});
	});
};
// Modify the toggle function to include initializeSelect2 as a callback
const handleToggleFilterForms = () => {
	toggleFilterForms(initializeSelect2); // Pass initializeSelect2 as the callback
};
// Watch for roles and gender changes to re-initialize Select2
watch([roles, gender], () => {
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
					<RouterLink to="/userlist" class="text-decoration-none"
						>Users</RouterLink
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
					menuAccess.userAdd ||
					menuAccess.userBulkDelete ||
					menuAccess.userFilter ||
					menuAccess.userExport
				"
			>
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-body p-2">
							<div class="d-flex justify-content-end my-1 my-lg-0">
								<div class="d-flex flex-row gap-2">
									<RouterLink
										v-if="menuAccess.userAdd"
										to="/usercreate"
										class="btn btn-sm btn-primary"
										><i class="fa fa-plus"></i>
										Create
									</RouterLink>
									<!-- Updated Filter button to toggle visibility of filter forms -->
									<button
										class="btn btn-sm btn-info"
										v-if="menuAccess.userFilter"
										@click="handleToggleFilterForms"
									>
										<i class="fa fa-sliders"></i> Filter
									</button>
									<div class="d-flex" v-if="menuAccess.userExport">
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
									<!-- Mass Delete Button -->
									<!-- Mass Delete Button -->
									<button
										v-if="
											menuAccess.userBulkDelete &&
											selectedUsers.length > 0
										"
										class="btn btn-danger btn-sm"
										@click="deleteSelectedUsers"
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
			<div v-if="showFilterForms" class="row">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-body">
							<!-- Row start -->
							<div class="row gx-3">
								<!-- Gender filter -->
								<div class="col-lg-6 col-sm-6 col-12">
									<div class="mb-3">
										<label for="filterGender" class="form-label"
											>Gender</label
										>
										<select
											id="filterGender"
											name="filterGender"
											class="form-select select"
											v-model="filterGender"
										>
											<option value="" disabled>
												Select gender
											</option>
											<option
												v-for="sex in gender"
												:key="sex.gender"
												:value="sex.gender"
											>
												{{ sex.gender }}
											</option>
										</select>
									</div>
								</div>
								<!-- Role filter -->
								<div class="col-lg-6 col-sm-6 col-12">
									<div class="mb-3">
										<label for="filterRole" class="form-label"
											>Role</label
										>
										<select
											id="filterRole"
											name="filterRole"
											class="form-select select"
											v-model="filterRole"
										>
											<option value="" disabled>Select role</option>
											<option
												v-for="role in roles"
												:key="role.id"
												:value="role.id"
											>
											{{ role.name }}
											</option>
										</select>
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
										@change="fetchUsers"
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
													@change="toggleAllUsers($event)"
												/>
											</th>
											<th scope="col">#</th>
											<th scope="col">USER NUMBER/ID</th>
											<th scope="col">NAME</th>
											<th scope="col">GENDER</th>
											<th scope="col">CONTACT</th>
											<th scope="col">EMAIL</th>
											<th scope="col">ROLE</th>
											<th scope="col">STATUS</th>
											<th scope="col">DATE</th>
											<th scope="col">ACTIONS</th>
											<th scope="col" class="control-column"></th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(log, index) in users" :key="index">
											<td>
												<input
													type="checkbox"
													:value="log.id"
													@change="toggleUserSelection(log.id)"
													:checked="
														selectedUsers.includes(log.id)
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
											<td>{{ log.user_number }}</td>
											<td>{{ log.name }}</td>
											<td>{{ log.gender }}</td>
											<td>{{ log?.phone_number }}</td>
											<td>{{ log.email }}</td>
											<td>{{ log.role ? log.role.name : "N/A" }}</td>
											<td>
												<span :class="log.is_locked == 1 ? 'badge bg-danger' : 'badge bg-success'">
													{{ log.is_locked == 1 ? "Locked" : "Unlocked" }}
												</span>
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
																		name: 'UserShow',
																		params: {
																			id: log.id,
																		},
																	}"
																	>View
																</RouterLink>
															</li>
															<div
																v-if="menuAccess.userEdit"
																class="dropdown-divider"
															></div>
															<li
																v-if="menuAccess.userEdit"
															>
																<RouterLink
																	class="dropdown-item"
																	:to="{
																		name:
																			'UserUpdate',
																		params: {
																			id: log.id,
																		},
																	}"
																>
																	Edit</RouterLink
																>
															</li>
															<div
																v-if="
																	menuAccess.userDelete
																"
																class="dropdown-divider"
															></div>
															<li
																v-if="
																	menuAccess.userDelete
																"
															>
																<a
																	class="dropdown-item"
																	href="#"
																	@click.prevent="
																		deleteUser(log.id)
																	"
																	>Delete</a
																>
															</li>
															<div
																v-if="
																	menuAccess.userLockUnlock
																"
																class="dropdown-divider"
															></div>
															<li
																v-if="
																	menuAccess.userLockUnlock
																"
															>
																<a
																	class="dropdown-item"
																	href="#"
																	@click.prevent="
																		lockUnlockUser(log.id,log.is_locked)
																	"
																> {{ log.is_locked == 0 ? "Lock" : "Unlock" }} </a>
															</li>
														</ul>
													</div>
												</div>
											</td>
											<td class="control-column"></td>
										</tr>
										<tr v-if="!isLoading && users.length === 0">
											<th colspan="11" class="text-center">
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
