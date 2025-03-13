<script setup>
import { onMounted, ref, reactive, watch } from "vue";
import { RouterLink } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";
import { PaginationSizes, PaginationSizeOptions } from "@/enums/paginationSizes";
import axios from "@/axios";
import LoadingIndicator from "../../singles/SpinnerGrow.vue";
import { useMenuAccess } from "@/permissions"; // Adjust the path as needed

// Use the menu access composable
const { menuAccess } = useMenuAccess();
const {
} = useCustomUtils();

// Get all the public countries available
const publicCountries = getPublicCountries();

// Function to get the currency based on the country name
const getCurrency = (countryName) => {
    const country = publicCountries.find((c) => c.country === countryName);
    return country ? country.currency : "Currency Not Found";  // Fallback if country is not found
};
const isLoading = ref(false);
const searchQuery = ref("");
const searchExecuted = ref(false);
const clients = ref([]);
const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 });
const paginationSize = ref(PaginationSizes.SMALL);
const paginationSizeOptions = PaginationSizeOptions;
const alerts = reactive({
	success: "",
	error: "",
});
const isMasked = ref(true);

const toggleMask = () => {
    isMasked.value = !isMasked.value;
};

const maskValue = (value, field) => {
    if (isMasked.value) {
        if (field === 'phone') {
        return 'XXX-XXX-XXXX'; // Custom mask for phone
        }
        return 'XXXXX'; // Default mask for other fields
    }
    return value || 'N/A';
};

const fetchClients = async (page = 1) => {
	isLoading.value = true; // Start loading
	try {
		// Retrieve the token from local storage
		const token = localStorage.getItem("token");

		// Make sure the token exists before making the request
		if (!token) {
			throw new Error("No token found");
		}
		const response = await axios.get("/revenuebyclientlist", {
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
			// Assuming the response structure looks like:
			// response.data.data is an array of clients
			// response.data.current_page, response.data.last_page, response.data.total are for pagination
			clients.value = response.data.data.map(client => ({
				// Map the client data correctly, no need to wrap in 'client' object
				client_number: client.client_number || 'N/A',
				business_name: client.business_name || 'N/A',
				total_credit: client.total_credit || 0,
				total_debit: client.total_debit || 0,
				total_revenue: client.total_revenue || 0,
				company_revenue: client.company_revenue || 0,
				consultant_revenue: client.consultant_revenue || 0,
				balance: client.balance || 0,
				country: client.country || 'Unknown', // Add default value if country is missing
				id: client.id || null, // Make sure `id` is available for action links
			}));

			// Update pagination
			pagination.value = {
				currentPage: response.data.current_page,
				lastPage: response.data.last_page,
				total: response.data.total,
			};
		} else {
			console.error("Error fetching client logs:", response.statusText);
			alerts.error = `Error: ${response.statusText}`;
		}
	} catch (error) {
		console.error("Error fetching client logs:", error);
		alerts.error = `Error fetching client logs: ${error.message}`;
	} finally {
		isLoading.value = false; // Stop loading
	}
};

const exportFile = async (fileType) => {
	// Set loading state to true while the file is being prepared
	isLoading.value = true;

	// Define URL endpoints for different file types
	const urls = {
		xlsx: "/revenuebyclientxlsx",
		csv: "/revenuebyclientcsv",
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
		const filename = `${today}_revenuebyclients.${fileType}`;

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
		keepCell: [5],
	});
	fetchClients();
});

// Search function
const search = () => {
	searchExecuted.value = true;
	fetchClients();
};

// Clear search and reset
const clearSearch = () => {
	searchQuery.value = "";
	searchExecuted.value = false;
	fetchClients();
};

// Handle pagination button click
const handlePaginationClick = (page) => {
	if (page > 0 && page <= pagination.value.lastPage) {
		fetchClients(page);
	}
};

// Watch for pagination size changes and refetch data
watch(paginationSize, () => {
	fetchClients();
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
					<RouterLink to="/revenuebyclientlist" class="text-decoration-none"
						>Revenue By Clients</RouterLink
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
					menuAccess.revenueByClientListExport ||
					menuAccess.revenueByClientListMaskToggle
				"
			>
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-body p-2">
							<div class="d-flex justify-content-end my-1 my-lg-0">
								<div class="d-flex flex-row gap-2">
									<button v-if="menuAccess.revenueByClientListMaskToggle" @click="toggleMask" class="btn btn-sm btn-info d-flex align-items-center gap-2">
                                    <i :class="isMasked ? 'bi-eye-slash' : 'bi-eye'"></i>
                                    {{ isMasked ? "Unmask" : "Mask" }}
                                    </button>
									<div class="d-flex" v-if="menuAccess.revenueByClientListExport">
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
										@change="fetchClients"
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
											<th scope="col">CLIENT NUMBER/ID.</th>
											<th scope="col">BUSINESS NAME</th>
											<th scope="col">CREDIT</th>
											<th scope="col">DEBIT</th>
											<th scope="col">BALANCE</th>
											<th scope="col">TOTAL REVENUE</th>
											<th scope="col">COMPANY REVENUE</th>
											<th scope="col">CONSULTANT REVENUE</th>
											<th scope="col">CURRENCY</th>
											<th scope="col" class="control-column"></th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(log, index) in clients" :key="index">
											<th scope="row">
												{{
													(pagination.currentPage - 1) * paginationSize + index + 1
												}}
											</th>
											<td>{{ log.client_number }}</td>
											<td>{{ log.business_name }}</td>
											<td class="text-end">{{ maskValue(Number(log.total_credit || 0).toLocaleString(), 'credit') }}</td>
											<td class="text-end">{{ maskValue(Number(log.total_debit || 0).toLocaleString(), 'debit') }}</td>
											<td class="text-end">{{ maskValue(Number(log.balance || 0).toLocaleString(), 'balance') }}</td>
											<td class="text-end">{{ maskValue(Number(log.total_revenue || 0).toLocaleString(), 'debit') }}</td>
											<td class="text-end">{{ maskValue(Number(log.company_revenue || 0).toLocaleString(), 'debit') }}</td>
											<td class="text-end">{{ maskValue(Number(log.consultant_revenue || 0).toLocaleString(), 'debit') }}</td>
											<td>{{ getCurrency(log.country) }}</td>
											<td class="control-column"></td>
										</tr>
										<tr v-if="clients.length === 0">
											<th colspan="9" class="text-center">
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
												pagination.currentPage * paginationSize, pagination.total
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
