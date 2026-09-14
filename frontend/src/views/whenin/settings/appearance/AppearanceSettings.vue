<script setup>
// Import necessary modules and functions
import { onMounted, ref, reactive, watch } from "vue";
import axios from "@/axios"; // Ensure axios is properly configured
const backendBaseUrl = import.meta.env.VITE_API_URL || window.location.origin; // Import the backend base URL from config
import { useRouter } from "vue-router";
import { useCustomUtils } from "@/utils/customUtils";

// Execute any required custom utility functions
useCustomUtils();

// Initialize reactive references for form fields, alerts, and loading state
const name = ref(""); // Stores application name input
const logo = ref(null); // Stores logo file input
const favicon = ref(null); // Stores favicon file input
const wallpaper = ref(null); // Stores wallpaper file input
const logoPreview = ref(null); // Stores the logo preview URL
const faviconPreview = ref(null); // Stores the favicon preview URL
const wallpaperPreview = ref(null); // Stores the wallpaper preview URL

const alerts = reactive({
	success: "",
	error: "",
	name: "",
	logo: "",
	favicon: "",
	wallpaper: "",
});
const isLoading = ref(false); // Tracks loading state for button disable and loading spinner
const router = useRouter();

// Helper function to retrieve authentication token
const getToken = () => {
	const token = localStorage.getItem("token");
	if (!token) throw new Error("No token found"); // Throws error if no token is found
	return token;
};

// Centralized error handling function to display API errors
const handleError = (error, alertField = "error") => {
	alerts[alertField] =
		error.response?.data?.message || "An error occurred. Please try again later.";
	console.error("API Error:", error);
};

// Function to validate the form fields before submission
const validateForm = () => {
	// Clear previous validation alerts
	alerts.name = "";
	alerts.logo = "";
	alerts.favicon = "";
	alerts.wallpaper = "";

	let isValid = true;

	// Validate the name field
	if (!name.value) {
		alerts.name = "Name is required.";
		isValid = false;
	}

	return isValid; // Return form validity status
};
// Helper function to validate image files
const validateImageFile = (file) => {
	const validTypes = ["image/png", "image/jpg", "image/jpeg"];
	if (!file || !validTypes.includes(file.type)) {
		return false;
	}
	return true;
};
// Handle file change for the logo
const handleLogoChange = (event) => {
	const file = event.target.files[0];
	if (validateImageFile(file)) {
		logo.value = file;
		logoPreview.value = URL.createObjectURL(file);
		alerts.logo = "";
	} else {
		alerts.logo = "Invalid file type. Only PNG and JPG are allowed.";
		logo.value = null;
		logoPreview.value = null;
	}
};

// Handle file change for the favicon
const handleFaviconChange = (event) => {
	const file = event.target.files[0];
	if (validateImageFile(file)) {
		favicon.value = file;
		faviconPreview.value = URL.createObjectURL(file);
		alerts.favicon = "";
	} else {
		alerts.favicon = "Invalid file type. Only PNG and JPG are allowed.";
		favicon.value = null;
		faviconPreview.value = null;
	}
};

// Handle file change for the wallpaper
const handleWallpaperChange = (event) => {
    const file = event.target.files[0];
    if (validateImageFile(file)) {
        wallpaper.value = file;
        wallpaperPreview.value = URL.createObjectURL(file);
        alerts.wallpaper = "";
    } else {
        alerts.wallpaper = "Invalid file type. Only PNG and JPG are allowed.";
        wallpaper.value = null;
        wallpaperPreview.value = null;
    }
};

// Function to handle form submission to the backend
const handleSubmit = async () => {
	alerts.success = "";
	alerts.error = "";

	// Validate form fields before submitting
	if (!validateForm()) {
		return; // Stop submission if validation fails
	}

	isLoading.value = true; // Set loading state to true during the request

	try {
		const token = getToken(); // Retrieve authentication token
		// Prepare form data for file upload
		const formData = new FormData();
		formData.append("name", name.value);
		formData.append("logo", logo.value);
		formData.append("favicon", favicon.value);
		formData.append("wallpaper", wallpaper.value);

		// Send a POST request to the API with form data and headers
		const response = await axios.post("/appearancesettingsstore", formData, {
			headers: {
				Authorization: `Bearer ${token}`, // Include token in the Authorization header
				"Content-Type": "multipart/form-data", // Ensure content-type is set for file uploads
			},
		});

		// Handle the API response for a successful submission
		if (response.data.success) {
			alerts.success = "Settings saved successfully!";
			await fetchSettings(); // Refresh data after saving
		} else {
			alerts.error = response.data.message || "Failed to save settings. Please try again.";
		}
	} catch (error) {
		handleError(error); // Use centralized error handler for API errors
	} finally {
		isLoading.value = false; // Reset loading state
	}
};

// Function to fetch and populate saved data
const fetchSettings = async () => {
	isLoading.value = true; // Set loading state to true
	try {
		const token = getToken();
		const response = await axios.get("/appearancesettingslist", {
			headers: {
				Authorization: `Bearer ${token}`,
			},
		});

		// Adjusted code to check if `data` exists and is an object, not an array
		if (response.data && response.data.success && response.data.data) {
			const settingsData = response.data.data;

			// Populate fields with saved data
			name.value = settingsData.name || "";

			// Set logo preview, defaulting to a noimage placeholder if no logo exists
			if (settingsData.logo) {
				logoPreview.value = `${backendBaseUrl}/storage/${settingsData.logo}`;
			} else {
				logoPreview.value = "/assets/images/noimage.jpg"; // Set default image if no logo
			}

			// Set favicon preview, defaulting to a noimage placeholder if no favicon exists
			if (settingsData.favicon) {
				faviconPreview.value = `${backendBaseUrl}/storage/${settingsData.favicon}`;
			} else {
				faviconPreview.value = "/assets/images/noimage.jpg"; // Set default image if no favicon
			}

			// Set wallpaper preview, defaulting to a noimage placeholder if no wallpaper exists
            if (settingsData.wallpaper) {
                wallpaperPreview.value = `${backendBaseUrl}/storage/${settingsData.wallpaper}`;
            } else {
                wallpaperPreview.value = "/assets/images/noimage.jpg"; // Set default image if no wallpaper
            }
		} else {
			console.error("Expected settings not found in response data.");
			alerts.error = "Failed to load settings. Please try again later.";
		}
	} catch (error) {
		handleError(error); // Use centralized error handler for API errors
	} finally {
		isLoading.value = false; // Set loading state to false after request completes
	}
};

// Initial data fetch
onMounted(() => {
	fetchSettings();
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
					Appearance Settings
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
							<h5 class="card-title">Create Appearance Settings</h5>
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
										<label class="form-label">Application Name</label>
										<input
											v-model="name"
											type="text"
											class="form-control"
											placeholder="Enter application name"
										/>
										<!-- Display validation message for sender name -->
										<div v-if="alerts.name" class="text-danger mt-2">
											{{ alerts.name }}
										</div>
									</div>
								</div>
								<!-- Logo Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Logo</label>
										<input
											type="file"
											class="form-control"
											@change="handleLogoChange"
											placeholder="Select Logo"
										/>
										<!-- Display validation message for Logo -->
										<div v-if="alerts.logo" class="text-danger mt-2">
											{{ alerts.logo }}
										</div>
										<!-- Image Preview with Centering -->
										<div class="img-preview-container mt-4">
											<img
												v-if="logoPreview"
												:src="logoPreview"
												alt="Logo Preview"
												class="img-fluid img-preview"
											/>
										</div>
									</div>
								</div>
								<!-- Favicon Field -->
								<div class="col-lg-6 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Favicon</label>
										<input
											type="file"
											class="form-control"
											@change="handleFaviconChange"
											placeholder="Select Favicon"
										/>
										<!-- Display validation message for Favicon -->
										<div
											v-if="alerts.favicon"
											class="text-danger mt-2"
										>
											{{ alerts.favicon }}
										</div>
										<!-- Image Preview with Centering -->
										<div class="img-preview-container mt-4">
											<img
												v-if="faviconPreview"
												:src="faviconPreview"
												alt="Favicon Preview"
												class="img-fluid img-preview"
											/>
										</div>
									</div>
								</div>
                                <!-- Wallpaper Field -->
                                <div class="col-lg-12 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Wallpaper</label>
                                        <input
                                            type="file"
                                            class="form-control"
                                            @change="handleWallpaperChange"
                                            placeholder="Select Wallpaper"
                                        />
                                        <!-- Display validation message for Wallpaper -->
                                        <div
                                            v-if="alerts.wallpaper"
                                            class="text-danger mt-2"
                                        >
                                            {{ alerts.wallpaper }}
                                        </div>
                                        <!-- Image Preview with Centering -->
                                        <div class="img-preview-container-wp mt-4">
                                            <img
                                                v-if="wallpaperPreview"
                                                :src="wallpaperPreview"
                                                alt="wallpaper Preview"
                                                class="img-fluid img-preview-wp"
                                            />
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
