<script setup>
import { computed, onMounted, ref, reactive, watch } from "vue";
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
	parseDate,
} = useCustomUtils();
// Access Vuex store
const store = useStore();
const isLoading = ref(false);
const searchQuery = ref("");
const searchExecuted = ref(false);
const suppliers = ref([]);
const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 });
const paginationSize = ref(PaginationSizes.SMALL);
const paginationSizeOptions = PaginationSizeOptions;
const alerts = reactive({
	success: "",
	error: "",
});
// Function to delete a supplier with confirmation
const deleteSupplier = async (supplierId) => {
	const confirmed = window.confirm("Are you sure you want to delete this supplier?");
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
			const response = await axios.delete(`/supplierdelete/${supplierId}`, {
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			});

			if (response.data.success) {
				// Refresh suppliers list after deletion
				fetchSuppliers();
				alerts.success = "Ssupplier deleted successfully!";
			}
		} catch (error) {
			console.error("Error deleting supplier:", error);
			alerts.error =
				response.data.message || "An error occurred while deleting the supplier.";
		} finally {
			isLoading.value = false; // Stop loading
		}
	}
};
// Track selected suppliers for mass delete
const selectedSuppliers = ref([]);

// Toggle supplier selection
const toggleSupplierSelection = (supplierId) => {
	if (selectedSuppliers.value.includes(supplierId)) {
		selectedSuppliers.value = selectedSuppliers.value.filter((id) => id !== supplierId);
	} else {
		selectedSuppliers.value.push(supplierId);
	}
};

// Toggle all suppliers selection
const toggleAllSuppliers = (event) => {
	if (event.target.checked) {
		selectedSuppliers.value = suppliers.value.map((supplier) => supplier.id);
	} else {
		selectedSuppliers.value = [];
	}
};

// Mass delete function
const deleteSelectedSuppliers = async () => {
	const confirmed = window.confirm(
		`Are you sure you want to delete ${selectedSuppliers.value.length} supplier(s)?`
	);
	if (confirmed && selectedSuppliers.value.length > 0) {
		alerts.success = "";
		alerts.error = "";
		isLoading.value = true; // Start loading

		try {
			const token = localStorage.getItem("token");
			if (!token) {
				throw new Error("No token found");
			}

			// Updated API URL and request body key
			const response = await axios.delete(`/suppliersdelete`, {
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
				data: {
					supplier_ids: selectedSuppliers.value, // Update to match Laravel's expected key 'supplier_ids'
				},
			});

			if (response.data.success) {
				// Refresh suppliers list after deletion
				fetchSuppliers();
				alerts.success = `${selectedSuppliers.value.length} supplier(s) deleted successfully!`;
				selectedSuppliers.value = []; // Clear selected suppliers after deletion
			}
		} catch (error) {
			console.error("Error deleting suppliers:", error);
			alerts.error = "An error occurred while deleting the selected suppliers.";
		} finally {
			isLoading.value = false; // Stop loading
		}
	}
};

const fetchSuppliers = async (page = 1) => {
	isLoading.value = true; // Start loading
	try {
		// Retrieve the token from local storage
		const token = localStorage.getItem("token");

		// Make sure the token exists before making the request
		if (!token) {
			throw new Error("No token found");
		}
		const response = await axios.get("/supplierlist", {
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
			suppliers.value = response.data.data;
			pagination.value = {
				currentPage: response.data.current_page,
				lastPage: response.data.last_page,
				total: response.data.total,
			};
		} else {
			console.error("Error fetching supplier logs:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching supplier logs:", error);
	} finally {
		isLoading.value = false; // Stop loading
	}
};
const exportFile = async (fileType) => {
	// Set loading state to true while the file is being prepared
	isLoading.value = true;

	// Define URL endpoints for different file types
	const urls = {
		xlsx: "/supplierxlsx",
		csv: "/suppliercsv",
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
		const filename = `${today}_suppliers.${fileType}`;

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
	fetchSuppliers();
});

// Search function
const search = () => {
	searchExecuted.value = true;
	fetchSuppliers();
};

// Clear search and reset
const clearSearch = () => {
	searchQuery.value = "";
	searchExecuted.value = false;
	fetchSuppliers();
};

// Handle pagination button click
const handlePaginationClick = (page) => {
	if (page > 0 && page <= pagination.value.lastPage) {
		fetchSuppliers(page);
	}
};

// Watch for pagination size changes and refetch data
watch(paginationSize, () => {
	fetchSuppliers();
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
					<RouterLink to="/supplierlist" class="text-decoration-none"
						>Suppliers</RouterLink
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
					menuAccess.supplierAdd ||
					menuAccess.supplierBulkDelete ||
					menuAccess.supplierExport
				"
			>
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-body p-2">
							<div class="d-flex justify-content-end my-1 my-lg-0">
								<div class="d-flex flex-row gap-2">
									<RouterLink
										v-if="menuAccess.supplierAdd"
										to="/suppliercreate"
										class="btn btn-sm btn-primary"
										><i class="fa fa-plus"></i>
										Create
									</RouterLink>									
									<div class="d-flex" v-if="menuAccess.supplierExport">
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
											menuAccess.supplierBulkDelete &&
											selectedSuppliers.length > 0
										"
										class="btn btn-danger btn-sm"
										@click="deleteSelectedSuppliers"
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
										@change="fetchSuppliers"
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
													@change="toggleAllSuppliers($event)"
												/>
											</th>
											<th scope="col">#</th>
											<th scope="col">SUPPLIER NUMBER/ID</th>
											<th scope="col">NAME</th>
											<th scope="col">CONTACT</th>
											<th scope="col">EMAIL</th>
											<th scope="col">TIN</th>
											<th scope="col">COUNTRY</th>
											<th scope="col">ADDRESS</th>
											<th scope="col">DATE</th>
											<th scope="col">ACTIONS</th>
											<th scope="col" class="control-column"></th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(log, index) in suppliers" :key="index">
											<td>
												<input
													type="checkbox"
													:value="log.id"
													@change="toggleSupplierSelection(log.id)"
													:checked="
														selectedSuppliers.includes(log.id)
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
											<td>{{ log.supplier_number }}</td>
											<td>{{ log.name }}</td>
											<td>{{ log?.phone_number }}</td>
											<td>{{ log.email }}</td>
											<td>{{ log.tin }}</td>
											<td>{{ log.country }}</td>
											<td>{{ log.address }}</td>
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
															<li
																v-if="menuAccess.supplierEdit"
															>
																<RouterLink
																	class="dropdown-item"
																	:to="{
																		name:
																			'SupplierUpdate',
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
																	menuAccess.supplierDelete
																"
																class="dropdown-divider"
															></div>
															<li
																v-if="
																	menuAccess.supplierDelete
																"
															>
																<a
																	class="dropdown-item"
																	href="#"
																	@click.prevent="
																		deleteSupplier(log.id)
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
										<tr v-if="!isLoading && suppliers.length === 0">
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
