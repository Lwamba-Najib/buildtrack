<script setup>
import { onMounted, ref, reactive, watchEffect } from "vue";
import axios from "@/axios"; // Ensure axios is properly configured
import { useRouter, useRoute, RouterLink } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";
useCustomUtils();

// Initialize reactive variables for measurement update fields, alerts, and loading state
const measurementId = ref(""); // measurement ID for updates
const name = ref("");
const alerts = reactive({
	success: "",
	error: "",
	name: "",	
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
			`/measurementupdate/${measurementId.value}`,
			{
				name: name.value,
			},
			{
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			}
		);

		// Check if the measurement update was successful
		if (response.data.success) {
			alerts.success = "Measurement updated successfully!";
			setTimeout(() => router.push("/measurementlist"), 1000); // Redirect after 1 second
		} else {
			alerts.error = response.data.message || "Failed to update measurement. Please try again.";
		}
	} catch (error) {
		handleError(error); // Handle error using centralized error handle
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
	}
};

// Function to fetch the current measurement data
const getMeasurementDetails = async () => {
	try {
		const token = getToken(); // Retrieve the token

		// Fetch measurement details based on measurementId
		const response = await axios.get(`/measurementshow/${measurementId.value}`, {
			headers: {
				Authorization: `Bearer ${token}`,
			},
		});

		if (response.status === 200) {
			const measurement = response.data.data; // Adjust if necessary based on the actual response structure
			name.value = measurement.name;
		} else {
			console.error("Error fetching measurement details:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching measurement details:", error);
	}
};
// Initial data fetch (fetch categories and user details)
onMounted(() => {
	measurementId.value = route.params.id; // Get the user ID from route params
	getMeasurementDetails();
});
// Reinitialize Select2 and validate form on dependency changes
watchEffect(() => {
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
					<RouterLink to="/measurementlist" class="text-decoration-none"
						>Measurements</RouterLink
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
							<h5 class="card-title">Update Measurement</h5>
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
								<div class="col-lg-12 col-sm-4 col-12">
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
