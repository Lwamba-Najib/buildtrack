<script setup>
import { ref, reactive, onMounted, watch } from "vue";
import axios from "@/axios"; // Ensure axios is correctly configured for API calls
import { useRouter, RouterLink } from "vue-router";
import { useStore } from 'vuex';
import { useCustomUtils } from "@/utils/customUtils";
useCustomUtils();

// Reactive variables for form fields
const products = ref([]);
const brands = ref([]);
const measurements = ref([]);
const suppliers = ref([]);
const product_id = ref("");
const brand_id = ref("");
const measurement_id = ref("");
const quantity = ref("");
const unit_price = ref("");
const total_cost = ref("");
const sale_price = ref("");
const min_stock_level = ref("");
const supplier_id = ref("");
const stock_date = ref("");
const environment = ref(""); // Environment selection (e.g., Development, Production)
const shouldRedirect = ref(true); // New reactive variable for redirection control

// Reactive object to manage alerts
const alerts = reactive({
    success: "", // Success message alert
    error: "", // Error message alert
    product_id: "",
    brand_id: "",
    measurement_id: "",
    quantity: "",
    unit_price: "",
    total_cost: "",
    sale_price: "",
    min_stock_level: "",
    supplier_id: "",
    stock_date: "",
    environment: "",
});

// Loading state to show spinner during processing
const isLoading = ref(false);
// Vue Router instance for navigation
const router = useRouter();
const store = useStore(); // Use Vuex store

// Save the selected option to localStorage
const saveRedirectPreference = (value) => {
	localStorage.setItem("shouldRedirect", value);
};

// Load the saved option from localStorage
const loadRedirectPreference = () => {
	const savedPreference = localStorage.getItem("shouldRedirect");
	if (savedPreference !== null) {
		shouldRedirect.value = savedPreference === "true";
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

const formatAmount = (value) => {
    if (!value) return '';
    // Remove any non-numeric characters except for the decimal point
    let num = value.replace(/[^\d.]/g, '');
    // Add commas for thousands
    return num.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

const updateAmount = (event, field) => {
    let value = event.target.value;
    // Remove any non-numeric characters except for the decimal point
    value = value.replace(/[^\d.]/g, '');
    // Update the corresponding field
    if (field === "quantity") quantity.value = formatAmount(value);
    if (field === "unit_price") unit_price.value = formatAmount(value);
    if (field === "sale_price") sale_price.value = formatAmount(value);
    if (field === "min_stock_level") min_stock_level.value = formatAmount(value);
	// Compute total_cost
	computeTotalCost();
};

// Function to compute total_cost with commas
const computeTotalCost = () => {
    // Remove commas and parse the values
    const qty = parseInt(quantity.value.replace(/,/g, "")) || 0;
    const price = parseInt(unit_price.value.replace(/,/g, "")) || 0;

    // Calculate the total cost
    const total = qty * price;

    // Format the total cost with commas
    total_cost.value = total.toLocaleString("en-US");
};

// Watch for changes in quantity or unit_price
watch([quantity, unit_price], () => {
    computeTotalCost();
});

// Validate form fields before submission
const validateForm = () => {
    let isValid = true;

    // Reset previous validation alerts
    alerts.product_id = "";
    alerts.brand_id = "";
    alerts.measurement_id = "";
    alerts.quantity = "";
    alerts.unit_price = "";
    alerts.total_cost = "";
    alerts.sale_price = "";
    alerts.min_stock_level = "";
    alerts.supplier_id = "";
    alerts.stock_date = "";
    alerts.environment = "";

    // Validate product field
    if (!product_id.value) {
        alerts.product_id = "Product is required.";
        isValid = false;
    }
    // Validate text field
    if (!brand_id.value) {
        alerts.brand_id = "Brand is required.";
        isValid = false;
    }
    // Validate text field
    if (!measurement_id.value) {
        alerts.measurement_id = "Measurement is required.";
        isValid = false;
    }
    // Validate text field
    if (!quantity.value) {
        alerts.quantity = "Quantity is required.";
        isValid = false;
    }
    // Validate text field
    if (!sale_price.value) {
        alerts.sale_price = "Sale price is required.";
        isValid = false;
    }
    // Validate text field
    if (!min_stock_level.value) {
        alerts.min_stock_level = "Minimum stock level is required.";
        isValid = false;
    }
    // Validate text field
    if (!supplier_id.value) {
        alerts.supplier_id = "Supplier is required.";
        isValid = false;
    }
    // Validate text field
    if (!stock_date.value) {
        alerts.stock_date = "Stock date is required.";
        isValid = false;
    }
    // Check for environment selection
    if (!environment.value) {
        alerts.environment = "Environment selection is required.";
        isValid = false;
    }

    return isValid;
};

// Handle form submission
const handleSubmit = async () => {
    alerts.success = "";
    alerts.error = "";

    // Validate form before submission
    if (!validateForm()) {
        return; // Stop submission if validation fails
    }

    isLoading.value = true; // Set loading state to true

    try {
        const token = getToken(); // Retrieve the token

        // Send a POST request to the backend API with form data
        const response = await axios.post("/stockstore",
            {
                product_id: product_id.value,
                brand_id: brand_id.value,
                measurement_id: measurement_id.value,
                quantity: quantity.value.replace(/,/g, ""), // Remove commas
                unit_price: unit_price.value.replace(/,/g, ""), // Remove commas
                total_cost: total_cost.value, // Assuming no commas need to be removed
                sale_price: sale_price.value.replace(/,/g, ""), // Remove commas
                min_stock_level: min_stock_level.value.replace(/,/g, ""), // Remove commas
                supplier_id: supplier_id.value,
                stock_date: stock_date.value, // Assuming it's already in correct format
                environment: environment.value,
            },
            {
                headers: {
                    Authorization: `Bearer ${token}`, // Include the token in the Authorization header
                },
            }
        );

        if (response.data.success) {
            alerts.success = "Stock created successfully.";
            if (shouldRedirect.value) {
                setTimeout(() => router.push("/stocklist"), 1000); // Redirect after 1 second
            }
            //console.log(response.data.data)
        } else {
            alerts.error = response.data.message || "An error occurred during submission.";
        }
    } catch (error) {
        handleError(error); // Handle error using centralized error handle
    } finally {
        isLoading.value = false; // Hide loading spinner
    }
};

// Function to fetch products from the API
const getProducts = async () => {
    try {
        const token = getToken(); // Retrieve the token

        // Fetch products from the API
        const response = await axios.get("/getproducts", {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the Authorization header
            },
        });

        if (response.status === 200) {
            products.value = response.data; // Store the fetched products
        } else {
            console.error("Error fetching products:", response.statusText);
        }
    } catch (error) {
        console.error("Error fetching products:", error);
    }
};

// Function to fetch brands by product ID
const fetchBrandsByProductId = async (productId) => {
    try {
        const token = getToken(); // Retrieve the token

        // Fetch brands by product ID from the API
        const response = await axios.get(`/getbrandsbyproduct/${productId}`, {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the Authorization header
            },
        });

        if (response.status === 200) {
            brands.value = response.data; // Store the fetched brands
        } else {
            console.error("Error fetching brands by product ID:", response.statusText);
        }
    } catch (error) {
        console.error("Error fetching brands by product ID:", error);
    }
};

// Function to fetch measurements from the API
const getMeasurements = async () => {
    try {
        const token = getToken(); // Retrieve the token

        // Fetch measurements from the API
        const response = await axios.get("/getmeasurements", {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the Authorization header
            },
        });

        if (response.status === 200) {
            measurements.value = response.data; // Store the fetched measurements
        } else {
            console.error("Error fetching measurements:", response.statusText);
        }
    } catch (error) {
        console.error("Error fetching measurements:", error);
    }
};

// Function to fetch suppliers from the API
const getSuppliers = async () => {
    try {
        const token = getToken(); // Retrieve the token

        // Fetch suppliers from the API
        const response = await axios.get("/getsuppliers", {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the Authorization header
            },
        });

        if (response.status === 200) {
            suppliers.value = response.data; // Store the fetched suppliers
        } else {
            console.error("Error fetching suppliers:", response.statusText);
        }
    } catch (error) {
        console.error("Error fetching suppliers:", error);
    }
};

// Watch for changes in product_id
watch(product_id, async (newProductId) => {
    if (newProductId) {
        await fetchBrandsByProductId(newProductId);
    } else {
        brands.value = []; // Clear brands if no product is selected
    }
});

// Initialize Select2 on all select fields
const initializeSelect2 = () => {
    $(function () {
        // Apply Select2 to all select elements
        $(".select")
        .select2({
            allowClear: true,
            placeholder: "Select an option", // Placeholder for better UX
        })
        .on("change", function () {
            const fieldName = $(this).attr("id"); // Get the ID of the select field
            const newValue = $(this).val(); // Get the new value of the field

            switch (fieldName) {
            case "product":
                product_id.value = newValue || ""; // Use empty string if cleared
                break;
            case "brand":
                brand_id.value = newValue || ""; // Use empty string if cleared
                break;
            case "measurement":
                measurement_id.value = newValue || ""; // Use empty string if cleared
                break;
            case "supplier":
                supplier_id.value = newValue || ""; // Use empty string if cleared
                break;
            default:
                console.warn(`Unhandled field: ${fieldName}`);
            }

            if (!newValue) {
            //console.log(`${fieldName} was cleared.`);
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

// Initial data fetch
// Initial data fetch
onMounted(() => {
	loadRedirectPreference(); // Load the saved redirect preference
    getProducts(); // Fetch products
    getMeasurements(); // Fetch measurements
    getSuppliers(); // Fetch suppliers
    initializeSelect2(); // Initialize Select2 after the DOM is rendered
    initializeDatePickers(); // Initialize date pickers
});

// Initialize Date Pickers
const initializeDatePickers = () => {
    // Initialize stock_date date picker
    $(".stock_date").daterangepicker(
        {
            singleDatePicker: true,
            autoUpdateInput: false, // Prevents auto-filling
            locale: { format: "YYYY-MM-DD" }, // Set the desired date format
        },
        function (start) {
            stock_date.value = start.format("YYYY-MM-DD"); // Update the reactive value
            $(".stock_date").val(stock_date.value); // Update the input field
        }
    );
};

// Watch for changes in stock_date
watch(stock_date, (newDate) => {
    if (newDate) {
        $(".stock_date").val(newDate); // Update the input field
    }
});

// Initialize on mount
onMounted(() => {
    initializeDatePickers(); // Initialize date pickers
});

// Watch for changes to ensure formatting
watch(quantity, (newVal) => {
    quantity.value = formatAmount(newVal);
});

watch(unit_price, (newVal) => {
    unit_price.value = formatAmount(newVal);
});

watch(sale_price, (newVal) => {
    sale_price.value = formatAmount(newVal);
});

watch(min_stock_level, (newVal) => {
    min_stock_level.value = formatAmount(newVal);
});

// Watch for changes in brands to reinitialize Select2
watch(brands, (newBrands) => {
    if (newBrands.length > 0) {
        $('#brand').select2({
            allowClear: true,
            placeholder: "Select a brand",
        });
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
                    <RouterLink to="/stocklist" class="text-decoration-none">Stock</RouterLink>
                </li>
                <li class="breadcrumb-item text-secondary" aria-current="page">Create</li>
            </ol>
        </div>
		<!-- App Hero header ends -->

		<!-- App body starts -->
        <div class="app-body">
            <div class="row gx-3">
                <div class="col-xxl-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title">Create Stock</h5>
                        </div>
                        <div class="card-body">
							<!-- Loading Spinner with Text -->
							<div
								v-if="isLoading"
								class="d-flex justify-content-left align-items-left mb-3"
							>
								<div class="spinner-border text-success" role="status">
									<span class="visually-hidden">Loading...</span>
								</div>
								<span class="ms-2">Please wait...</span>
							</div>

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
								<!-- Text Input -->
                                <div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Product</label>
										<select
											v-model="product_id"
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
										<!-- Display validation message for product -->
										<div
											v-if="alerts.product_id"
											class="text-danger mt-2"
										>
											{{ alerts.product_id }}
										</div>
									</div>
								</div>
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Brand</label>
										<select
											v-model="brand_id"
											@change="validateForm"
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
										<!-- Display validation message for brand -->
										<div
											v-if="alerts.brand_id"
											class="text-danger mt-2"
										>
											{{ alerts.brand_id }}
										</div>
									</div>
								</div>
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Measurement</label>
										<select
											v-model="measurement_id"
											@change="validateForm"
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
										<!-- Display validation message for measurement_id -->
										<div
											v-if="alerts.measurement_id"
											class="text-danger mt-2"
										>
											{{ alerts.measurement_id }}
										</div>
									</div>
								</div>
								<!-- Text Input -->
                                <div class="col-lg-3 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input
											v-model="quantity"
											@input="updateAmount($event, 'quantity')"
											type="text"
											class="form-control"
											placeholder="Quantity"
										/>
										<!-- Display validation message -->
                                        <div v-if="alerts.quantity" class="text-danger mt-2">
                                            {{ alerts.quantity }}
                                        </div>
                                    </div>
                                </div>		
								<!-- Text Input -->
                                <div class="col-lg-3 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Unit Price</label>
                                        <input
											v-model="unit_price"
											@input="updateAmount($event, 'unit_price')"
											type="text"
											class="form-control"
											placeholder="Unit Price"
										/>
										<!-- Display validation message -->
                                        <div v-if="alerts.unit_price" class="text-danger mt-2">
                                            {{ alerts.unit_price }}
                                        </div>
                                    </div>
                                </div>		
								<!-- Text Input -->
                                <div class="col-lg-3 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Total Cost</label>
                                        <input
											v-model="total_cost"
											type="text"
											class="form-control"
											placeholder="Total Cost"
											readonly="true"
										/>
										<!-- Display validation message -->
                                        <div v-if="alerts.total_cost" class="text-danger mt-2">
                                            {{ alerts.total_cost }}
                                        </div>
                                    </div>
                                </div>		
								<!-- Text Input -->
                                <div class="col-lg-3 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Sale Price</label>
                                        <input
											v-model="sale_price"
											@input="updateAmount($event, 'sale_price')"
											type="text"
											class="form-control"
											placeholder="Sale Price"
										/>
										<!-- Display validation message -->
                                        <div v-if="alerts.sale_price" class="text-danger mt-2">
                                            {{ alerts.sale_price }}
                                        </div>
                                    </div>
                                </div>			
								<!-- Text Input -->
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Minimum Stock Level</label>
                                        <input
											v-model="min_stock_level"
											@input="updateAmount($event, 'min_stock_level')"
											type="text"
											class="form-control"
											placeholder="Minimum Stock Level"
										/>
										<!-- Display validation message -->
                                        <div v-if="alerts.min_stock_level" class="text-danger mt-2">
                                            {{ alerts.min_stock_level }}
                                        </div>
                                    </div>
                                </div>
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Supplier</label>
										<select
											v-model="supplier_id"
											@change="validateForm"
											id="supplier"
											class="form-select select"
										>
											<option value="" disabled>Select supplier</option>
											<option
												v-for="supplier in suppliers"
												:key="supplier.id"
												:value="supplier.id"
											>
												{{ supplier.name }}
											</option>
										</select>
										<!-- Display validation message for supplier -->
										<div
											v-if="alerts.supplier_id"
											class="text-danger mt-2"
										>
											{{ alerts.supplier_id }}
										</div>
									</div>
								</div>			
								<!-- Text Input -->
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Stock Date</label>
                                        <div class="input-group">
                                            <input
                                                type="text"
                                                class="form-control stock_date"
                                                placeholder="YYYY-MM-DD"
                                            />
                                            <span class="input-group-text">
                                                <i class="bi bi-calendar4"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
								<!-- Environment Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Environment</label>
										<div>
											<div class="form-check form-check-inline">
												<input
													v-model="environment"
													class="form-check-input"
													type="radio"
													name="environment"
													value="DEVELOPMENT"
													id="inlineDevelopment"
												/>
												<label
													class="form-check-label"
													for="inlineDevelopment"
													>Development</label
												>
											</div>
											<div class="form-check form-check-inline">
												<input
													v-model="environment"
													class="form-check-input"
													type="radio"
													name="environment"
													value="TEST"
													id="inlineTest"
												/>
												<label
													class="form-check-label"
													for="inlineTest"
													>Test</label
												>
											</div>
											<div class="form-check form-check-inline">
												<input
													v-model="environment"
													class="form-check-input"
													type="radio"
													name="environment"
													value="PRODUCTION"
													id="inlineProduction"
												/>
												<label
													class="form-check-label"
													for="inlineProduction"
													>Production</label
												>
											</div>
										</div>
										<!-- Display validation message for environment -->
										<div
											v-if="alerts.environment"
											class="text-danger mt-2"
										>
											{{ alerts.environment }}
										</div>
									</div>
								</div>
								<!-- Save Button and Redirect Options -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<!-- Generalized Label -->
										<label class="form-label">After Save Action</label>
										<div>
											<div class="form-check form-check-inline">
												<input
													v-model="shouldRedirect"
													class="form-check-input"
													type="radio"
													name="redirectOption"
													:value="true"
													id="inlineRedirect"
													@change="saveRedirectPreference(true)"
												/>
												<label
													class="form-check-label"
													for="inlineRedirect"
												>Save and go to list</label>
											</div>
											<div class="form-check form-check-inline">
												<input
													v-model="shouldRedirect"
													class="form-check-input"
													type="radio"
													name="redirectOption"
													:value="false"
													id="inlineNoRedirect"
													@change="saveRedirectPreference(false)"
												/>
												<label
													class="form-check-label"
													for="inlineNoRedirect"
												>Save and stay</label>
											</div>
										</div>
									</div>
								</div>
                            </div>
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
								<!-- Send Button -->
								<button
									type="button"
									class="btn btn-success"
									@click="handleSubmit"
									:disabled="isLoading"
								>
									<i class="bi bi-send"></i> Submit
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
/* Scoped styles if needed */
</style>
