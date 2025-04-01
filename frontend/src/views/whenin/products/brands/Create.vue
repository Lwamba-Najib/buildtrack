<script setup>
import { onMounted, ref, reactive, watch } from "vue";
import axios from "@/axios"; // Ensure axios is properly configured
import { useRouter, RouterLink } from "vue-router";
import { useStore } from 'vuex';
import { useCustomUtils } from "@/utils/customUtils";
useCustomUtils();

// Initialize reactive variables for user creation fields, alerts, and loading state
const products = ref([]);
const name = ref("");
const product_ids = ref([]); // Change from single product_id to multiple
const environment = ref("");
const shouldRedirect = ref(true); // New reactive variable for redirection control
const alerts = reactive({
	success: "",
	error: "",
	name: "",
	product_ids: "",
	environment: "",
});
const isLoading = ref(false);
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
	alerts[alertField] =
		error.response?.data?.message || "An error occurred. Please try again later.";
	console.error("API Error:", error);
};
// Function to validate the form fields
const validateForm = () => {
	// Clear previous validation alerts
	alerts.name = "";
	alerts.product_ids = "";
	alerts.environment = "";

	let isValid = true;

	// Validate name field
	if (!name.value) {
		alerts.name = "Name is required.";
		isValid = false;
	}

	// Validate product_ids field
	if (!product_ids.value) {
		alerts.product_ids = "At least one product is required.";
		isValid = false;
	}

	// Validate environment field
	if (!environment.value) {
		alerts.environment = "Environment is required.";
		isValid = false;
	}

	return isValid;
};

// Function to handle form submission
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
		const response = await axios.post(
			"/brandstore",
			{
				name: name.value,
				product_ids: product_ids.value, // Sending multiple product IDs
				environment: environment.value,
			},
			{
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			}
		);

		// Check if the brand creation was successful
		if (response.data.success) {
			alerts.success = "Brand created successfully!";
			if (shouldRedirect.value) {
				setTimeout(() => router.push("/brandlist"), 1000); // Redirect after 1 second
			}
		} else {
			alerts.error = response.data.message || "Failed to create brand. Please try again.";
		}
	} catch (error) {
		handleError(error); // Handle error using centralized error handle
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
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
// Initialize Select2 on all select fields
const initializeSelect2 = () => {
    $(function () {
        // Apply Select2 to all select elements
        $(".select")
            .select2({
                allowClear: true,
                placeholder: "Select an option",
            })
            .on("change", function () {
                const fieldName = $(this).attr("id");
                const newValue = $(this).val();

                switch (fieldName) {
                    case "product":
                        product_ids.value = newValue || []; // Array for multi-select
                        break;
                    default:
                        console.warn(`Unhandled field: ${fieldName}`);
                }

                if (!newValue || newValue.length === 0) {
                    console.log(`${fieldName} was cleared.`);
                }
            })
            .on("select2:unselecting", function (e) {
                console.log("Clearing select field:", $(this).attr("id"));
            })
            .on("select2:open", function () {
                setTimeout(() => {
                    let searchField = document.querySelector(
                        ".select2-container--open .select2-search__field"
                    );
                    if (searchField) {
                        searchField.focus();
                    }
                }, 50);
            });

        // Special configuration for product multi-select
        $("#product").select2({
            allowClear: true,
            multiple: true,          // Enable multi-select
            placeholder: "Select products", 
            closeOnSelect: false      // Keep dropdown open
        });
    });
};

// Initial data fetch
onMounted(() => {
	loadRedirectPreference(); // Load the saved redirect preference
	getProducts(); // Fetch categories when the component is mounted
	initializeSelect2(); // Initialize Select2 after the DOM is rendered
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
					<RouterLink to="/brandlist" class="text-decoration-none"
						>Brands</RouterLink
					>
				</li>
				<li class="breadcrumb-item text-secondary" aria-current="page">Create</li>
			</ol>
		</div>
		<!-- App Hero header ends -->

		<!-- App body starts -->
		<div class="app-body">
			<!-- Row start -->
			<div class="row gx-3">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-header">
							<h5 class="card-title">Create Brand</h5>
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
								<!-- Name Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Name</label>
										<input
											v-model="name"
											type="text"
											class="form-control"
											placeholder="Enter name"
										/>
										<!-- Display validation message for name -->
										<div v-if="alerts.name" class="text-danger mt-2">
											{{ alerts.name }}
										</div>
									</div>
								</div>
								<!-- Product Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Product</label>
										<select
											v-model="product_id"
											id="product"
											class="form-select select" multiple
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
											v-if="alerts.product_ids"
											class="text-danger mt-2"
										>
											{{ alerts.product_ids }}
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
								<!-- Save Button -->
								<button
									type="button"
									class="btn btn-success"
									@click="handleSubmit"
									:disabled="isLoading"
								>
									<i class="fa fa-save"></i> Save
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