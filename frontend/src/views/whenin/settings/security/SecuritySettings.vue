<script setup>
import { onMounted, ref, reactive } from "vue";
import axios from "@/axios"; // Ensure axios is properly configured
import { useRouter } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";
useCustomUtils();

// Initialize reactive variables for form fields, alerts, and loading state
const security_settings_2fa = ref("No"); // Ensure this is a string
const security_settings_lowercase = ref("No");
const security_settings_uppercase = ref("No");
const security_settings_numbers = ref("No");
const security_settings_symbols = ref("No");
const security_settings_length = ref("8");
const security_settings_expiry = ref("6");
const dormant_account_expiry = ref("90");
const security_settings_login_attempt = ref("5");
const security_settings_history_counts = ref("5");

const alerts = reactive({
	success: "",
	error: "",
	security_settings_2fa: "",
	security_settings_lowercase: "",
	security_settings_uppercase: "",
	security_settings_numbers: "",
	security_settings_symbols: "",
	security_settings_length: "",
	security_settings_expiry: "",
	dormant_account_expiry: "",
	security_settings_login_attempt: "",
	security_settings_history_counts: "",
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

// Function to validate the form fields
const validateForm = () => {
	// Clear previous validation alerts
	alerts.security_settings_length = "";
	alerts.security_settings_expiry = "";
	alerts.dormant_account_expiry = "";
	alerts.security_settings_login_attempt = "";
	alerts.security_settings_history_counts = "";

	let isValid = true;

	// Validate security_settings_length field
	if (
		security_settings_length.value &&
		(isNaN(security_settings_length.value) || security_settings_length.value < 8)
	) {
		alerts.security_settings_length =
			"Password length should be a number greater than or equal to 8.";
		isValid = false;
	}

	// Validate security_settings_expiry field
	if (
		security_settings_expiry.value &&
		(isNaN(security_settings_expiry.value) || security_settings_expiry.value <= 0)
	) {
		alerts.security_settings_expiry = "Password expiry should be a positive number.";
		isValid = false;
	}

	// Validate dormant_account_expiry field
	if (
		dormant_account_expiry.value &&
		(isNaN(dormant_account_expiry.value) || dormant_account_expiry.value <= 0)
	) {
		alerts.dormant_account_expiry = "Account expiry should be a positive number.";
		isValid = false;
	}

	// Validate security_settings_login_attempt field
	if (
		security_settings_login_attempt.value &&
		(isNaN(security_settings_login_attempt.value) ||
			security_settings_login_attempt.value <= 0)
	) {
		alerts.security_settings_login_attempt =
			"Login attempts should be a positive number.";
		isValid = false;
	}

	// Validate security_settings_history_counts field
	if (
		security_settings_history_counts.value &&
		(isNaN(security_settings_history_counts.value) ||
			security_settings_history_counts.value < 1)
	) {
		alerts.security_settings_history_counts =
			"History count should be a number greater than or equal to 1.";
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

		// Ensure all data, including security_settings_2fa, is passed as a string
		const response = await axios.post(
			"/securitysettingsstore",
			{
				security_settings_2fa: String(security_settings_2fa.value), // Ensure 2fa is a string
				security_settings_lowercase: security_settings_lowercase.value,
				security_settings_uppercase: security_settings_uppercase.value,
				security_settings_numbers: security_settings_numbers.value,
				security_settings_symbols: security_settings_symbols.value,
				security_settings_length: security_settings_length.value,
				security_settings_expiry: security_settings_expiry.value,
				dormant_account_expiry: dormant_account_expiry.value,
				security_settings_login_attempt: security_settings_login_attempt.value,
				security_settings_history_counts: security_settings_history_counts.value,
			},
			{
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			}
		);

		// Check if the security settings was successfully saved
		if (response.data.success) {
			alerts.success = "Security settings saved successfully!";
			fetchSecuritySettings(); // Fetch updated security settings after saving
		} else {
			alerts.error =
				response.data.message ||
				"Failed to save security settings. Please try again.";
		}
	} catch (error) {
		handleError(error); // Handle error using centralized error handler
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
	}
};

// Fetch existing security settings settings when the component is mounted
const fetchSecuritySettings = async () => {
	isLoading.value = true; // Set loading state to true
	try {
		const token = getToken(); // Retrieve the token

		// Send GET request to fetch existing security settings settings
		const response = await axios.get("/securitysettingslist", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
		});

		// Check if the response is successful and contains the security settings data
		if (response.data.success && response.data.data) {
			const SecuritySettings = response.data.data; // Access the correct data from the response

			// Populate form fields with the fetched data
			security_settings_2fa.value = SecuritySettings.security_settings_2fa;
			security_settings_lowercase.value = SecuritySettings.security_settings_lowercase;
			security_settings_uppercase.value = SecuritySettings.security_settings_uppercase;
			security_settings_numbers.value = SecuritySettings.security_settings_numbers;
			security_settings_symbols.value = SecuritySettings.security_settings_symbols;
			security_settings_length.value = SecuritySettings.security_settings_length;
			security_settings_expiry.value = SecuritySettings.security_settings_expiry;
			dormant_account_expiry.value = SecuritySettings.dormant_account_expiry;
			security_settings_login_attempt.value = SecuritySettings.security_settings_login_attempt;
			security_settings_history_counts.value = SecuritySettings.security_settings_history_counts;
		} else {
			// If the response doesn't contain valid data, show an error
			alerts.error = response.data.message || "Failed to fetch security settings.";
		}
	} catch (error) {
		console.error("Axios error:", error); // Log the error to the console for debugging
		handleError(error); // Handle error using centralized error handler
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
	}
};

// Fetch security settings settings when the component is mounted
onMounted(() => {
	fetchSecuritySettings(); // Fetch initial security settings settings from the API
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
					Security Settings
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
							<h5 class="card-title">Configure Security Settings</h5>
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
								<div class="col-xxl-12">
									<div class="bg-light bg-opacity-1 p-2 mb-3 fw-bold">
										Authentication
									</div>
								</div>
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">2FA</label>
										<div>
											<div class="form-check form-check-inline">
												<input
													v-model="security_settings_2fa"
													class="form-check-input"
													type="radio"
													name="security_settings_2fa"
													value="No"
												/>
												<label class="form-check-label">No</label>
											</div>
											<div class="form-check form-check-inline">
												<input
													v-model="security_settings_2fa"
													class="form-check-input"
													type="radio"
													name="security_settings_2fa"
													value="Yes"
												/>
												<label class="form-check-label"
													>Yes</label
												>
											</div>
										</div>
										<div
											v-if="alerts.security_settings_2fa"
											class="text-danger mt-2"
										>
											{{ alerts.security_settings_2fa }}
										</div>
									</div>
								</div>
							</div>
							<!-- Row start -->
							<div class="row gx-3">
								<hr />
								<div class="col-xxl-12">
									<div class="bg-light bg-opacity-1 p-2 mb-3 fw-bold">
										Password Strength
									</div>
								</div>
								<div class="col-lg-3 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label"
											>Lower case (abcd)</label
										>
										<div>
											<div class="form-check form-check-inline">
												<input
													v-model="security_settings_lowercase"
													class="form-check-input"
													type="radio"
													name="security_settings_lowercase"
													value="No"
												/>
												<label class="form-check-label">No</label>
											</div>
											<div class="form-check form-check-inline">
												<input
													v-model="security_settings_lowercase"
													class="form-check-input"
													type="radio"
													name="security_settings_lowercase"
													value="Yes"
												/>
												<label class="form-check-label"
													>Yes</label
												>
											</div>
										</div>
										<div
											v-if="alerts.security_settings_lowercase"
											class="text-danger mt-2"
										>
											{{ alerts.security_settings_lowercase }}
										</div>
									</div>
								</div>
								<div class="col-lg-3 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label"
											>Upper case (ABCD)</label
										>
										<div>
											<div class="form-check form-check-inline">
												<input
													v-model="security_settings_uppercase"
													class="form-check-input"
													type="radio"
													name="security_settings_uppercase"
													value="No"
												/>
												<label class="form-check-label">No</label>
											</div>
											<div class="form-check form-check-inline">
												<input
													v-model="security_settings_uppercase"
													class="form-check-input"
													type="radio"
													name="security_settings_uppercase"
													value="Yes"
												/>
												<label class="form-check-label"
													>Yes</label
												>
											</div>
										</div>
										<div
											v-if="alerts.security_settings_uppercase"
											class="text-danger mt-2"
										>
											{{ alerts.security_settings_uppercase }}
										</div>
									</div>
								</div>
								<div class="col-lg-3 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Numbers (123)</label>
										<div>
											<div class="form-check form-check-inline">
												<input
													v-model="security_settings_numbers"
													class="form-check-input"
													type="radio"
													name="security_settings_numbers"
													value="No"
												/>
												<label class="form-check-label">No</label>
											</div>
											<div class="form-check form-check-inline">
												<input
													v-model="security_settings_numbers"
													class="form-check-input"
													type="radio"
													name="security_settings_numbers"
													value="Yes"
												/>
												<label class="form-check-label"
													>Yes</label
												>
											</div>
										</div>
										<div
											v-if="alerts.security_settings_numbers"
											class="text-danger mt-2"
										>
											{{ alerts.security_settings_numbers }}
										</div>
									</div>
								</div>
								<div class="col-lg-3 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Symbols (@#$)</label>
										<div>
											<div class="form-check form-check-inline">
												<input
													v-model="security_settings_symbols"
													class="form-check-input"
													type="radio"
													name="security_settings_symbols"
													value="No"
												/>
												<label class="form-check-label">No</label>
											</div>
											<div class="form-check form-check-inline">
												<input
													v-model="security_settings_symbols"
													class="form-check-input"
													type="radio"
													name="security_settings_symbols"
													value="Yes"
												/>
												<label class="form-check-label"
													>Yes</label
												>
											</div>
										</div>
										<div
											v-if="alerts.security_settings_symbols"
											class="text-danger mt-2"
										>
											{{ alerts.security_settings_symbols }}
										</div>
									</div>
								</div>
							</div>
							<!-- Row start -->
							<div class="row gx-3">
								<hr />
								<div class="col-xxl-12">
									<div class="bg-light bg-opacity-1 p-2 mb-3 fw-bold">
										Other settings
									</div>
								</div>
								<div class="col-lg-2 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label"
											>Password Length (min 3)</label
										>
										<input
											v-model="security_settings_length"
											type="text"
											class="form-control"
											placeholder="Enter Password Length"
											name="security_settings_length"
										/>
										<div
											v-if="alerts.security_settings_length"
											class="text-danger mt-2"
										>
											{{ alerts.security_settings_length }}
										</div>
									</div>
								</div>
								<div class="col-lg-2 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label"
											>Password Expiry (Months)</label
										>
										<input
											v-model="security_settings_expiry"
											type="number"
											class="form-control"
											placeholder="Enter Password Expiry"
											name="security_settings_expiry"
										/>
										<div
											v-if="alerts.security_settings_expiry"
											class="text-danger mt-2"
										>
											{{ alerts.security_settings_expiry }}
										</div>
									</div>
								</div>
								<div class="col-lg-2 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label"
											>Account Expiry (Days)</label
										>
										<input
											v-model="dormant_account_expiry"
											type="number"
											class="form-control"
											placeholder="Enter Account Expiry"
											name="dormant_account_expiry"
										/>
										<div
											v-if="alerts.security_settings_expiry"
											class="text-danger mt-2"
										>
											{{ alerts.dormant_account_expiry }}
										</div>
									</div>
								</div>
								<div class="col-lg-2 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Login Attempts</label>
										<input
											v-model="security_settings_login_attempt"
											type="number"
											class="form-control"
											placeholder="Enter Login Attempts"
											name="security_settings_login_attempt"
										/>
										<div
											v-if="alerts.security_settings_login_attempt"
											class="text-danger mt-2"
										>
											{{ alerts.security_settings_login_attempt }}
										</div>
									</div>
								</div>
								<div class="col-lg-2 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">History Counts</label>
										<input
											v-model="security_settings_history_counts"
											type="number"
											class="form-control"
											placeholder="Enter History Counts"
											name="security_settings_history_counts"
										/>
										<div
											v-if="alerts.security_settings_login_attempt"
											class="text-danger mt-2"
										>
											{{ alerts.security_settings_history_counts }}
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
