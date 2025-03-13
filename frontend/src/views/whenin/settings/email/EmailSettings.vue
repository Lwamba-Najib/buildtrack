<script setup>
import { onMounted, ref, reactive } from "vue";
import axios from "@/axios"; // Ensure axios is properly configured
import { useRouter } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";
useCustomUtils();

// Initialize reactive variables for form fields, alerts, and loading state
const sender_name = ref("");
const sender_email = ref("");
const smtp_driver = ref("");
const smtp_host = ref("");
const smtp_username = ref("");
const smtp_password = ref("");
const smtp_encryption = ref("");
const smtp_port = ref("");
const alerts = reactive({
	success: "",
	error: "",
	sender_name: "",
	sender_email: "",
	smtp_driver: "",
	smtp_host: "",
	smtp_username: "",
	smtp_password: "",
	smtp_encryption: "",
	smtp_port: "",
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

// Fetch existing email settings when the component is mounted
const fetchEmailSettings = async () => {
	isLoading.value = true; // Set loading state to true
	try {
		const token = getToken(); // Retrieve the token

		// Send GET request to fetch existing email settings
		const response = await axios.get("/emailsettingslist", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
		});

		// Populate form fields with the fetched data
		if (response.data.success && response.data.data) {
			const emailSettings = response.data.data;
			sender_name.value = emailSettings.sender_name;
			sender_email.value = emailSettings.sender_email;
			smtp_driver.value = emailSettings.smtp_driver;
			smtp_host.value = emailSettings.smtp_host;
			smtp_username.value = emailSettings.smtp_username;
			smtp_encryption.value = emailSettings.smtp_encryption;
			smtp_port.value = emailSettings.smtp_port;
		} else {
			alerts.error = response.data.message || "Failed to fetch email settings.";
		}
	} catch (error) {
		handleError(error); // Handle error using centralized error handler
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
	}
};

// Function to validate the form fields
const validateForm = () => {
	// Clear previous validation alerts
	Object.keys(alerts).forEach((field) => {
		if (field !== "success" && field !== "error") {
			alerts[field] = "";
		}
	});

	let isValid = true;

	// Validate sender_name field
	if (!sender_name.value) {
		alerts.sender_name = "Sender name is required.";
		isValid = false;
	}

	// Validate sender_email field
	if (!sender_email.value) {
		alerts.sender_email = "Sender email is required.";
		isValid = false;
	}

	// Validate smtp_driver field
	if (!smtp_driver.value) {
		alerts.smtp_driver = "SMTP driver is required.";
		isValid = false;
	}

	// Validate smtp_host field
	if (!smtp_host.value) {
		alerts.smtp_host = "SMTP host is required.";
		isValid = false;
	}

	// Validate smtp_username field
	if (!smtp_username.value) {
		alerts.smtp_username = "SMTP username is required.";
		isValid = false;
	}

	// Validate smtp_password field
	if (!smtp_password.value) {
		alerts.smtp_password = "SMTP password is required.";
		isValid = false;
	}

	// Validate smtp_encryption field
	if (!smtp_encryption.value) {
		alerts.smtp_encryption = "SMTP encryption is required.";
		isValid = false;
	}

	// Validate smtp_port field
	if (!smtp_port.value) {
		alerts.smtp_port = "SMTP port is required.";
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

		// Send POST request to save email settings
		const response = await axios.post(
			"/emailsettingsstore",
			{
				sender_name: sender_name.value,
				sender_email: sender_email.value,
				smtp_driver: smtp_driver.value,
				smtp_host: smtp_host.value,
				smtp_username: smtp_username.value,
				smtp_password: smtp_password.value,
				smtp_encryption: smtp_encryption.value,
				smtp_port: smtp_port.value,
			},
			{
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			}
		);

		// Check if the email settings were successfully saved
		if (response.data.success) {
			alerts.success = "Email settings saved successfully!";
			setTimeout(() => {
				fetchEmailSettings(); // Fetch updated email settings after saving
			}, 1000); // Wait 1 second before fetching updated data
		} else {
			alerts.error =
				response.data.message ||
				"Failed to save email settings. Please try again.";
		}
	} catch (error) {
		handleError(error); // Handle error using centralized error handler
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
	}
};

// Fetch email settings when the component is mounted
onMounted(() => {
	fetchEmailSettings(); // Fetch initial email settings from the API
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
					<RouterLink to="/dashboard" class="text-decoration-none"
						>Home</RouterLink
					>
				</li>
				<li class="breadcrumb-item text-secondary" aria-current="page">
					Email Settings
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
							<h5 class="card-title">Configure Email Settings</h5>
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
										<label class="form-label">Sender Name</label>
										<input
											v-model="sender_name"
											type="text"
											class="form-control"
											placeholder="Enter sender name"
										/>
										<!-- Display validation message for sender name -->
										<div
											v-if="alerts.sender_name"
											class="text-danger mt-2"
										>
											{{ alerts.sender_name }}
										</div>
									</div>
								</div>
								<!-- Email Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Sender Email</label>
										<input
											v-model="sender_email"
											type="email"
											class="form-control"
											placeholder="Enter sender email"
										/>
										<!-- Display validation message for sender email -->
										<div
											v-if="alerts.sender_email"
											class="text-danger mt-2"
										>
											{{ alerts.sender_email }}
										</div>
									</div>
								</div>
								<!-- SMTP Driver Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">SMTP Driver</label>
										<input
											v-model="smtp_driver"
											type="text"
											class="form-control"
											placeholder="Enter SMTP Driver"
										/>
										<!-- Display validation message for SMTP Driver -->
										<div
											v-if="alerts.smtp_driver"
											class="text-danger mt-2"
										>
											{{ alerts.smtp_driver }}
										</div>
									</div>
								</div>
								<!-- SMTP Host Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">SMTP Host</label>
										<input
											v-model="smtp_host"
											type="text"
											class="form-control"
											placeholder="Enter SMTP Host"
										/>
										<!-- Display validation message for SMTP Host -->
										<div
											v-if="alerts.smtp_host"
											class="text-danger mt-2"
										>
											{{ alerts.smtp_host }}
										</div>
									</div>
								</div>
								<!-- SMTP Username Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">SMTP Username</label>
										<input
											v-model="smtp_username"
											type="text"
											class="form-control"
											placeholder="Enter SMTP Username"
										/>
										<!-- Display validation message for SMTP Username -->
										<div
											v-if="alerts.smtp_username"
											class="text-danger mt-2"
										>
											{{ alerts.smtp_username }}
										</div>
									</div>
								</div>
								<!-- SMTP Password Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">SMTP Password</label>
										<input
											v-model="smtp_password"
											type="password"
											class="form-control"
											placeholder="Enter SMTP Password"
										/>
										<!-- Display validation message for SMTP Password -->
										<div
											v-if="alerts.smtp_password"
											class="text-danger mt-2"
										>
											{{ alerts.smtp_password }}
										</div>
									</div>
								</div>
								<!-- SMTP Encryption Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">SMTP Encryption</label>
										<input
											v-model="smtp_encryption"
											type="text"
											class="form-control"
											placeholder="Enter SMTP Username"
										/>
										<!-- Display validation message for SMTP Encryption -->
										<div
											v-if="alerts.smtp_encryption"
											class="text-danger mt-2"
										>
											{{ alerts.smtp_encryption }}
										</div>
									</div>
								</div>
								<!-- SMTP Port Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">SMTP Port</label>
										<input
											v-model="smtp_port"
											type="text"
											class="form-control"
											placeholder="Enter SMTP Port"
										/>
										<!-- Display validation message for SMTP Port -->
										<div
											v-if="alerts.smtp_port"
											class="text-danger mt-2"
										>
											{{ alerts.smtp_port }}
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
								<button
									type="button"
									class="btn btn-outline-secondary"
									@click="router.go(-1)"
								>
									<i class="fa fa-arrow-left"></i> Back
								</button>
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

<style scoped></style>
