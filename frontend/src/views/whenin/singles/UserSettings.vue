<script setup>
import { computed, onMounted, ref, reactive, watch } from "vue";
// Ensure axios is properly configured
import axios from "@/axios"; 
import { RouterLink, useRouter } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";

useCustomUtils();

// Initialize reactive variables for user creation fields, alerts, and loading state
const current_password = ref("");
const new_password = ref("");
const new_password_confirmation = ref("");
const alerts = reactive({
	success: "",
	error: "",
	current_password: "",
	new_password: "",
	new_password_confirmation: "",
});
const isLoading = ref(false);
const router = useRouter();
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
// Add a ref to toggle password visibility
const showPassword = ref(false);

// Function to validate the form fields
const validateForm = () => {
	// Clear previous validation alerts
	alerts.current_password = "";
	alerts.new_password = "";
	alerts.new_password_confirmation = "";

	let isValid = true;

	// Validate current password field
	if (!current_password.value) {
		alerts.current_password = "current password is required.";
		isValid = false;
	}

	// Validate new password field
	if (!new_password.value) {
		alerts.new_password = "new password is required.";
		isValid = false;
	}
	
	// Validate confirm new password field
	if (!new_password_confirmation.value) {
		alerts.new_password_confirmation = "confirm new password is required.";
		isValid = false;
	}

	// Validate confirm & new password field
	if (new_password.value !== new_password_confirmation.value) {
		alerts.new_password_confirmation = "Passwords do not match.";
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
		// Retrieve the token from local storage
		const token = getToken(); // Retrieve the token

		// Send a POST request to the backend API with form data
		const response = await axios.post("/userchangepassword",
			{
				current_password: current_password.value,
				new_password: new_password.value,
				new_password_confirmation: new_password_confirmation.value,
			},
			{
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			}
		);

		// Check if the user creation was successful
		if (response.data.success) {
			alerts.success = "Password changed successfully!";
		} else {
			alerts.error =response.data.message || "Failed to change password. Please try again.";
		}
	} catch (error) {
		handleError(error); // Handle error using centralized error handler
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
	}
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
					<RouterLink to="/dashboard" class="text-decoration-none"
						>Home</RouterLink
					>
				</li>
				<li class="breadcrumb-item text-secondary" aria-current="page">
					User Settings
				</li>
			</ol>
			<!-- Breadcrumb end -->
		</div>
		<!-- App Hero header ends -->

		<!-- App body starts -->
		<div class="app-body">
			<!-- Row start -->
			<div class="row gx-3">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-header">
							<h5 class="card-title">Change Password</h5>
						</div>
						<div class="card-body">
							<!-- Loading Spinner with Text -->
                            <div v-if="isLoading" class="d-flex justify-content-left align-items-left mb-3">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="ms-2">Please wait...</span>
                            </div>

                            <!-- Success Alert -->
                            <div v-if="alerts.success"
                                class="alert border border-success alert-dismissible fade show text-success" role="alert">
                                {{ alerts.success }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>

                            <!-- Error Alert -->
                            <div v-if="alerts.error"
                                class="alert border border-danger alert-dismissible fade show text-danger" role="alert">
                                {{ alerts.error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
							<!-- Password visibility toggle -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" v-model="showPassword" id="showPassword">
                                <label class="form-check-label" for="showPassword">
                                    Show Passwords
                                </label>
                            </div>
                            <!-- Row start -->
							<div class="row gx-3">
								<!-- Form Field Start -->
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label for="current_password" class="form-label"
											>Current Password</label
										>
										<input
											v-model="current_password"											
											@input="validateForm"
          									@blur="validateForm"
											:type="showPassword ? 'text' : 'password'"
											class="form-control"
											id="current_password"
											placeholder="Enter Current Password"
										/>
										<!-- Display validation message -->
										<div v-if="alerts.current_password" class="text-danger mt-2">
											{{ alerts.current_password }}
										</div>
									</div>
								</div>
								<!-- Form Field Start -->
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label for="new_password" class="form-label"
											>New Password</label
										>
										<input
											v-model="new_password"											
											@input="validateForm"
          									@blur="validateForm"
											:type="showPassword ? 'text' : 'password'"
											class="form-control"
											id="new_password"
											placeholder="Enter New Password"
										/>
										<!-- Display validation message -->
										<div v-if="alerts.new_password" class="text-danger mt-2">
											{{ alerts.new_password }}
										</div>
									</div>
								</div>
								<!-- Form Field Start -->
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label for="new_password_confirmation" class="form-label"
											>Confirm New Password</label
										>
										<input
											v-model="new_password_confirmation"											
											@input="validateForm"
          									@blur="validateForm"
											:type="showPassword ? 'text' : 'password'"
											class="form-control"
											id="new_password_confirmation"
											placeholder="Confirm New Password"
										/>
										<!-- Display validation message -->
										<div v-if="alerts.new_password_confirmation" class="text-danger mt-2">
											{{ alerts.new_password_confirmation }}
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center my-2 my-lg-0">
                                <!-- Back Button -->
                                <button type="button" class="btn btn-outline-secondary" @click="router.go(-1)">
                                    <i class="fa fa-arrow-left"></i> Back
                                </button>
                                <!-- Save Button -->
                                <button type="button" class="btn btn-success" @click="handleSubmit" :disabled="isLoading">
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

<style scoped></style>
