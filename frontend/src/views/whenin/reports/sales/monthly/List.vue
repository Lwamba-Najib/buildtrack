<script setup>
import { onMounted, ref, reactive, watch, nextTick, computed } from "vue";
import { RouterLink } from "vue-router";
import { useStore } from 'vuex';
import { useCustomUtils } from "@/utils/customUtils";
import { PaginationSizes, PaginationSizeOptions } from "@/enums/paginationSizes";
import axios from "@/axios";
import LoadingIndicator from "../../../singles/SpinnerGrow.vue";
import { useMenuAccess } from "@/permissions";
import DatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'

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
const selectedMonth = ref("");
const totalSales = ref("0");
const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 });
const paginationSize = ref(PaginationSizes.SMALL);
const paginationSizeOptions = PaginationSizeOptions;
const filterMonth = ref(""); // For API calls
const tempFilterMonth = ref(""); // Temporary storage before submit
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

const fetchReportMonthlySales = async (page = 1) => {
	isLoading.value = true;
	try {
		const token = getToken();

		const response = await axios.get("/reportmonthlysaleslist", {
			headers: {
				Authorization: `Bearer ${token}`,
			},
			params: {
				pagination_size: paginationSize.value,
				page: page,
				search: searchQuery.value,
				month: filterMonth.value,
			},
		});

		if (response.status === 200) {
			sales.value = response.data.data?.data || [];
			selectedMonth.value = response.data.selected_month || new Date().toISOString().slice(0, 7);
			totalSales.value = response.data.total_sales;

			pagination.value = {
				currentPage: response.data.data.current_page,
				lastPage: response.data.data.last_page,
				total: response.data.data.total,
			};
		} else {
			console.error("Error fetching monthly sales report:", response.statusText);
		}
	} catch (error) {
		handleError(error);
	} finally {
		isLoading.value = false;
	}
};

const exportFile = async (fileType) => {
	isLoading.value = true;
	const urls = {
		xlsx: "/reportmonthlysalesxlsx",
		csv: "/reportmonthlysalescsv",
	};

	try {
		const token = getToken();
		const response = await axios.get(urls[fileType], {
			headers: { Authorization: `Bearer ${token}` },
			responseType: "blob",
			params: {
				month: filterMonth.value
			}
		});

		const filename = `${filterMonth.value || new Date().toISOString().slice(0, 7)}_reportmonthlysales.${fileType}`;
		const disposition = response.headers["content-disposition"];
		const filenameMatch = disposition
			? disposition.match(/filename="([^"]*)"/)
			: null;
		const finalFilename = filenameMatch ? filenameMatch[1] : filename;

		const urlBlob = window.URL.createObjectURL(new Blob([response.data]));
		const link = document.createElement("a");
		link.href = urlBlob;
		link.download = finalFilename;
		document.body.appendChild(link);
		link.click();
		link.remove();
		window.URL.revokeObjectURL(urlBlob);
	} catch (error) {
		console.error(`Error exporting ${fileType} file:`, error);
	} finally {
		isLoading.value = false;
	}
};

const exportXlsx = () => exportFile("xlsx");
const exportCsv = () => exportFile("csv");

// Initial data fetch
onMounted(() => {
	new Podtable("#table", {
		keepCell: [9],
	});
	fetchReportMonthlySales();
});

// Search function
const search = () => {
	searchExecuted.value = true;
	fetchReportMonthlySales();
};

// Clear search and reset
const clearSearch = () => {
	searchQuery.value = "";
	searchExecuted.value = false;
	fetchReportMonthlySales();
};

// Reset filters and hide filter forms
const resetFiltersAndHide = () => {
	filterMonth.value = "";
	tempFilterMonth.value = "";
	hideFilterForms();
	fetchReportMonthlySales();
};

// Apply filters
const applyFilters = () => {
	if (tempFilterMonth.value) {
		const date = new Date(tempFilterMonth.value);
		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		filterMonth.value = `${year}-${month}`;
	} else {
		filterMonth.value = "";
	}
	fetchReportMonthlySales();
};

// Handle pagination button click
const handlePaginationClick = (page) => {
	if (page > 0 && page <= pagination.value.lastPage) {
		fetchReportMonthlySales(page);
	}
};

// Watch for pagination size changes and refetch data
watch(paginationSize, () => {
	fetchReportMonthlySales();
});

// Format display value
const displayMonth = computed(() => {
	if (!tempFilterMonth.value) return 'Select month';
	const date = new Date(tempFilterMonth.value);
	return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
});

const handleToggleFilterForms = () => {
	toggleFilterForms();
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
					<RouterLink to="/reportmonthlysaleslist" class="text-decoration-none">Reports</RouterLink>
				</li>
				<li class="breadcrumb-item text-secondary" aria-current="page">Monthly Sales</li>
			</ol>
			<!-- Breadcrumb end -->
		</div>
		<!-- App Hero header ends -->

		<!-- App body starts -->
		<div class="app-body">
			<!-- Row start -->
			<div class="row" v-if="
				menuAccess.reportMonthlySalesFilter ||
				menuAccess.reportMonthlySalesExport
			">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-body p-2">
							<div class="d-flex justify-content-end my-1 my-lg-0">
								<div class="d-flex flex-row gap-2">
									<!-- Filter button -->
									<button class="btn btn-sm btn-info" v-if="menuAccess.reportMonthlySalesFilter"
										@click="handleToggleFilterForms">
										<i class="fa fa-sliders"></i> Filter
									</button>
									<div class="d-flex" v-if="menuAccess.reportMonthlySalesExport">
										<div class="dropdown">
											<button type="button" class="btn btn-success btn-sm dropdown-toggle"
												data-bs-toggle="dropdown">
												<i class="fa fa-download"></i>
												Export
											</button>
											<ul class="dropdown-menu dropdown-menu-end" style="right: 0; left: auto">
												<li>
													<a class="dropdown-item" href="#"
														@click.prevent="exportXlsx">XLSX</a>
												</li>
												<div class="dropdown-divider"></div>
												<li>
													<a class="dropdown-item" href="#" @click.prevent="exportCsv">CSV</a>
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

			<!-- Filter Form Row -->
			<div v-if="showFilterForms" class="row">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-body">
							<div class="row gx-3">
								<!-- Month filter -->
								<div class="col-lg-12 col-sm-4 col-12">
									<div class="mb-3">
										<label for="filterMonth" class="form-label">Month</label>
										<DatePicker v-model="tempFilterMonth" month-picker text-input
											text-input-format="yyyy-MM" format="yyyy-MM" placeholder="Select month"
											:enable-time-picker="false" :max-date="new Date()" model-type="yyyy-MM" />
									</div>
								</div>
							</div>
						</div>
						<div class="card-footer">
							<div class="d-flex justify-content-between align-items-center my-2 my-lg-0">
								<button type="button" class="btn btn-sm btn-danger" @click="resetFiltersAndHide">
									<i class="fa fa-times"></i> Cancel
								</button>
								<button type="button" class="btn btn-sm btn-success" @click="applyFilters">
									<i class="fa fa-send"></i> Submit
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Summary Cards Row -->
			<div class="row gx-3">
				<div class="col-xl-3 col-sm-6 col-12">
					<div class="card mb-3">
						<div class="card-body">
							<strong class="d-flex align-items-center justify-content-between">
								Month
								<span class="text-default">{{ selectedMonth || 'Current Month' }}</span>
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

			<!-- Main Table Row -->
			<div class="row gx-3">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-header">
							<div class="d-flex justify-content-between align-items-center my-2 my-lg-0">
								<div class="form-inline">
									<!-- Pagination size dropdown -->
									<select name="paginationSize" class="form-select form-select-sm"
										v-model="paginationSize" @change="fetchReportMonthlySales">
										<option v-for="size in paginationSizeOptions" :key="size" :value="size">
											{{ size }}
										</option>
									</select>
								</div>
								<div class="input-group mb-0 filter">
									<input name="searchQuery" type="text" class="form-control form-control-sm"
										placeholder="Search" v-model="searchQuery" />
									<div class="input-group-append">
										<button class="btn btn-primary btn-sm" type="button" @click="search">
											<i class="fa fa-search"></i>
										</button>
										<button class="btn btn-secondary btn-sm" type="button" @click="clearSearch"
											v-if="searchExecuted">
											<i class="fa fa-times"></i>
										</button>
									</div>
								</div>
							</div>
						</div>
						<div class="card-body">
							<!-- Success Alert -->
							<div v-if="alerts.success"
								class="alert border border-success alert-dismissible fade show text-success"
								role="alert">
								{{ alerts.success }}
								<button type="button" class="btn-close" data-bs-dismiss="alert"
									aria-label="Close"></button>
							</div>

							<!-- Error Alert -->
							<div v-if="alerts.error"
								class="alert border border-danger alert-dismissible fade show text-danger" role="alert">
								{{ alerts.error }}
								<button type="button" class="btn-close" data-bs-dismiss="alert"
									aria-label="Close"></button>
							</div>

							<!-- Table Container -->
							<div class="position-relative">
								<LoadingIndicator :isLoading="isLoading" />
								<table id="table" class="table align-middle table-hover m-0">
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
											<td class="text-end">{{ Number(log.sale.discount).toLocaleString() || 0 }}%
											</td>
											<td class="text-end">{{ Number(log.total_price).toLocaleString() || 0 }}
											</td>
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

								<!-- Pagination -->
								<div v-if="pagination.total > 0" class="d-flex justify-content-between mt-2">
									<div>
										{{
											`Showing ${pagination.currentPage > 1 ? (pagination.currentPage - 1) *
												paginationSize + 1 :
												1
											} to ${Math.min(
												pagination.currentPage * paginationSize,
												pagination.total
											)} of ${pagination.total} results`
										}}
									</div>
									<nav aria-label="Page navigation example">
										<ul class="pagination">
											<li class="page-item" v-if="pagination.currentPage > 1">
												<button class="page-link btn-sm" @click="handlePaginationClick(1)">
													&laquo;&laquo;
												</button>
											</li>
											<li class="page-item" v-if="pagination.currentPage > 1">
												<button class="page-link btn-sm"
													@click="handlePaginationClick(pagination.currentPage - 1)">
													&laquo;
												</button>
											</li>

											<template v-if="pagination.lastPage > 1">
												<template
													v-for="pageNumber in Math.min(pagination.lastPage, pagination.currentPage + 4)">
													<li :key="pageNumber" class="page-item" :class="{
														active:
															pageNumber === pagination.currentPage,
													}" v-if="pageNumber >= pagination.currentPage && pageNumber <= pagination.currentPage + 3">
														<button class="page-link btn-sm"
															@click="handlePaginationClick(pageNumber)">
															{{ pageNumber }}
														</button>
													</li>
												</template>
											</template>

											<li class="page-item" v-if="pagination.currentPage < pagination.lastPage">
												<button class="page-link btn-sm"
													@click="handlePaginationClick(pagination.currentPage + 1)">
													&raquo;
												</button>
											</li>
											<li class="page-item" v-if="pagination.currentPage < pagination.lastPage">
												<button class="page-link btn-sm"
													@click="handlePaginationClick(pagination.lastPage)">
													&raquo;&raquo;
												</button>
											</li>
										</ul>
									</nav>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</template>

<style scoped>
/* Custom styles for the date picker */
.dp__month_year_row {
	justify-content: center;
}

.dp__month_year_select {
	font-weight: bold;
}

.dp__instance_calendar {
	width: 100%;
}

/* Ensure the date picker appears above other elements */
.dp__menu {
	z-index: 10000 !important;
}
</style>