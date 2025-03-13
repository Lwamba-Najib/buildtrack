<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '@/axios';
import LoadingIndicator from '../../singles/SpinnerGrow.vue';
import { useCustomUtils } from "@/utils/customUtils";

// Destructure the utility functions from useCustomUtils
const { parseDate } = useCustomUtils();

const router = useRouter();
const route = useRoute();
const sale = ref({
    batch_number: null,
    created_at: null,
    customer_name: null,
    customer_phone: null,
    items: [],
    payment_method: null,
    total_amount: 0,
    discount: 0,
});
const isLoading = ref(true);

// Function to get the token from localStorage
const getToken = () => {
    const token = localStorage.getItem("token");
    if (!token) throw new Error("No token found");
    return token;
};

// Function to fetch sale details from the API
const fetchDetails = async () => {
    isLoading.value = true;
    try {
        const token = getToken();
        const response = await axios.get(`/saleshow/${route.params.id}`, {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        });

        console.log("API Response:", response.data); // Debug: Log the API response

        if (response.status === 200 && response.data.success) {
            sale.value = response.data.data; // Assign the sale object
            console.log("Sale Object:", sale.value); // Debug: Log the sale object
        } else {
            console.error('Error fetching sale:', response.statusText);
        }
    } catch (error) {
        console.error('Error fetching sale:', error);
    } finally {
        isLoading.value = false;
    }
};

// Fetch sale details when the component is mounted
onMounted(() => {
    fetchDetails();
});

// Function to print the receipt
const printReceipt = () => {
    const printContent = document.querySelector('.printable-receipt').innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Receipt</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .card { border: none !important; box-shadow: none !important; }
                    .table { width: 100%; border-collapse: collapse; }
                    .table, .table th, .table td { border: 1px solid black; padding: 8px; text-align: left; }
                </style>
            </head>
            <body>
                ${printContent}
            </body>
        </html>
    `);

    printWindow.document.close(); // Close document to finish writing
    printWindow.focus();
    printWindow.print(); // Trigger print dialog
    printWindow.close(); // Close the print window after printing
};

// Function to format currency (assuming this is missing in your original code)
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'UGX' }).format(value);
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
                    <RouterLink to="/saleslist" class="text-decoration-none">Sales</RouterLink>
                </li>
                <li class="breadcrumb-item text-secondary" aria-current="page">View</li>
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
                                    <div class="d-flex">
                                        <button type="button" class="btn btn-primary btn-sm" @click="printReceipt">
                                        <i class="fa fa-print"></i> Print
                                    </button>
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
                            <h5 class="card-title">View Receipt</h5>
                        </div>
                        <div class="card-body">
                            <!-- Row start -->
                            <div class="row gx-3">
                                <div class="col-12">
                                    <div class="mb-3 position-relative">
                                        <!-- Display the LoadingIndicator component -->
                                        <LoadingIndicator :isLoading="isLoading" />
                                        <div class="row d-flex justify-content-center my-2 my-lg-0">
                                            <div class="col-8">
                                                <div class="printable-receipt">
                                                    <div class="layout-receipt">
                                                        <div class="table-responsive">
                                                            <table class="table table-borderless">
                                                                <tbody>
                                                                    <tr style="line-height: 2;">
                                                                        <td class="align-left text-start" style="border: none;">
                                                                            <p class="text-start m-0">
                                                                                Venus Llc, 9990 St. <br />
                                                                                5000 Church Street, Suite 550<br />
                                                                                Huntsville, Alabama, 99990
                                                                            </p>
                                                                        </td>
                                                                        <td class="text-end">
                                                                            <p class="text-end m-0">
                                                                                Payment Date: <u><span class="pl-5">{{ parseDate(sale.created_at) || 'NA' }}</span></u><br>
                                                                                Receipt#: <u><span class="text-bold pl-5">{{ sale.batch_number }}</span></u>
                                                                            </p>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td colspan="2" style="border: none;">
                                                                            <hr style="border-top: 1px solid black;" class="seperator-line-dashed"/>
                                                                            <hr style="border-top: 1px solid black;" class="seperator-line-dashed"/>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="align-left text-start" style="border: none;">
                                                                            <strong>From:</strong>
                                                                            <p class="text-start m-0 mt-2" style="line-height: 4px;">
                                                                                Venus Llc, 9990 St. <hr class="seperator-line"/>
                                                                                5000 Church Street, Suite 550<hr class="seperator-line"/>
                                                                            </p>
                                                                        </td>
                                                                        <td class="align-middle" style="border: none;">
                                                                            <strong>Sold To:</strong>
                                                                            <p class="text-end m-0 mt-2" style="line-height: 4px;">
                                                                                {{ sale.customer_name }}<hr class="seperator-line"/>
                                                                                {{ sale.customer_phone }}<hr class="seperator-line"/>
                                                                            </p>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <div class="row gx-3">
                                                            <div class="col-12">
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>ITEM</th>
                                                                                <th>BRAND</th>
                                                                                <th>MEASUREMENT</th>
                                                                                <th>QTY</th>
                                                                                <th>UNIT PRICE</th>
                                                                                <th>TOTAL PRICE</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <tr v-for="item in sale.items" :key="item.id">
                                                                                <td>
                                                                                    <h6>{{ item.product?.name }}</h6>
                                                                                    <p>{{ item.product?.description }}</p>
                                                                                </td>
                                                                                <td>
                                                                                    <h6>{{ item.brand?.name || 'N/A' }}</h6>
                                                                                </td>
                                                                                <td>
                                                                                    <h6>{{ item.measurement?.name || 'N/A' }}</h6>
                                                                                </td>
                                                                                <td>
                                                                                    <h6>{{ Number(item.quantity).toLocaleString() || 0 }}</h6>
                                                                                </td>
                                                                                <td>
                                                                                    <h6>{{ formatCurrency(item.unit_price) }}</h6>
                                                                                </td>
                                                                                <td>
                                                                                    <h6>{{ formatCurrency(item.total_price) }}</h6>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td colspan="4">
                                                                                    <h5>Payment Method: {{ sale.payment_method || 'Not specified' }}</h5>
                                                                                    <div class="form-check">
                                                                                        <input
                                                                                            type="radio"
                                                                                            class="form-check-input"
                                                                                            id="radio1"
                                                                                            value="CASH"
                                                                                            v-model="sale.payment_method"
                                                                                            :disabled="sale.payment_method !== 'CASH'"
                                                                                        />
                                                                                        <label class="form-check-label" for="radio1">Cash</label>
                                                                                    </div>
                                                                                    <div class="form-check">
                                                                                        <input
                                                                                            type="radio"
                                                                                            class="form-check-input"
                                                                                            id="radio2"
                                                                                            value="MOBILE_MONEY"
                                                                                            v-model="sale.payment_method"
                                                                                            :disabled="sale.payment_method !== 'MOBILE_MONEY'"
                                                                                        />
                                                                                        <label class="form-check-label" for="radio2">Mobile Money</label>
                                                                                    </div>
                                                                                    <div class="form-check">
                                                                                        <input
                                                                                            type="radio"
                                                                                            class="form-check-input"
                                                                                            id="radio3"
                                                                                            value="BANK"
                                                                                            v-model="sale.payment_method"
                                                                                            :disabled="sale.payment_method !== 'BANK'"
                                                                                        />
                                                                                        <label class="form-check-label" for="radio3">Bank</label>
                                                                                    </div>
                                                                                    <div class="form-check">
                                                                                        <input
                                                                                            type="radio"
                                                                                            class="form-check-input"
                                                                                            id="radio4"
                                                                                            value="CARD"
                                                                                            v-model="sale.payment_method"
                                                                                            :disabled="sale.payment_method !== 'CARD'"
                                                                                        />
                                                                                        <label class="form-check-label" for="radio4">Card</label>
                                                                                    </div>
                                                                                </td>
                                                                                <td>
                                                                                    <p>Subtotal</p>
                                                                                    <p>Discount</p>
                                                                                    <p>VAT</p>
                                                                                    <h5 class="mt-4 text-blue">Total UGX</h5>
                                                                                </td>
                                                                                <td>
                                                                                    <p>{{ formatCurrency(sale.total_amount) }}</p>
                                                                                    <p>{{ formatCurrency(sale.discount) }}</p>
                                                                                    <p>00%</p>
                                                                                    <h5 class="mt-4 text-blue">{{ formatCurrency(sale.total_amount - sale.discount) }}</h5>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td colspan="6" class="text-center align-middle">
                                                                                    <h6 class="text-red">THANK YOU FOR YOUR PURCHASE!</h6>
                                                                                    <small>
                                                                                        for questions or any concerns, please contact<br>
                                                                                        +256700000000
                                                                                    </small>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>  
                                    </div>
                                </div>
                            </div>
                            <!-- Row end -->
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-begin my-2 my-lg-0">
                                <button
									type="button"
									class="btn btn-outline-secondary"
									@click="router.go(-1)"
								>
									<i class="fa fa-arrow-left"></i> Back
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

<style scoped>
.layout-receipt{
    border: double;
    padding: 2%;
}
.seperator-line{
    border: 0.01em solid #000000 !important;
}
.seperator-line-dotted{
    border: 0.01em dotted #000000 !important;
    margin: 1% !important;
}
.seperator-line-dashed{
    border: 0.01em dashed #000000 !important;
    margin: 1% !important;
}
.seperator-line-double{
    border: 0.01em double #000000 !important;
    margin: 1% !important;
}
.seperator-line-hidden{
    border: none !important;
}
table > thead > tr > th{
    background: #87a5eb;
}
</style>