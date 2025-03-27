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
const businessInfoSettings = ref([]);
const logoSrc = ref("/assets/images/logo.png");
const isLoading = ref(true);

// Function to get the token from localStorage
const getToken = () => {
    const token = localStorage.getItem("token");
    if (!token) throw new Error("No token found");
    return token;
};

// Function to fetch business info settings
const fetchBusinessInfoSettings = async () => {
    isLoading.value = true; // Optionally set a loading state
    try {
        const token = getToken(); // Retrieve the token
        const response = await axios.get("/getbusinessinfosettings", {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the request
            },
        });

        if (response.status === 200 && response.data.success) {
            businessInfoSettings.value = response.data.data; // Directly assign the object
        } else {
            console.error('Error fetching business info settings:', response.statusText);
        }
    } catch (error) {
        console.error('Error fetching business info settings:', error);
    } finally {
        isLoading.value = false; // Optionally reset the loading state
    }
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

        if (response.status === 200 && response.data.success) {
            sale.value = response.data.data; // Assign the sale object
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
    fetchBusinessInfoSettings();
    fetchDetails();
});

// Function to print the receipt
const printReceipt = () => {
    // Get the printable receipt content
    const printOut = document.getElementById('printable-receipt').innerHTML;

    // Get all styles from the current document
    const styles = Array.from(document.querySelectorAll('style, link[rel="stylesheet"]'))
        .map(el => el.outerHTML)
        .join('');

    // Create a new window for printing
    const printWindow = window.open('', '', 'height=600,width=800');
    if (!printWindow) {
        alert('Please allow popups for this site to print the receipt.');
        return;
    }

    // Write the HTML content to the new window, including the styles
    printWindow.document.write(`
        <html>
            <head>
                <title>Print Receipt</title>
                ${styles}
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 0;
                    }
                    .layout-receipt {
                        width: auto;
                        margin: 0 auto;
                        padding: 20px;
                        border: 1px solid #000;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    th, td {
                        padding: 8px;
                        text-align: left;
                        border: 1px solid #000;
                    }
                    th {
                        background-color: #B22222;
                        color: white;
                    }
                    .custom-border {
                        border: 3px dashed #B22222;
                        border-radius: 10px;
                        padding: 5px;
                    }
                    .text-start {
                        text-align: left;
                    }
                    .text-end {
                        text-align: right;
                    }
                    .text-center {
                        text-align: center;
                    }
                    .seperator-line {
                        border-top: 1px solid #000;
                    }
                    .seperator-line-double {
                        border-top: 2px solid #000;
                    }
                    @media print {
                        body {
                            font-size: 12pt;
                        }
                        .layout-receipt {
                            border: none;
                        }
                        table {
                            width: 100%;
                        }
                        th, td {
                            padding: 6px;
                        }
                        .custom-border {
                            border: 3px dashed #B22222;
                            border-radius: 10px;
                            padding: 5px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="layout-receipt">
                    ${printOut}
                </div>
            </body>
        </html>
    `);

    printWindow.document.close();

    // Print the receipt
    printWindow.print();

    // Close the print window after a short delay
    setTimeout(() => {
        printWindow.close();
    }, 100); // 100ms delay to ensure the print dialog is closed
};

// Function to format currency (assuming this is missing in your original code)
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'UGX' }).format(value);
};
const pluralizeMeasurement = (measurement, quantity) => {
    return quantity > 1 ? `${measurement}(s)` : measurement;
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
                                                <div id="printable-receipt">
                                                    <div class="layout-receipt">
                                                        <div class="table-responsive">
                                                            <table class="table table-borderless">
                                                                <tbody>
                                                                    <tr style="line-height: 2;">
                                                                        <td colspan="2" class="text-center align-middle" style="border: none;">
                                                                            <img :src="logoSrc" class="logo custom-border" alt="Logo" style="width: 100px; height: 100px;"/>
                                                                        </td>
                                                                    </tr>
                                                                    <tr style="line-height: 2;">
                                                                        <td class="align-left text-start" style="border: none;">
                                                                            <p class="text-start m-0">
                                                                                {{ businessInfoSettings.business_name }} <br />
                                                                                {{ (businessInfoSettings.business_address || 'No address') + ', (' + (businessInfoSettings.business_contact || 'No contact') + ')' }}
                                                                            </p>
                                                                        </td>
                                                                        <td class="text-end" style="border: none;">
                                                                            <p class="text-end m-0">
                                                                                Payment Date: <u><span class="pl-5">{{ parseDate(sale.created_at) || 'NA' }}</span></u><br>
                                                                                Receipt#: <u><span class="text-bold pl-5">{{ sale.batch_number }}</span></u>
                                                                            </p>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td colspan="2" style="border: none;">
                                                                            <hr style="border-top: 1px solid #000;" class="seperator-line-double"/>
                                                                            <hr style="border-top: 1px solid #000;" class="seperator-line-double"/>
                                                                            <br>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="align-left text-start" style="border: none;">
                                                                            <strong>From:</strong>
                                                                            <p class="text-start m-0 mt-2" style="line-height: 4px;">
                                                                                {{ businessInfoSettings.business_name || 'No name' }}
                                                                                . <hr class="seperator-line"/>
                                                                                {{ businessInfoSettings.business_contact || 'No contact' }}
                                                                                . <hr class="seperator-line"/>
                                                                            </p>
                                                                        </td>
                                                                        <td class="align-middle" style="border: none;">
                                                                            <strong>Sold To:</strong>
                                                                            <p class="text-begin m-0 mt-2" style="line-height: 4px;">
                                                                                {{ sale.customer_name || 'No name' }}
                                                                                . <hr class="seperator-line"/>
                                                                                {{ sale.customer_phone || 'No contact' }}
                                                                                . <hr class="seperator-line"/>
                                                                            </p>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <div class="row gx-3">
                                                            <div class="col-12">
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered" style="border: none;">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>DESCRIPTION</th>
                                                                                <th>QUANTITY</th>
                                                                                <th>UNIT PRICE</th>
                                                                                <th>TOTAL PRICE</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <tr v-for="item in sale.items" :key="item.id">
                                                                                <td>
                                                                                    {{ item.product?.name }} 
                                                                                    ({{ item.brand?.name || 'N/A' }}, 
                                                                                    {{ item.measurement?.name ? pluralizeMeasurement(item.measurement.name, item.quantity) : 'N/A' }})
                                                                                </td>
                                                                                <td class="text-end">
                                                                                    <h6>{{ Number(item.quantity).toLocaleString() || 0 }}</h6>
                                                                                </td>
                                                                                <td class="text-end">
                                                                                    <h6>{{ formatCurrency(item.unit_price) }}</h6>
                                                                                </td>
                                                                                <td class="text-end">
                                                                                    <h6>{{ formatCurrency(item.total_price) }}</h6>
                                                                                </td>
                                                                            </tr>
                                                                            <tr class="no-border">
                                                                                <td colspan="2" class="no-bottom-border" style="border: none;">
                                                                                    <h5>Payment Method:</h5>
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
                                                                                <td class="text-end" style="border: none; flex-direction: column;  padding: 0px;">
                                                                                    <div style="padding-top: 5px; padding-right: 5px;">Subtotal: </div>
                                                                                    <div style="padding-top: 9px; padding-right: 5px;">Discount: </div>
                                                                                    <div style="padding-top: 10px; padding-right: 5px;">VAT: </div>
                                                                                    <div style="padding-top: 5px; padding-right: 5px;" class="pt-md-3 text-blue">Total UGX: </div>
                                                                                </td>
                                                                                <td class="text-end" style=" flex-direction: column; border: 1px solid #000; padding: 0px;">
                                                                                    <div style="border-bottom: 1px solid #000; border-left: 1px solid #000; border-right: 1px solid #000; padding: 5px;">{{ formatCurrency(sale.total_amount) }}</div>
                                                                                    <div style="border-bottom: 1px solid #000; border-left: 1px solid #000; border-right: 1px solid #000; padding: 5px;">{{ formatCurrency(sale.discount) }}</div>
                                                                                    <div style="border-bottom: 1px solid #000; border-left: 1px solid #000; border-right: 1px solid #000; padding: 5px;">00%</div>
                                                                                    <div style="border-bottom: 1px solid #000; border-left: 1px solid #000; border-right: 1px solid #000; padding: 5px;" class="pt-md-3 text-blue">{{ formatCurrency(sale.total_amount - sale.discount) }}</div>
                                                                                </td>
                                                                            </tr>
                                                                            <tr class="no-border">
                                                                                <td colspan="6" style="border: none;">
                                                                                    <br>
                                                                                    <hr style="border-top: 1px solid #000;" class="seperator-line-double"/>
                                                                                    <hr style="border-top: 1px solid #000;" class="seperator-line-double"/>
                                                                                    <br>
                                                                                </td>
                                                                            </tr>
                                                                            <tr class="no-border">
                                                                                <td colspan="6" class="text-center align-middle" style="border: none;">
                                                                                    <h6 class="text-red">THANK YOU FOR YOUR PURCHASE!</h6>
                                                                                    <hr class="seperator-line"/>
                                                                                {{ businessInfoSettings.business_legal_disclaimer || 'No legal disclaimer' }}<br>
                                                                                    <small>
                                                                                        For questions or any concerns, please contact<br>
                                                                                        {{ businessInfoSettings.business_contact || 'No contact' }}
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
.custom-border {
    border: 3px dashed #B22222;
    border-radius: 10px;
    padding: 5px;
}
.layout-receipt {
    border: double;
    padding: 2%;
}
hr{
    color: #000000;
    background: #000000;
    border: #000000;
}
.seperator-line {
    border: 0.01em solid #000000 !important;
}
.seperator-line-dotted {
    border: 0.01em dotted #000000 !important;
    margin: 1% !important;
}
.seperator-line-dashed {
    border: 0.02em dashed #000000 !important;
    margin: 0.5% !important;
}
.seperator-line-double {
    border: 0.02em double #000000 !important;
    margin: 0.2% !important;
}
.seperator-line-hidden {
    border: none !important;
}
table > thead > tr > th {
    background: #B22222;
    color: aliceblue;
}
table > thead > tr > td {
    border: 1px solid #000000;
}
.no-bottom-border {
    border-bottom: none !important;
}
tr.no-border, tr.no-border td {
    border: none !important;
}
@media print {
    /* Ensure background colors and images are printed */
    * {
        -webkit-print-color-adjust: exact !important; /* Chrome, Safari */
        print-color-adjust: exact !important; /* Standard */
    }

    /* Reset layout for printing */
    #content, #page {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        float: none !important;
    }

    /* Set page margins and size */
    @page {
        size: A4 portrait !important;
        margin: 2cm !important;
    }

    /* Base styles for printing */
    body {
        font: 13pt Georgia, "Times New Roman", Times, serif !important;
        line-height: 1.3 !important;
        background: #fff !important;
        color: #000 !important;
    }

    tr.no-border, tr.no-border td {
        border: none !important;
    }

    h1 {
        font-size: 24pt !important;
    }

    h2, h3, h4 {
        font-size: 14pt !important;
        margin-top: 25px !important;
    }

    /* Avoid breaking elements across pages */
    a, blockquote, h1, h2, h3, h4, h5, h6, img, table, pre {
        page-break-inside: avoid !important;
    }

    ul, ol, dl {
        page-break-before: avoid !important;
    }

    /* Link styling for print */
    a:link, a:visited, a {
        background: transparent !important;
        color: #520 !important;
        font-weight: bold !important;
        text-decoration: underline !important;
        text-align: left !important;
    }

    a[href^=http]:after {
        content: " <" attr(href) "> " !important;
    }

    article a[href^="#"]:after {
        content: "" !important;
    }

    a:not(:local-link):after {
        content: " <" attr(href) "> " !important;
    }

    /* Hide unnecessary elements */
    #header-widgets, nav, aside.mashsb-container, 
    .sidebar, .mashshare-top, .mashshare-bottom, 
    .content-ads, .make-comment, .author-bio, 
    .heading, .related-posts, #decomments-form-add-comment, 
    #breadcrumbs, #footer, .post-byline, .meta-single, 
    .site-title img, .post-tags, .readability {
        display: none !important;
    }

    /* Add custom messages before and after content */
    .entry:after {
        content: " All rights reserved. (c) 2023 Your Company" !important;
        color: #999 !important;
        font-size: 1em !important;
        padding-top: 30px !important;
    }

    #header:before {
        content: " Thank you for printing our receipt." !important;
        color: #777 !important;
        font-size: 1em !important;
        padding-top: 30px !important;
        text-align: center !important;
    }

    /* Define important elements */
    p, address, li, dt, dd, blockquote {
        font-size: 100% !important;
    }

    /* Set font for code examples */
    code, pre { 
        font-family: "Courier New", Courier, mono !important;
    }

    ul, ol {
        list-style: square !important; 
        margin-left: 18pt !important;
        margin-bottom: 20pt !important;
    }

    li {
        line-height: 1.6em !important;
    }
}
</style>