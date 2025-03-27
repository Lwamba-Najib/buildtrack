<script setup>
import { ref, reactive, computed, onMounted, watch } from "vue";
import axios from "@/axios";
import { useRouter, RouterLink } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";
useCustomUtils();

// Reactive variables
const customerName = ref("Anonymous");
const customerPhone = ref("0700000000");
const customerEmail = ref("example@gmail.com");
const customerAddress = ref("Unknown");
const selectedProductId = ref("");
const selectedBatchNumber = ref("");
const selectedBrandId = ref("");
const selectedMeasurementId = ref("");
const salePrice = ref("");
const quantity = ref(1);
const discount = ref(0);
const paymentMethod = ref("CASH");
const notice = ref("");
const cart = ref([]);
const products = ref([]);
const batchNumbers = ref([]);
const brands = ref([]);
const measurements = ref([]);
const redirectOption = ref("stay"); // Default to "stay"

const alerts = reactive({ 
    success: "", 
    error: "" 
});

const router = useRouter();
const isLoading = ref(false);

// Save the selected option to localStorage
const saveRedirectPreference = (value) => {
    localStorage.setItem("redirectOption", value);
};

// Load the saved option from localStorage
const loadRedirectPreference = () => {
    const savedPreference = localStorage.getItem("redirectOption");
    if (savedPreference) {
        redirectOption.value = savedPreference;
    }
};
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

// Fetch products from the backend
const fetchProductsInStock = async () => {
    try {
        const token = getToken(); // Retrieve the token
        const response = await axios.get("/getproductsinstock", {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the request
            },
        });
        products.value = response.data;
    } catch (error) {
        handleError(error); // Handle error
    }
};
// Fetch products from the backend
const fetchBatchNumbersByProductId = async (productId) => {
    try {
        const token = getToken(); // Retrieve the token
        const response = await axios.get(`/getbatchnumbersbyproductinstock/${productId}`, {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the request
            },
        });

        batchNumbers.value = response.data;
        selectedBatchNumber.value = "";
        selectedBrandId.value = "";
        selectedMeasurementId.value = ""; // Reset measurement field
        salePrice.value = ""; // Reset sale price field
        // Reset Select2 dropdowns
        $("#brand").val("").trigger("change");
        $("#measurement").val("").trigger("change");
    } catch (error) {
        handleError(error); // Handle error
    }
};

// Fetch brands by batchNumber
const fetchBrandsBybatchNumber = async (batchNumber) => {
    try {
        const token = getToken(); // Retrieve the token
        const response = await axios.get(`/getbrandsbybatchnumberinstock/${batchNumber}`, {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the request
            },
        });
        brands.value = response.data;
        selectedBrandId.value = "";
        selectedMeasurementId.value = ""; // Reset measurement field
        salePrice.value = ""; // Reset sale price field
        // Reset Select2 dropdowns
        $("#brand").val("").trigger("change");
        $("#measurement").val("").trigger("change");
    } catch (error) {
        handleError(error); // Handle error
    }
};

// Fetch measurements by brand Id
const fetchMeasurementsByBrandId = async (brandId) => {
    try {
        const token = getToken(); // Retrieve the token
        const response = await axios.get(`/getmeasurementsbybrandinstock/${brandId}`, {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the request
            },
        });
        measurements.value = response.data;
        selectedMeasurementId.value = ""; // Reset measurement field
        salePrice.value = ""; // Reset sale price field
        // Reset Select2 dropdown
        $("#measurement").val("").trigger("change");
    } catch (error) {
        handleError(error); // Handle error
    }
};

// Fetch sale price for selected product and brand
const fetchSalePrice = async () => {
    // Return early if any required field is empty
    if (!selectedProductId.value || !selectedBatchNumber.value || !selectedBrandId.value || !selectedMeasurementId.value) {
        salePrice.value = ""; // Reset sale price
        return;
    }

    try {
        const token = getToken(); // Retrieve the token
        const response = await axios.get(`/getsalepriceinstock`, {
            params: {
                productId: selectedProductId.value,
                batchNumber: selectedBatchNumber.value,
                brandId: selectedBrandId.value,
                measurementId: selectedMeasurementId.value,
            },
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the request
            },
        });
        salePrice.value = response.data.salePrice;
    } catch (error) {
        if (error.response?.status === 404) {
            salePrice.value = ""; // Reset sale price if not found
        } else {
            handleError(error); // Handle other errors
        }
    }
};

// Add product to cart
const addToCart = () => {
    const product = products.value.find((p) => p.id == selectedProductId.value); // Use == for loose comparison
    const batchNumber = batchNumbers.value.find((bn) => bn.batch_number == selectedBatchNumber.value); // Use == for loose comparison
    const brand = brands.value.find((b) => b.id == selectedBrandId.value); // Use == for loose comparison
    const measurement = measurements.value.find((m) => m.id == selectedMeasurementId.value); // Use == for loose comparison

    if (product && brand && measurement && quantity.value > 0) {
        // Check if the product already exists in the cart
        const existingItem = cart.value.find(
            (item) =>
                item.product.id == product.id && item.brand.id == brand.id && item.measurement.id == measurement.id
        );

        if (existingItem) {
            // Update quantity if the product already exists
            existingItem.quantity += quantity.value;
        } else {
            // Add new item to the cart
            cart.value.push({
                product,
                batchNumber,
                brand,
                measurement,
                quantity: quantity.value,
                salePrice: salePrice.value,
            });
        }

        // Reset form fields
        selectedProductId.value = "";
        selectedBatchNumber.value = "";
        selectedBrandId.value = "";
        selectedMeasurementId.value = "";
        salePrice.value = "";
        quantity.value = 1;

        // Reset Select2 dropdowns
        $("#product").val("").trigger("change");
        $("#brand").val("").trigger("change");
        $("#measurement").val("").trigger("change");
    } else {
        alerts.error = "Please select a valid product, brand, and measurement before adding to cart.";
    }
};

// Remove product from cart
const removeFromCart = (index) => {
    cart.value.splice(index, 1);
};

// Clear cart
const clearCart = () => {
    cart.value = [];
};

// Calculate subtotal
const subtotal = computed(() => {
    return cart.value.reduce((total, item) => total + item.salePrice * item.quantity, 0);
});
// Function to validate email format
const validateEmail = (email) => {
	const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return emailPattern.test(email);
};
// Function to validate phone number (check only for digits)
const validatePhoneNumber = (phone) => {
	const phonePattern = /^0\d*$/; // Ensure phone number starts with '0' and contains only digits
	return phonePattern.test(phone);
};
// Validate form fields before submission
const validateForm = () => {
    let isValid = true;

    // Reset previous validation alerts
    alerts.customerName = "";
    alerts.customerPhone = "";
    alerts.customerEmail = "";
    alerts.customerAddress = "";
    alerts.paymentMethod ="";

    // Validate phone number field
	if (!customerPhone.value) {
		alerts.customerPhone = "Customer phone is required.";
		isValid = false;
	} else if (!validatePhoneNumber(customerPhone.value)) {
		alerts.customerPhone = "Invalid customer phoner format. Please enter only digits.";
		isValid = false;
	}
    // Validate text field
    if (!customerName.value) {
        alerts.customerName = "Customer name is required.";
        isValid = false;
    }
    // Validate email field
	if (!customerEmail.value) {
		alerts.customerEmail = "Customer email is required.";
		isValid = false;
	} else if (!validateEmail(customerEmail.value)) {
		alerts.customerEmail = "Invalid email format.";
		isValid = false;
	}
    // Validate text field
    if (!customerAddress.value) {
        alerts.customerAddress = "Customer address is required.";
        isValid = false;
    }
    // Validate text field
    if (!paymentMethod.value) {
        alerts.paymentMethod = "Payment method is required.";
        isValid = false;
    }

    return isValid;
};
// Process sale
const processSale = async () => {
    alerts.success = "";
	alerts.error = "";

	// Validate form before submission
	if (!validateForm()) {
		return; // Stop submission if validation fails
	}
    isLoading.value = true;
    try {
        const token = getToken(); // Retrieve the token
        const response = await axios.post("/salesstore",
            {
                customerName: customerName.value,
                customerPhone: customerPhone.value,
                customerEmail: customerEmail.value,
                customerAddress: customerAddress.value,
                items: cart.value,
                discount: discount.value,
                paymentMethod: paymentMethod.value,
                notice: notice.value,
            },
            {
                headers: {
                    Authorization: `Bearer ${token}`, // Include the token in the request
                },
            }
        );
        if (response.data.success) {
            alerts.success = "Sale processed successfully!";
            clearCart();
            // Redirect after 2 seconds
            setTimeout(() => {
                let route;
                if (redirectOption.value === "receipt") {
                    route = `/salesreceipt/${response.data.data.id}`;
                } else if (redirectOption.value === "invoice") {
                    route = `/salesinvoice/${response.data.data.id}`;
                } else {
                    route = "/salespos";
                }
                router.push(route);
            }, 2000);
        } else {
            alerts.error = response.data.message || "An error occurred during submission.";
        }        
    } catch (error) {
        handleError(error); // Handle error
    } finally {
        isLoading.value = false;
    }
};

// Format currency
const formatCurrency = (value) => {
    return new Intl.NumberFormat("en-US", { style: "currency", currency: "UGX" }).format(value);
};
// Initialize Select2 on all select fields
const initializeSelect2 = () => {
    $(function () {
        // Apply Select2 to all select elements with the class "select"
        $(".select")
            .select2({
                allowClear: true,
                placeholder: "Select an option", // Placeholder for better UX
            })
            .on("change", function () {
                const fieldName = $(this).attr("id"); // Get the ID of the select field
                const newValue = $(this).val(); // Get the new value of the field

                // Update the corresponding reactive variable
                switch (fieldName) {
                    case "product":
                        selectedProductId.value = newValue || ""; // Use empty string if cleared
                        fetchBatchNumbersByProductId(newValue); // Fetch batches when product changes
                        break;
                    case "batchNumber":
                        selectedBatchNumber.value = newValue || ""; // Use empty string if cleared
                        fetchBrandsBybatchNumber(newValue); // Fetch batch numbers when batchNumber changes
                        break;
                    case "brand":
                        selectedBrandId.value = newValue || ""; // Use empty string if cleared
                        fetchMeasurementsByBrandId(newValue); // Fetch measurements when brand changes
                        break;
                    case "measurement":
                        selectedMeasurementId.value = newValue || ""; // Use empty string if cleared
                        fetchSalePrice(); // Fetch sale price when measurement changes
                        break;
                    case "payment_method":
                        paymentMethod.value = newValue || ""; // Use empty string if cleared
                        break;
                    default:
                        console.warn(`Unhandled field: ${fieldName}`);
                }
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

// Initial data fetch
onMounted(() => {
    new Podtable("#table", {
		keepCell: [7],
	});
    loadRedirectPreference(); // Load the saved redirect preference
    // Reset reactive variables
    selectedProductId.value = "";
    selectedBatchNumber.value = "";
    selectedBrandId.value = "";
    selectedMeasurementId.value = "";
    salePrice.value = "";

    // Initialize Select2
    initializeSelect2();
    // Fetch products
    fetchProductsInStock();
});
const pluralizeMeasurement = (measurement, quantity) => {
    return quantity > 1 ? `${measurement}(s)` : measurement;
};
// Watch for changes in `customerPhone`
watch(customerPhone, (newVal, oldVal) => {
	if (!newVal) {
		customerPhone.value = "0"; // Default to '0' if empty
	} else if (!newVal.startsWith("0")) {
		customerPhone.value = "0" + newVal; // Prepend '0' if not present
	}
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
                    <RouterLink to="/saleslist" class="text-decoration-none">Sales</RouterLink>
                </li>
                <li class="breadcrumb-item text-secondary" aria-current="page">New Sale</li>
            </ol>
        </div>
        <!-- App Hero header ends -->

        <!-- App body starts -->
        <div class="app-body">
            <div class="row gx-3">
                <div class="col-xxl-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title">Create Sales</h5>
                        </div>
                        <div class="card-body">
                            <!-- Loading Spinner with Text -->
                            <div v-if="isLoading" class="d-flex justify-content-left align-items-left mb-3">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="ms-2">Processing sale...</span>
                            </div>

                            <!-- Success Alert -->
                            <div v-if="alerts.success" class="alert border border-success alert-dismissible fade show text-success" role="alert">
                                {{ alerts.success }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>

                            <!-- Error Alert -->
                            <div v-if="alerts.error" class="alert border border-danger alert-dismissible fade show text-danger" role="alert">
                                {{ alerts.error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>

                            <!-- Customer Details -->
                            <div class="row gx-3">
                                <div :class="{'col-lg-6': redirectOption !== 'invoice', 'col-lg-3': redirectOption === 'invoice'}"
                                class="col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Customer Name</label>
                                        <input
                                            v-model="customerName"
                                            @input="validateForm"
          									@blur="validateForm"
                                            type="text"
                                            class="form-control"
                                            placeholder="Customer Name"
                                        />
                                        <!-- Display validation message -->
										<div v-if="alerts.customerPhone" class="text-danger mt-2">
											{{ alerts.customerName }}
										</div>
                                    </div>
                                </div>
                                <div :class="{'col-lg-6': redirectOption !== 'invoice', 'col-lg-3': redirectOption === 'invoice'}"
                                class="col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Customer Phone</label>
                                        <input
                                            v-model="customerPhone"
                                            @input="validateForm"
          									@blur="validateForm"
                                            type="text"
                                            class="form-control"
                                            placeholder="Customer Phone"
                                        />
                                        <!-- Display validation message -->
										<div v-if="alerts.customerPhone" class="text-danger mt-2">
											{{ alerts.customerPhone }}
										</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-4 col-12" v-if="redirectOption === 'invoice'">
                                    <div class="mb-3">
                                        <label class="form-label">Customer Email</label>
                                        <input
                                            v-model="customerEmail"
                                            @input="validateForm"
          									@blur="validateForm"
                                            type="email"
                                            class="form-control"
                                            placeholder="Customer Email"
                                        />
                                        <!-- Display validation message -->
										<div v-if="alerts.customerEmail" class="text-danger mt-2">
											{{ alerts.customerEmail }}
										</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-4 col-12" v-if="redirectOption === 'invoice'">
                                    <div class="mb-3">
                                        <label class="form-label">Customer Address</label>
                                        <input
                                            v-model="customerAddress"
                                            @input="validateForm"
          									@blur="validateForm"
                                            type="text"
                                            class="form-control"
                                            placeholder="Customer Address"
                                        />
                                        <!-- Display validation message -->
										<div v-if="alerts.customerAddress" class="text-danger mt-2">
											{{ alerts.customerAddress }}
										</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product Selection -->
                            <div class="row gx-2 align-items-end">
                                <!-- Product Dropdown -->
                                <div class="col-lg-3 col-sm-4 col-12">
                                    <div>
                                        <label class="form-label">Product</label>
                                        <select
                                            v-model="selectedProductId"
                                            id="product"
                                            class="form-select select"
                                        >
                                            <option value="" disabled>Select product</option>
                                            <option
                                                v-for="product in products"
                                                :key="product.id"
                                                :value="product.id"
                                            >
                                                {{ product.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Batch Number Dropdown based on the selected product -->
                                <div class="col-lg-2 col-sm-4 col-12">
                                    <div>
                                        <label class="form-label">Batch Number</label>
                                        <select
                                            v-model="selectedBatchNumber"
                                            id="batchNumber"
                                            class="form-select select"
                                            :disabled="!selectedProductId"
                                        >
                                            <option value="" disabled>Select batch</option>
                                            <option
                                                v-for="batch in batchNumbers"
                                                :key="batch.batch_number"
                                                :value="batch.batch_number"
                                            >
                                                {{ batch.batch_number }} (Balance: {{ batch.balance }})
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Brand Dropdown -->
                                <div class="col-lg-2 col-sm-4 col-12">
                                    <div>
                                        <label class="form-label">Brand</label>
                                        <select
                                            v-model="selectedBrandId"
                                            id="brand"
                                            class="form-select select"
                                        >
                                            <option value="" disabled>Select brand</option>
                                            <option
                                                v-for="brand in brands"
                                                :key="brand.id"
                                                :value="brand.id"
                                            >
                                                {{ brand.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <!-- Measurement Dropdown -->
                                <div class="col-lg-2 col-sm-4 col-12">
                                    <div>
                                        <label class="form-label">Measurement</label>
                                        <select
                                            v-model="selectedMeasurementId"
                                            id="measurement"
                                            class="form-select select"
                                        >
                                            <option value="" disabled>Select measurement</option>
                                            <option
                                                v-for="measurement in measurements"
                                                :key="measurement.id"
                                                :value="measurement.id"
                                            >
                                                {{ measurement.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Sale Price Display -->
                                <div class="col-lg-1 col-sm-4 col-12">
                                    <div>
                                        <label class="form-label">Sale Price</label>
                                        <input
                                            v-model="salePrice"
                                            type="text"
                                            class="form-control"
                                            placeholder="Sale Price"
                                            readonly
                                        />
                                    </div>
                                </div>

                                <!-- Quantity Input -->
                                <div class="col-lg-1 col-sm-4 col-12">
                                    <div>
                                        <label class="form-label">Quantity</label>
                                        <input
                                            v-model="quantity"
                                            type="number"
                                            class="form-control"
                                            placeholder="Quantity"
                                            min="1"
                                        />
                                    </div>
                                </div>

                                <!-- Add to Cart Button -->
                                <div class="col-lg-1 col-sm-4 col-12 d-flex align-items-end">
                                    <button
                                        type="button"
                                        class="btn btn-primary w-100"
                                        @click="addToCart"
                                        :disabled="!selectedProductId || !selectedBrandId || !selectedMeasurementId || !quantity"
                                    >
                                        <i class="bi bi-cart-plus"></i> Add Cart
                                    </button>
                                </div>
                            </div>

                            <!-- Cart Items Table -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h6>Cart Items</h6>
                                    <table id="table" class="table align-middle table-hover m-0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">PRODUCT</th>
                                                <th scope="col">BRAND</th>
                                                <th scope="col">MEASUREMENT</th>
                                                <th scope="col">QTY</th>
                                                <th scope="col">UNIT PRICE</th>
                                                <th scope="col">TOTAL</th>
                                                <th scope="col">ACTIONS</th>
                                                <th scope="col" class="control-column"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(item, index) in cart" :key="index">
                                                <th scope="row">{{ index + 1 }}</th>
                                                <td>{{ item.product.name }}</td>
                                                <td>{{ item.brand.name }}</td>
                                                <td>{{ pluralizeMeasurement(item.measurement.name, item.quantity) }}</td>
                                                <td>{{ item.quantity }}</td>
                                                <td>{{ formatCurrency(item.salePrice) }}</td>
                                                <td>{{ formatCurrency(item.quantity * item.salePrice) }}</td>
                                                <td>
                                                    <button
                                                        type="button"
                                                        class="btn btn-danger btn-sm"
                                                        @click="removeFromCart(index)"
                                                    >
                                                        Remove
                                                    </button>
                                                </td>
                                                <td class="control-column"></td>
                                            </tr>
                                            <tr v-if="cart.length === 0">
                                                <th colspan="8" class="text-center">
                                                    Your cart is empty. Start shopping now!
                                                </th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Totals and Payment Section -->
                            <div class="row mt-4">
                                <div class="col-lg-6 col-sm-12">
                                    <div class="mb-3">
                                        <label class="form-label">Discount (%)</label>
                                        <input
                                            v-model="discount"
                                            type="number"
                                            class="form-control"
                                            placeholder="Discount"
                                            min="0"
                                            max="100"
                                        />
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-12">
                                    <div class="mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <select v-model="paymentMethod" id="payment_method" class="form-select select">
                                            <option value="CASH">Cash</option>
                                            <option value="MOBILE MONEY">Mobile Money</option>
                                            <option value="BANK">Bank</option>
                                            <option value="CARD">Card</option>
                                        </select>
                                        <!-- Display validation message -->
                                        <div
                                            v-if="alerts.paymentMethod"
                                            class="text-danger mt-2"
                                        >
                                            {{ alerts.paymentMethod }}
                                        </div>
                                    </div>
                                </div>
                            </div>     
                            <div class="row" v-if="redirectOption === 'invoice'">
                                <div class="col-lg-12 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Notice</label>
                                        <textarea
                                            v-model="notice"
                                            type="text"
                                            class="form-control"
                                            placeholder="Enter Notice"
                                        ></textarea>
                                        <!-- Display validation message -->
                                        <div
                                            v-if="alerts.notice"
                                            class="text-danger mt-2"
                                        >
                                            {{ alerts.notice }}
                                        </div>
                                    </div>
                                </div>
                            </div>                       
                            <!-- Totals Display -->
                            <div class="row mt-4">
                                <div class="col-12 text-end">
                                    <h5>Subtotal: {{ formatCurrency(subtotal) }}</h5>
                                    <h5>Discount: {{ formatCurrency(subtotal * (discount / 100)) }}</h5>
                                    <h4>Total: {{ formatCurrency(subtotal - (subtotal * (discount / 100))) }}</h4>
                                </div>
                            </div>
                            <hr>
                            <div class="row mt-4">
                                <!-- Save Button and Redirect Options -->
                                <div class="col-12">
                                    <div class="d-flex justify-content-end align-items-center my-2 my-lg-0">
                                        <div class="mb-3">
                                            <!-- Generalized Label -->
                                            <label class="form-label">After Save Action</label>
                                            <div>
                                                <div class="form-check form-check-inline">
                                                    <input
                                                        v-model="redirectOption"
                                                        class="form-check-input"
                                                        type="radio"
                                                        name="redirectOption"
                                                        value="receipt"
                                                        id="redirectReceipt"
                                                        @change="saveRedirectPreference('receipt')"
                                                    />
                                                    <label class="form-check-label" for="redirectReceipt">Save and go to Receipt</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input
                                                        v-model="redirectOption"
                                                        class="form-check-input"
                                                        type="radio"
                                                        name="redirectOption"
                                                        value="invoice"
                                                        id="redirectInvoice"
                                                        @change="saveRedirectPreference('invoice')"
                                                    />
                                                    <label class="form-check-label" for="redirectInvoice">Save and go to Invoice</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input
                                                        v-model="redirectOption"
                                                        class="form-check-input"
                                                        type="radio"
                                                        name="redirectOption"
                                                        value="stay"
                                                        id="redirectStay"
                                                        @change="saveRedirectPreference('stay')"
                                                    />
                                                    <label class="form-check-label" for="redirectStay">Save and stay</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>                        

                        <!-- Footer with Action Buttons -->
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center my-2 my-lg-0">
                                <!-- Clear Cart Button -->
                                <button
                                    type="button"
                                    class="btn btn-outline-danger"
                                    @click="clearCart"
                                >
                                    <i class="bi bi-trash"></i> Clear Cart
                                </button>

                                <!-- Process Sale Button -->
                                <button
                                    type="button"
                                    class="btn btn-success"
                                    @click="processSale"
                                    :disabled="cart.length === 0 || isLoading"
                                >
                                    <i class="bi bi-send"></i> Process Sale
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- App body ends -->
    </section>
</template>
<style scoped>
/* Custom styles if needed */
</style>