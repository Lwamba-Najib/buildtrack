<script setup>
import { onMounted, ref, reactive, watchEffect } from "vue";
import axios from "@/axios"; // Ensure axios is properly configured
import { useRouter, useRoute, RouterLink } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";
useCustomUtils();

// Initialize reactive variables for product  update fields, alerts, and loading state
const productId = ref(""); // product  ID for updates
const categories = ref([]);
const name = ref("");
const category_id = ref("");
const alerts = reactive({
	success: "",
	error: "",
	name: "",
	category_id: "",	
});
const isLoading = ref(false);
const router = useRouter();
const route = useRoute(); // Get route params
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

	let isValid = true;

	// Validate each field
	if (!name.value) {
		alerts.name = "Name is required.";
		isValid = false;
	}

	// Validate category_id field
	if (!category_id.value) {
		alerts.category_id = "Category is required.";
		isValid = false;
	}

	return isValid;
};

// Function to handle form submission (Update User)
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

		// Send a PUT request to the backend API with updated form data
		const response = await axios.put(
			`/productupdate/${productId.value}`,
			{
				name: name.value,
				category_id: category_id.value,
			},
			{
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			}
		);

		// Check if the product  update was successful
		if (response.data.success) {
			alerts.success = "Product  updated successfully!";
			setTimeout(() => router.push("/productlist"), 1000); // Redirect after 1 second
		} else {
			alerts.error = response.data.message || "Failed to update product . Please try again.";
		}
	} catch (error) {
		handleError(error); // Handle error using centralized error handle
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
	}
};

// Function to fetch the current product data
const getProductDetails = async () => {
	try {
		const token = getToken(); // Retrieve the token

		// Fetch category details based on productId
		const response = await axios.get(`/productshow/${productId.value}`, {
			headers: {
				Authorization: `Bearer ${token}`,
			},
		});

		if (response.status === 200) {
			const product = response.data.data; // Adjust if necessary based on the actual response structure
			name.value = product.name;
			category_id.value = product.category_id;
		} else {
			console.error("Error fetching product  details:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching category details:", error);
	}
};
// Function to fetch categories from the API
const getCategories = async () => {
	try {
		// Retrieve the token from local storage
		const token = localStorage.getItem("token");

		// Ensure the token exists before making the request
		if (!token) {
			throw new Error("No token found");
		}

		// Fetch categories from the API
		const response = await axios.get("/getcategories", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
		});

		if (response.status === 200) {
			categories.value = response.data; // Store the fetched categories
		} else {
			console.error("Error fetching categories:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching categories:", error);
	}
};
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
			case "category":
				category_id.value = newValue || ""; // Use empty string if cleared
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
		});
	});
};
// Initial data fetch (fetch categories and user details)
onMounted(() => {
	productId.value = route.params.id; // Get the user ID from route params
	getProductDetails();
	getCategories(); // Fetch categories when the component is mounted
	initializeSelect2(); // Initialize Select2 after the DOM is rendered
});
// Reinitialize Select2 and validate form on dependency changes
watchEffect([category_id],() => {
    validateForm();
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
					<RouterLink to="/categorylist" class="text-decoration-none"
						>products</RouterLink
					>
				</li>
				<li class="breadcrumb-item text-secondary" aria-current="page">Update</li>
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
							<h5 class="card-title">Update Product </h5>
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
								<!-- Category Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Category</label>
										<select
											v-model="category_id"
											id="category"
											class="form-select select"
										>
											<option value="" disabled>Select category</option>
											<option
												v-for="category in categories"
												:key="category.id"
												:value="category.id"
											>
												{{ category.name }}
											</option>
										</select>
										<!-- Display validation message for category -->
										<div
											v-if="alerts.category_id"
											class="text-danger mt-2"
										>
											{{ alerts.category_id }}
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
