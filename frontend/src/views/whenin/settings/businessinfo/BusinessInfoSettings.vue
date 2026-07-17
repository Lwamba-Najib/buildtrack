<script setup>
import { onMounted, ref, reactive, watch } from "vue";
import axios from "@/axios"; // Ensure axios is properly configured
import { useRouter, RouterLink } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";
useCustomUtils();

// Initialize reactive variables for form fields, alerts, and loading state
const business_name = ref("");
const business_reg_number = ref("");
const business_tin = ref("");
const business_slogan = ref("");
const business_address = ref("");
const business_email = ref("");
const business_contact = ref("");
const business_website = ref("");
const business_legal_disclaimer = ref("");
const alerts = reactive({
	success: "",
	error: "",
	business_name: "",
	business_reg_number: "",
	business_tin: "",
	business_slogan: "",
	business_address: "",
	business_email: "",
	business_contact: "",
	business_website: "",
	business_legal_disclaimer: "",
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
// Function to validate website format
const validateWebsite = (website) => {
    const websitePattern = /^(https?:\/\/)?([\w-]+(\.[\w-]+)+)(\/[\w-]*)*$/;
    return websitePattern.test(website);
};

// Fetch existing business info settings when the component is mounted
const fetchBusinessInfoSettings = async () => {
	isLoading.value = true; // Set loading state to true
	try {
		const token = getToken(); // Retrieve the token

		// Send GET request to fetch existing business info settings
		const response = await axios.get("/businessinfosettingslist", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
		});

		// Populate form fields with the fetched data
		if (response.data.success && response.data.data) {
			const businessInfoSettings = response.data.data;
			business_name.value = businessInfoSettings.business_name;
			business_reg_number.value = businessInfoSettings.business_reg_number;
			business_tin.value = businessInfoSettings.business_tin;
			business_slogan.value = businessInfoSettings.business_slogan;
			business_address.value = businessInfoSettings.business_address;
			business_email.value = businessInfoSettings.business_email;
			business_contact.value = businessInfoSettings.business_contact;
			business_website.value = businessInfoSettings.business_website;
			business_legal_disclaimer.value = businessInfoSettings.business_legal_disclaimer;
		} else {
			alerts.error = response.data.message || "Failed to fetch business info settings.";
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

	// Validate business_name field
	if (!business_name.value) {
		alerts.business_name = "business name is required.";
		isValid = false;
	}

	// Validate business_reg_number field
	if (!business_reg_number.value) {
		alerts.business_reg_number = "business regbusiness registration number is required.";
		isValid = false;
	}

	// Validate business_tin field
	if (!business_tin.value) {
		alerts.business_tin = "business tax identification number is required.";
		isValid = false;
	}

	// Validate business_name field
	if (!business_address.value) {
		alerts.business_address = "business address is required.";
		isValid = false;
	}

	// Validate email field
	if (!business_email.value) {
		alerts.business_email = "business email required.";
		isValid = false;
	} else if (!validateEmail(business_email.value)) {
		alerts.business_email = "Invalid email format.";
		isValid = false;
	}

	// Validate business_contact field
	if (!business_contact.value) {
		alerts.business_contact = "business contact required.";
		isValid = false;
	} else if (!validatePhoneNumber(business_contact.value)) {
		alerts.business_contact = "Invalid business contact format. Please enter only digits.";
		isValid = false;
	}

	// Validate business_website field
	if (business_website.value && !validateWebsite(business_website.value)) {
		alerts.business_website = "Invalid website format. Please enter a valid URL.";
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
			"/businessinfosettingsstore",
			{
				business_name: business_name.value,
				business_reg_number: business_reg_number.value,
				business_tin: business_tin.value,
				business_slogan: business_slogan.value,
				business_address: business_address.value,
				business_email: business_email.value,
				business_contact: business_contact.value,
				business_website: business_website.value,
				business_legal_disclaimer: business_legal_disclaimer.value,
			},
			{
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			}
		);

		// Check if the business info settings were successfully saved
		if (response.data.success) {
			alerts.success = "Business info settings saved successfully!";
			setTimeout(() => {
				fetchBusinessInfoSettings(); // Fetch updated business info settings after saving
			}, 1000); // Wait 1 second before fetching updated data
		} else {
			alerts.error = response.data.message || "Failed to save business info settings. Please try again.";
		}
	} catch (error) {
		handleError(error); // Handle error using centralized error handler
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
	}
};

// Fetch email settings when the component is mounted
onMounted(() => {
	fetchBusinessInfoSettings(); // Fetch initial email settings from the API
});
// Watch for changes in `business_contact`
watch(business_contact, (newVal, oldVal) => {
	if (!newVal) {
		business_contact.value = "0"; // Default to '0' if empty
	} else if (!newVal.startsWith("0")) {
		business_contact.value = "0" + newVal; // Prepend '0' if not present
	}
});
// Watch for changes in `business_website`
watch(business_website, (newVal, oldVal) => {
	if (!newVal) {
		business_website.value = "https://"; // Default to 'https://' if empty
	} else if (!newVal.startsWith("https://")) {
		business_website.value = "https://" + newVal; // Prepend 'https://' if not present
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
					<RouterLink to="/dashboard" class="text-decoration-none"
						>Home</RouterLink
					>
				</li>
				<li class="breadcrumb-item text-secondary" aria-current="page">
					Business Info Settings
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
							<h5 class="card-title">Create Business Info Settings</h5>
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
										<label class="form-label">Business Name</label>
										<input
											v-model="business_name"
											type="text"
											class="form-control"
											placeholder="Enter Business name"
										/>
										<!-- Display validation message for business name -->
										<div
											v-if="alerts.business_name"
											class="text-danger mt-2"
										>
											{{ alerts.business_name }}
										</div>
									</div>
								</div>								
								<!-- Registration Number Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Registration Number</label>
										<input
											v-model="business_reg_number"
											type="text"
											class="form-control"
											placeholder="Enter Registration Number"
										/>
										<!-- Display validation message for Registration Number -->
										<div
											v-if="alerts.business_reg_number"
											class="text-danger mt-2"
										>
											{{ alerts.business_reg_number }}
										</div>
									</div>
								</div>
								<!-- Tax Identification Number Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Tax Identification Number</label>
										<input
											v-model="business_tin"
											type="text"
											class="form-control"
											placeholder="Enter Tax Identification Number"
										/>
										<!-- Display validation message for Tax Identification Number -->
										<div
											v-if="alerts.business_tin"
											class="text-danger mt-2"
										>
											{{ alerts.business_tin }}
										</div>
									</div>
								</div>
								<!-- Slogan Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Slogan</label>
										<input
											v-model="business_slogan"
											type="text"
											class="form-control"
											placeholder="Enter Slogan"
										/>
										<!-- Display validation message for Slogan -->
										<div
											v-if="alerts.business_slogan"
											class="text-danger mt-2"
										>
											{{ alerts.business_slogan }}
										</div>
									</div>
								</div>
								<!-- Email Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Business Address</label>
										<input
											v-model="business_address"
											type="text"
											class="form-control"
											placeholder="Enter Business Address"
										/>
										<!-- Display validation message for business address -->
										<div
											v-if="alerts.business_address"
											class="text-danger mt-2"
										>
											{{ alerts.business_address }}
										</div>
									</div>
								</div>
								<!-- Email Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Business Email</label>
										<input
											v-model="business_email"
											type="email"
											class="form-control"
											placeholder="Enter Business email"
										/>
										<!-- Display validation message for business email -->
										<div
											v-if="alerts.business_email"
											class="text-danger mt-2"
										>
											{{ alerts.business_email }}
										</div>
									</div>
								</div>
								<!-- Business Contact Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Business Contact</label>
										<input
											v-model="business_contact"
											type="text"
											class="form-control"
											placeholder="Enter Business Contact"
										/>
										<!-- Display validation message for Business Contact -->
										<div
											v-if="alerts.business_contact"
											class="text-danger mt-2"
										>
											{{ alerts.business_contact }}
										</div>
									</div>
								</div>
								<!-- Website Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Website</label>
										<input
											v-model="business_website"
											type="text"
											class="form-control"
											placeholder="Enter Website"
										/>
										<!-- Display validation message for Website -->
										<div
											v-if="alerts.business_website"
											class="text-danger mt-2"
										>
											{{ alerts.business_website }}
										</div>
									</div>
								</div>
								<!-- Legal Disclaimer Field -->
								<div class="col-lg-12 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Legal Disclaimer</label>
										<textarea
											v-model="business_legal_disclaimer"
											type="text"
											class="form-control"
											placeholder="Enter Legal Disclaimer"
										></textarea>
										<!-- Display validation message for Legal Disclaimer -->
										<div
											v-if="alerts.business_legal_disclaimer"
											class="text-danger mt-2"
										>
											{{ alerts.business_legal_disclaimer }}
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
