<script setup>
import { onMounted, ref, reactive, watch } from "vue";
import axios from "@/axios"; // Ensure axios is properly configured
import { useRouter, useRoute } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";
useCustomUtils();

// Initialize reactive variables for role update fields, alerts, and loading state
const roleId = ref(""); // Role ID for updates
const name = ref("");
const alerts = reactive({
	success: "",
	error: "",
	name: "",	
});
const isLoading = ref(false);
const router = useRouter();
const route = useRoute(); // Get route params

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
		// Retrieve the token from local storage
		const token = localStorage.getItem("token");
		if (!token) {
			throw new Error("No token found");
		}

		// Send a PUT request to the backend API with updated form data
		const response = await axios.put(
			`/roleupdate/${roleId.value}`,
			{
				name: name.value,
			},
			{
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			}
		);

		// Check if the role update was successful
		if (response.data.success) {
			alerts.success = "Role updated successfully!";
			setTimeout(() => router.push("/rolelist"), 1000); // Redirect after 1 second
		} else {
			alerts.error = response.data.message || "Failed to update role. Please try again.";
		}
	} catch (error) {
		// Handle different types of errors
		if (error.response && error.response.status === 422) {
			const errors = error.response.data.errors;
			alerts.name = errors.name ? errors.name[0] : "";
		} else {
			alerts.error = error.response?.data?.message || "An error occurred. Please try again later.";
		}
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
	}
};

// Function to fetch the current role data
const getRoleDetails = async () => {
	try {
		const token = localStorage.getItem("token");
		if (!token) {
			throw new Error("No token found");
		}

		// Fetch role details based on roleId
		const response = await axios.get(`/roleshow/${roleId.value}`, {
			headers: {
				Authorization: `Bearer ${token}`,
			},
		});

		if (response.status === 200) {
			const role = response.data.data; // Adjust if necessary based on the actual response structure
			name.value = role.name;
		} else {
			console.error("Error fetching role details:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching role details:", error);
	}
};
// Initial data fetch (fetch roles and user details)
onMounted(() => {
	roleId.value = route.params.id; // Get the user ID from route params
	getRoleDetails();
});
// Reinitialize Select2 and validate form on dependency changes
watch(() => {
    validateForm();
}, { immediate: true }); // Ensure both actions run immediately when dependencies are populated
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
					<RouterLink to="/rolelist" class="text-decoration-none"
						>Roles</RouterLink
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
							<h5 class="card-title">Update Role</h5>
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
