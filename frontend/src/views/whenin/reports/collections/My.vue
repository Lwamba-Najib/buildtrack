<script setup>
import { onMounted, ref, reactive, computed } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import axios from '@/axios';
import { useStore } from 'vuex';
import LoadingIndicator from '../../singles/SpinnerGrow.vue';
import * as textTransform from '@/utils/textTransform.js';
import { useCustomUtils } from "@/utils/customUtils";
import { useMenuAccess } from "@/permissions"; // Adjust the path as needed
// Use the menu access composable
const { menuAccess } = useMenuAccess();
const { } = useCustomUtils();

const route = useRoute();
const router = useRouter();
// Access Vuex store
const store = useStore();
const clientId = computed(() => store.state.clientId); // Get clientId from Vuex store

const headers = ref({}); // Empty object as default
const transactions = ref([]);    // Empty array as default
const isLoading = ref(true);

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

const alerts = reactive({
	success: "",
	error: "",
    otp: "",
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

const fetchDetails = async () => {
    isLoading.value = true; // Start loading
    try {
        // Retrieve the token from local storage
        const token = getToken();

        // Fetch the specific floatreport by batch number
        const response = await axios.get(`/collectionreportshow/${clientId.value}`, {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the Authorization header
            },
        });
        if (response.status === 200 && response.data.success) {
            headers.value = response.data.data.titles || {};
            transactions.value = response.data.data.summaries || [];
        } else {
            console.error('Error fetching collection report:', response.statusText);
        }
    } catch (error) {
        handleError(error); // Handle error using centralized error handle
        floatreport.value = {};
        transactions.value = [];
    } finally {
        isLoading.value = false; // Stop loading
    }
};
// Reactive state for totals
const totals = ref({
	"MTN Ghana": 0,
	"AIRTELTIGO AT": 0,
	"TELECEL": 0,
	"MTN Uganda": 0,
	"AIRTEL Uganda": 0,
});

// Fetch totals function
const fetchTotalCredits = async (mno = null) => {
    isLoading.value = true; // Start loading

    try {
        const token = getToken(); // Retrieve the token
        
        let url = `/collectionreporttotalcredit/${clientId.value}`;  // No need to include clientId in the URL
        if (mno) {
            url += `/${mno}`;
        }

        const response = await axios.get(url, {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the Authorization header
            },
        });

        if (response.status === 200 && response.data.success) {
            const data = response.data.data;
            // Populate totals from the response
            if (mno) {
                totals.value[mno] = data.total || 0;
            } else {
                totals.value = {
                    "MTN Ghana": data["MTN Ghana"] || 0,
                    "AIRTELTIGO AT": data["AIRTELTIGO AT"] || 0,
                    "TELECEL": data["TELECEL"] || 0,
                    "MTN Uganda": data["MTN Uganda"] || 0,
                    "AIRTEL Uganda": data["AIRTEL Uganda"] || 0,
                };
            }
        } else {
            alerts.error = response.data.message || "Failed to load collection totals.";
        }
    } catch (error) {
        handleError(error);
    } finally {
        isLoading.value = false; // Stop loading
    }
};

const computeTotals = (summary) => {
    // Compute total amount, and total for the transactions under a specific date summary
    const totals = summary.transactions.reduce(
        (acc, transaction) => {
        acc.total = Number(transaction.total || acc.total); // Use the latest total
        return acc;
        },
        { amount: 0, total: 0 }
    );
    return totals;
};

// Method to trigger PDF download
const exportPDF = async () => {
    isLoading.value = true; // Set loading state to true while preparing the file
    try {
        // Retrieve the token from local storage
        const token = getToken();

        // Make an HTTP GET request to fetch the PDF file
        const response = await axios.get(`/collectionreportmypdf/${clientId.value}`, {
            headers: {
                Authorization: `Bearer ${token}`,
            },
            responseType: 'blob', // Set response type to 'blob' for handling file downloads
        });

        // Generate filename with the current date
        const today = new Date().toISOString().split('T')[0].replace(/-/g, '_'); // Format: yyyy_mm_dd
        const id = clientId.value;
        const filename = `${today}_collectionreportmy_${id}.pdf`;

        // Extract filename from Content-Disposition header if available
        const disposition = response.headers['content-disposition'];
        const filenameMatch = disposition ? disposition.match(/filename="([^"]*)"/) : null;
        const finalFilename = filenameMatch ? filenameMatch[1] : filename;

        // Create a URL for the blob and trigger a download
        const urlBlob = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = urlBlob;
        link.download = finalFilename; // Set the file name for download
        document.body.appendChild(link);
        link.click(); // Programmatically click the link to trigger download
        link.remove(); // Remove the link from the DOM
        window.URL.revokeObjectURL(urlBlob); // Clean up the object URL
    } catch (error) {
        handleError(error); // Handle error using centralized error handle
    } finally {
        isLoading.value = false; // Reset loading state after processing
    }
};
// Function to scroll to the top
const scrollToTop = () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth', // Smooth scrolling
    });
};

onMounted(() => {
    new Podtable("#table");
    fetchTotalCredits();
    fetchDetails();
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
                    <RouterLink to="/collectionreportmy" class="text-decoration-none">Collections Report</RouterLink>
                </li>
                <li class="breadcrumb-item text-secondary" aria-current="page">View</li>
            </ol>
            <!-- Breadcrumb end -->
        </div>
        <!-- App Hero header ends -->

        <!-- App body starts -->
        <div class="app-body">
            <!-- Row start -->
            <div class="row" 
                v-if="
					menuAccess.collectionReportMyMaskToggle ||
					menuAccess.collectionReportMyExport
				">
                <div class="col-xxl-12">
                    <div class="card mb-3">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-end my-1 my-lg-0">
                                <div class="d-flex flex-row gap-2">
                                    <button v-if="menuAccess.collectionReportMyMaskToggle" @click="toggleMask" class="btn btn-sm btn-info d-flex align-items-center gap-2">
                                    <i :class="isMasked ? 'bi-eye-slash' : 'bi-eye'"></i>
                                    {{ isMasked ? "Unmask" : "Mask" }}
                                    </button>
                                    <div class="d-flex" v-if="menuAccess.collectionReportMyExport">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-success btn-sm dropdown-toggle"
                                                data-bs-toggle="dropdown"><i class="fa fa-download"></i>
                                                Export
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" style="right: 0; left: auto;">
                                                <li>
                                                    <a class="dropdown-item" href="#" @click.prevent="exportPDF">PDF</a>
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
                            <h5 class="card-title">View Collection Transfer Report</h5>
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
                            <!-- Row start -->
                            <div class="row gx-3">
                                <div class="col-12">
                                    <div class="mb-3 position-relative">
                                        <!-- Display the LoadingIndicator component -->
                                        <LoadingIndicator :isLoading="isLoading" />
                                        <div class="d-flex justify-content-between align-items-center my-2 my-lg-0">
                                            <div v-if="headers">
                                                <h3>{{ textTransform.toUcwords(headers.client_name || 'N/A') }}</h3>
                                                <br>
                                                <h6><span class="badge rounded-pill bg-success">Total Collections</span></h6>
                                                <!-- Row start -->
                                                <div class="row gx-3" v-if="headers.client_country==='Ghana'">
                                                    <h5>MTN Ghana: {{ new Intl.NumberFormat().format(totals["MTN Ghana"]) }} GHS</h5>
                                                    <h5>AIRTELTIGO AT: {{ new Intl.NumberFormat().format(totals["AIRTELTIGO AT"]) }} GHS</h5>
                                                    <h5>TELECEL: {{ new Intl.NumberFormat().format(totals["TELECEL"]) }} GHS</h5>
                                                </div>
                                                <!-- Row end -->
                                                <!-- Row start -->
                                                <div class="row gx-3" v-if="headers.client_country==='Uganda'">
                                                    <h5>MTN Uganda: {{ new Intl.NumberFormat().format(totals["MTN Uganda"]) }} UGX</h5>
                                                    <h5>AIRTEL Uganda: {{ new Intl.NumberFormat().format(totals["AIRTEL Uganda"]) }} UGX</h5>
                                                </div>
                                                <!-- Row end -->
                                            </div>
                                            <div v-else>
                                                <span class="ms-2">Headers...</span>
                                            </div>
                                        </div><hr>
                                        <table id="table" class="table align-middle table-hover table-bordered m-0">
                                            <thead>
                                                <tr>
                                                    <th scope="col">DATE</th>
                                                    <th scope="col">ID</th>
                                                    <th scope="col">TYPE</th>
                                                    <th scope="col">NARRATION</th>
                                                    <th scope="col">MNO</th>
                                                    <th scope="col">AMOUNT</th>
                                                    <th scope="col">CHARGE</th>
                                                    <th scope="col">W-FEE</th>
                                                    <th scope="col">TAX</th>
                                                    <th scope="col">CREDIT</th>
                                                    <th scope="col">TOTAL</th>
                                                    <th scope="col" class="control-column"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template v-for="(summary, index) in transactions" :key="index">
                                                    <!-- Summary Date Row -->
                                                    <tr>
                                                        <td colspan="11" class="text-center fw-bold bg-light bg-opacity-1">{{ summary.date }}</td>
                                                    </tr>
                                                    <template v-for="(transaction, i) in summary.transactions" :key="i">
                                                        <!-- Normal Transactions -->
                                                        <tr>
                                                            <td>{{ transaction.date || '' }}</td>
                                                            <td>{{ maskValue(transaction.phone || 0, 'phone') }}</td>
                                                            <td>{{ transaction.type || '' }}</td>
                                                            <td>{{ transaction.category || '' }}</td>
                                                            <td>{{ transaction.mno || '' }}</td>
                                                            <td class="text-end">{{ maskValue(Number(transaction.amount || 0).toLocaleString(),'') }}</td>
                                                            <td class="text-end">{{ maskValue(Number(transaction.charge || 0).toLocaleString(),'') }}</td>
                                                            <td class="text-end">{{ maskValue(Number(transaction.w_fee || 0).toLocaleString(),'') }}</td>
                                                            <td class="text-end">{{ maskValue(Number(transaction.tax || 0).toLocaleString(),'') }}</td>
                                                            <td class="text-end">{{ maskValue(Number(transaction.amount+transaction.charge+transaction.w_fee+transaction.tax || 0).toLocaleString(),'') }}</td>
                                                            <td class="text-end">{{ maskValue(Number(transaction.total || 0).toLocaleString(),'') }}</td>
                                                        </tr>
                                                    </template>
                                                    <!-- Footer Row with Totals -->
                                                    <tr class="bg-light fw-bold">
                                                        <td colspan="10" class="text-end">Totals for {{ summary.date }}:</td>
                                                        <td class="text-end">{{ maskValue(computeTotals(summary).total.toLocaleString(),'') }}</td>
                                                    </tr>
                                                </template>
                                                <!-- No Records Found -->
                                                <tr v-if="transactions.length === 0">
                                                    <td colspan="11" class="text-center">No records found.</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- Row end -->
                        </div>
                        <div class="card-footer">
							<div
								class="d-flex justify-content-between align-items-center my-2 my-lg-0"
							>
								<!-- Back Button -->
								<button
									type="button"
									class="btn btn-outline-secondary"
									@click="router.go(-1)"
								>
									<i class="fa fa-arrow-left"></i> Back
								</button>
								<!-- Top Button -->
								<button
									type="button"
									class="btn btn-success"
									@click="scrollToTop"
								>
									<i class="fa fa-arrow-up"></i> Top
								</button>
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

