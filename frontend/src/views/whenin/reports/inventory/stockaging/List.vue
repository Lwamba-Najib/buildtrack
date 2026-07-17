<script setup>
import { onMounted, ref, reactive, watch, nextTick } from "vue";
import { RouterLink } from "vue-router";
import { useStore } from 'vuex';
import { useCustomUtils } from "@/utils/customUtils";
import { PaginationSizes, PaginationSizeOptions } from "@/enums/paginationSizes";
import axios from "@/axios";
import LoadingIndicator from "../../../singles/SpinnerGrow.vue";
import { useMenuAccess } from "@/permissions";

const { menuAccess } = useMenuAccess();
const {
    showFilterForms,
    toggleFilterForms,
    hideFilterForms,
    parseDate,
} = useCustomUtils();

const store = useStore();
const isLoading = ref(false);
const searchQuery = ref("");
const searchExecuted = ref(false);
const stocks = ref([]);
const totalValue = ref("0");
const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 });
const paginationSize = ref(PaginationSizes.SMALL);
const paginationSizeOptions = PaginationSizeOptions;
const alerts = reactive({
    success: "",
    error: "",
});
const agingPeriod = ref("30");
const customDays = ref(null);
const thresholdDate = ref("");
const agingThreshold = ref("30 days");

const getToken = () => {
    const token = localStorage.getItem("token");
    if (!token) throw new Error("No token found");
    return token;
};

const handleError = (error, alertField = "error") => {
    alerts[alertField] = error.response?.data?.message || "An error occurred. Please try again later.";
    console.error("API Error:", error);
};

const fetchReportStockAging = async (page = 1) => {
    isLoading.value = true;
    try {
        const token = getToken();

        const response = await axios.get("/reportstockaginglist", {
            headers: {
                Authorization: `Bearer ${token}`,
            },
            params: {
                pagination_size: paginationSize.value,
                page: page,
                search: searchQuery.value,
                aging_period: agingPeriod.value,
                custom_days: agingPeriod.value === 'custom' ? customDays.value : null
            },
        });

        if (response.status === 200) {
            stocks.value = response.data.data?.data || [];
            totalValue.value = response.data.total_value;
            thresholdDate.value = response.data.threshold_date;
            agingThreshold.value = response.data.aging_threshold;

            pagination.value = {
                currentPage: response.data.data.current_page,
                lastPage: response.data.data.last_page,
                total: response.data.data.total,
            };
        } else {
            console.error("Error fetching stock aging report:", response.statusText);
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
        xlsx: "/reportstockagingxlsx",
        csv: "/reportstockagingcsv",
    };

    try {
        const token = getToken();
        const response = await axios.get(urls[fileType], {
            headers: { Authorization: `Bearer ${token}` },
            responseType: "blob",
            params: {
                aging_period: agingPeriod.value,
                custom_days: agingPeriod.value === 'custom' ? customDays.value : null
            }
        });

        const today = new Date().toISOString().split("T")[0].replace(/-/g, "_");
        const filename = `${today}_reportstockaging.${fileType}`;

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

onMounted(() => {
    new Podtable("#table", {
        keepCell: [10],
    });
    fetchReportStockAging();
});

const search = () => {
    searchExecuted.value = true;
    fetchReportStockAging();
};

const clearSearch = () => {
    searchQuery.value = "";
    searchExecuted.value = false;
    fetchReportStockAging();
};

const resetFiltersAndHide = () => {
    agingPeriod.value = "30";
    customDays.value = null;
    hideFilterForms();
	fetchReportStockAging();
};

const applyFilters = () => {
    if (agingPeriod.value === 'custom' && !customDays.value) {
        alerts.error = "Please enter the number of days for custom aging period";
        return;
    }
    fetchReportStockAging();
};

const handlePaginationClick = (page) => {
    if (page > 0 && page <= pagination.value.lastPage) {
        fetchReportStockAging(page);
    }
};

// Removed the watch on agingPeriod to prevent auto-submit

const handleToggleFilterForms = () => {
    toggleFilterForms();
};

// Method to determine aging status style
const getAgingStatus = (days) => {
    if (days <= 7) return { text: "0-7 days", class: "badge bg-success" };
    if (days <= 30) return { text: "8-30 days", class: "badge bg-info" };
    if (days <= 60) return { text: "31-60 days", class: "badge bg-primary" };
    if (days <= 90) return { text: "61-90 days", class: "badge bg-warning" };
    if (days <= 180) return { text: "91-180 days", class: "badge bg-orange" };
    if (days <= 365) return { text: "181-365 days", class: "badge bg-danger" };
    return { text: "Over 1 year", class: "badge bg-dark" };
};
</script>

<template>
    <section>
        <div class="app-hero-header d-flex align-items-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <i class="bi bi-house lh-1 pe-3 me-3 border-end border-dark"></i>
                    <RouterLink to="/home" class="text-decoration-none">Home</RouterLink>
                </li>
                <li class="breadcrumb-item">
                    <RouterLink to="/reportstockaginglist" class="text-decoration-none">Reports</RouterLink>
                </li>
                <li class="breadcrumb-item text-secondary" aria-current="page">Stock Aging Report</li>
            </ol>
        </div>

        <div class="app-body">
            <div class="row" v-if="menuAccess.reportStockAgingExport || menuAccess.reportStockAgingFilter">
                <div class="col-xxl-12">
                    <div class="card mb-3">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-end my-1 my-lg-0">
                                <div class="d-flex flex-row gap-2">
                                    <button class="btn btn-sm btn-info" v-if="menuAccess.reportStockAgingFilter"
                                        @click="handleToggleFilterForms">
                                        <i class="fa fa-sliders"></i> Filter
                                    </button>
                                    <div class="d-flex" v-if="menuAccess.reportStockAgingExport">
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

            <div v-if="showFilterForms" class="row">
                <div class="col-xxl-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row gx-3">
                                <div class="col-lg-6 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label for="agingPeriod" class="form-label">Aging Period</label>
                                        <select class="form-select" v-model="agingPeriod">
                                            <option value="7">7 Days</option>
                                            <option value="30">30 Days</option>
                                            <option value="60">60 Days</option>
                                            <option value="90">90 Days</option>
                                            <option value="180">180 Days</option>
                                            <option value="365">365 Days</option>
                                            <option value="custom">Custom Days</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-4 col-12" v-if="agingPeriod === 'custom'">
                                    <div class="mb-3">
                                        <label for="customDays" class="form-label">Custom Days</label>
                                        <input type="number" class="form-control" v-model="customDays" 
                                            placeholder="Enter days" min="1" />
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

            <div class="row gx-3">
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <strong class="d-flex align-items-center justify-content-between">
                                Total Value (UGX)
                                <span class="text-default">{{ Number(totalValue).toLocaleString() || 0 }}</span>
                            </strong>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <strong class="d-flex align-items-center justify-content-between">
                                Aging Threshold
                                <span class="text-default">{{ agingThreshold }}</span>
                            </strong>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <strong class="d-flex align-items-center justify-content-between">
                                Threshold Date
                                <span class="text-default">{{ thresholdDate }}</span>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row gx-3">
                <div class="col-xxl-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center my-2 my-lg-0">
                                <div class="form-inline">
                                    <select name="paginationSize" class="form-select form-select-sm"
                                        v-model="paginationSize" @change="fetchReportStockAging">
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
                            <div v-if="alerts.success"
                                class="alert border border-success alert-dismissible fade show text-success"
                                role="alert">
                                {{ alerts.success }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>

                            <div v-if="alerts.error"
                                class="alert border border-danger alert-dismissible fade show text-danger" role="alert">
                                {{ alerts.error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>

                            <div class="position-relative">
                                <LoadingIndicator :isLoading="isLoading" />
                                <table id="table" class="table align-middle table-hover m-0">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">PRODUCT</th>
                                            <th scope="col">BATCH NUMBER</th>
                                            <th scope="col">BRAND</th>
                                            <th scope="col">UNIT OF MEASUREMENT</th>
                                            <th scope="col">FIRST RECEIVED</th>
                                            <th scope="col">DAYS IN STOCK</th>
                                            <th scope="col">AGING PERIOD</th>
                                            <th scope="col">QTY IN STOCK</th>
                                            <th scope="col">UNIT PRICE</th>
                                            <th scope="col">TOTAL VALUE</th>
                                            <th scope="col" class="control-column"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(log, index) in stocks" :key="index">
                                            <th scope="row">
                                                {{ (pagination.currentPage - 1) * paginationSize + index + 1 }}
                                            </th>
                                            <td>{{ log.product.name || "N/A" }}</td>
                                            <td>{{ log.batch_number || "N/A" }}</td>
                                            <td>{{ log.brand.name || "N/A" }}</td>
                                            <td>{{ log.measurement.name || "N/A" }}</td>
                                            <td>{{ log.first_received_date || "N/A" }}</td>
                                            <td class="text-end">{{ log.days_in_stock || 0 }}</td>
                                            <td>
                                                <span :class="getAgingStatus(log.days_in_stock).class" class="badge">
                                                    {{ getAgingStatus(log.days_in_stock).text }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ Number(log.balance).toLocaleString() || 0 }}</td>
                                            <td class="text-end">{{ Number(log.unit_price).toLocaleString() || 0 }}</td>
                                            <td class="text-end">{{ Number(log.total_value).toLocaleString() || 0 }}</td>
                                            <td class="control-column"></td>
                                        </tr>
                                        <tr v-if="!isLoading && stocks.length === 0">
                                            <th colspan="12" class="text-center">
                                                No records found.
                                            </th>
                                        </tr>
                                    </tbody>
                                </table>

                                <div v-if="pagination.total > 0" class="d-flex justify-content-between mt-2">
                                    <div>
                                        {{
                                            `Showing ${pagination.currentPage > 1 ? (pagination.currentPage - 1) *
                                                paginationSize + 1 : 1
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
                                                        active: pageNumber === pagination.currentPage,
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
.badge.bg-orange {
    background-color: #fd7e14;
    color: white;
}

.card-body strong {
    font-size: 0.9rem;
}

.text-default {
    font-weight: bold;
    color: #333;
}
</style>