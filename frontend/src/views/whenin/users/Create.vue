<script setup>
import { computed, onMounted, ref, reactive, watch } from "vue";
// Ensure axios is properly configured
import axios from "@/axios"; 
import { useRouter } from "vue-router";
import { useStore } from 'vuex';
import { useCustomUtils } from "@/utils/customUtils";

useCustomUtils();
 
// Reactive variable to store roles fetched from API
const roles = ref([]);

// Initialize reactive variables for user creation fields, alerts, and loading state
const name = ref("");
const gender = ref("");
const nin = ref("");
const phone_number = ref("");
const email = ref("");
const role_id = ref("");
const environment = ref("");
const shouldRedirect = ref(true); // New reactive variable for redirection control
const alerts = reactive({
	success: "",
	error: "",
	name: "",
	email: "",
	phone_number: "",
	nin: "",
	gender: "",
	role_id: "",
	environment: "",
});
const isLoading = ref(false);
const router = useRouter();
// Access Vuex store
const store = useStore();

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
// Function to validate the form fields
const validateForm = () => {
	// Clear previous validation alerts
	alerts.name = "";
	alerts.email = "";
	alerts.phone_number = "";
	alerts.nin = "";
	alerts.gender = "";
	alerts.role_id = "";
	alerts.environment = "";

	let isValid = true;

	// Validate name field
	if (!name.value) {
		alerts.name = "Name is required.";
		isValid = false;
	}

	// Validate email field
	if (!email.value) {
		alerts.email = "Email is required.";
		isValid = false;
	} else if (!validateEmail(email.value)) {
		alerts.email = "Invalid email format.";
		isValid = false;
	}

	// Validate phone number field
	if (!phone_number.value) {
		alerts.phone_number = "Phone number is required.";
		isValid = false;
	} else if (!validatePhoneNumber(phone_number.value)) {
		alerts.phone_number = "Invalid phone number format. Please enter only digits.";
		isValid = false;
	}
	
	// Validate NIN field
	if (!nin.value) {
		alerts.nin = "NIN is required.";
		isValid = false;
	}

	// Validate gender field
	if (!gender.value) {
		alerts.gender = "Gender is required.";
		isValid = false;
	}

	// Validate role field
	if (!role_id.value) {
		alerts.role_id = "Role is required.";
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
		const response = await axios.post("/userstore",
			{
				name: name.value,
				gender: gender.value,
				nin: nin.value,
				phone_number: phone_number.value,
				email: email.value,
				role_id: role_id.value,
				environment: environment.value,
			},
			{
				headers: {
					Authorization: `Bearer ${token}`, // Include the token in the Authorization header
				},
			}
		);

		// Check if the user creation was successful
		if (response.data.success) {
			alerts.success = "User created successfully!";
			if (shouldRedirect.value) {
				setTimeout(() => router.push("/userlist"), 1000); // Redirect after 1 second
			}
		} else {
			alerts.error =response.data.message || "Failed to create user. Please try again.";
		}
	} catch (error) {
		handleError(error); // Handle error using centralized error handle
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
	}
};

// Function to fetch roles from the API
const getRoles = async () => {
	try {
		// Retrieve the token from local storage
		const token = localStorage.getItem("token");

		// Ensure the token exists before making the request
		if (!token) {
			throw new Error("No token found");
		}

		// Fetch roles from the API
		const response = await axios.get("/userroles", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
		});

		if (response.status === 200) {
			roles.value = response.data; // Store the fetched roles
		} else {
			console.error("Error fetching roles:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching roles:", error);
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
			case "role":
				role_id.value = newValue || ""; // Use empty string if cleared
				break;
			case "gender":
				gender.value = newValue || ""; // Handle gender field
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
// Watch for changes in `phone_number`
watch(phone_number, (newVal, oldVal) => {
	if (!newVal) {
		phone_number.value = "0"; // Default to '0' if empty
	} else if (!newVal.startsWith("0")) {
		phone_number.value = "0" + newVal; // Prepend '0' if not present
	}
});
// Watch the gender field for changes
watch([gender,role_id,environment], (newValue) => {
	// Whenever the gender field changes, trigger the form validation
	validateForm();
});
// Initial data fetch
// Initial data fetch
onMounted(() => {
	loadRedirectPreference(); // Load the saved redirect preference
	getRoles(); // Fetch roles when the component is mounted
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
					<RouterLink to="/userlist" class="text-decoration-none"
						>User</RouterLink
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
							<h5 class="card-title">Create User</h5>
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
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Name</label>
										<input
											v-model="name"
											@input="validateForm"
          									@blur="validateForm"
											type="text"
											class="form-control"
											placeholder="Enter fullname"
										/>
										<!-- Display validation message for name -->
										<div v-if="alerts.name" class="text-danger mt-2">
											{{ alerts.name }}
										</div>
									</div>
								</div>

								<!-- Gender Field -->
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Gender</label>
										<select
											v-model="gender"
											@change="validateForm"
											id="gender"
											class="form-select select"
										>
											<option value="">
												Select gender
											</option>
											<option value="Female">Female</option>
											<option value="Male">Male</option>
										</select>
										<!-- Display validation message for gender -->
										<div
											v-if="alerts.gender"
											class="text-danger mt-2"
										>
											{{ alerts.gender }}
										</div>
									</div>
								</div>

								<!-- NIN Field -->
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">NIN</label>
										<input
											v-model="nin"
											@input="validateForm"
          									@blur="validateForm"
											type="text"
											class="form-control"
											placeholder="Enter NIN"
											maxlength="14"
										/>
										<!-- Display validation message for NIN -->
										<div v-if="alerts.nin" class="text-danger mt-2">
											{{ alerts.nin }}
										</div>
									</div>
								</div>
								<!-- Phone Number Field -->
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Phone</label>
										<input
											v-model="phone_number"
											@input="validateForm"
											@blur="validateForm"
											type="tel"
											class="form-control"
											placeholder="Enter phone number"
											maxlength="18"
										/>
										<!-- Display validation message for phone number -->
										<div
											v-if="alerts.phone_number"
											class="text-danger mt-2"
										>
											{{ alerts.phone_number }}
										</div>
									</div>
								</div>					

								<!-- Email Field -->
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Email</label>
										<input
											v-model="email"
											@input="validateForm"
          									@blur="validateForm"
											type="email"
											class="form-control"
											placeholder="Enter email address"
										/>
										<!-- Display validation message for email -->
										<div v-if="alerts.email" class="text-danger mt-2">
											{{ alerts.email }}
										</div>
									</div>
								</div>

								<!-- Role Field -->
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Role</label>
										<select
											v-model="role_id"
											@change="validateForm"
											id="role"
											class="form-select select"
										>
											<option value="" disabled>Select role</option>
											<option
												v-for="role in roles"
												:key="role.id"
												:value="role.id"
											>
												{{ role.name }}
											</option>
										</select>
										<!-- Display validation message for role -->
										<div
											v-if="alerts.role_id"
											class="text-danger mt-2"
										>
											{{ alerts.role_id }}
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
