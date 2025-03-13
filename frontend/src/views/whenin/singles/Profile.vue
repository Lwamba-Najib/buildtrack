<script setup>
import { computed, onMounted, ref, reactive, watch } from 'vue';
import axios from '@/axios'; // Ensure axios is properly configured
import { RouterLink, useRouter} from 'vue-router';
import { useStore } from 'vuex';
import { useCustomUtils } from '@/utils/customUtils';



useCustomUtils();
// Access Vuex store
const store = useStore();
// Fetch public countries
 
// Reactive variable to store roles fetched from API
const roles = ref([]);
// Reactive variable to store roles fetched from API
const clients = ref([]);

// Initialize reactive variables for user update fields, alerts, and loading state
const id = ref("");
const name = ref("");
const gender = ref("");
const nin = ref("");
const country = ref("");
const code = ref("");
const phone_number = ref("");
const email = ref("");
const role_id = ref("");
const client_id = ref("");
const user_type = ref("");
const alerts = reactive({
    success: "",
	error: "",
	id: "",
    name: "",
	email: "",
	country: "",
	code: "",
	phone_number: "",
	nin: "",
	gender: "",
	role_id: "",
	client_id: "",
	user_type: "",
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

// Function to validate the form fields
const validateForm = () => {
    // Clear previous validation alerts
    alerts.name = "";
	alerts.email = "";
	alerts.country = "";
	alerts.code = "";
	alerts.phone_number = "";
	alerts.nin = "";
	alerts.gender = "";
	alerts.role_id = "";
	alerts.client_id = "";
	alerts.user_type = "";

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

	// Validate country field
	if (!country.value) {
		alerts.country = "Country is required.";
		isValid = false;
	}

	// Validate phone number field
	if (!phone_number.value) {
		alerts.phone_number = "Phone number is required.";
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

	// Validate client field
	if (!client_id.value) {
		alerts.client_id = "Client is required.";
		isValid = false;
	}

	// Validate user type field
	if (!user_type.value) {
		alerts.user_type = "Account type is required.";
		isValid = false;
	}

	return isValid;
};

// Function to handle form submission (Update User)
const handleSubmit = async () => {
    alerts.success = '';
    alerts.error = '';

    // Validate form before submission
    if (!validateForm()) {
        return; // Stop submission if validation fails
    }

    isLoading.value = true; // Set loading state to true

    try {
        const token = getToken(); // Retrieve the token

        // Send a PUT request to the backend API with updated form data
        const response = await axios.put(`/userupdate/${id.value}`, {
            name: name.value,
            gender: gender.value,
            nin: nin.value,
            country: country.value,
            code: code.value,
            phone_number: phone_number.value,
            email: email.value,
            role_id: role_id.value,
            client_id: client_id.value,
            user_type: user_type.value,
        }, {
            headers: {
                Authorization: `Bearer ${token}` // Include the token in the Authorization header
            }
        });

        // Check if the user update was successful
        if (response.data.success) {
            alerts.success = 'Personal details updated successfully!';
        } else {
            alerts.error = response.data.message || 'Failed to personal details. Please try again.';
        }
    } catch (error) {
        handleError(error); // Handle error using centralized error handler
    } finally {
        isLoading.value = false; // Set loading state to false after request completes
    }
};

// Function to fetch the current user data
const getUserPersonalDetails = async () => {
    try {
        const token = getToken(); // Retrieve the token
        const response = await axios.get(`/userpersonaldetails/`, {
            headers: {
                Authorization: `Bearer ${token}`
            }
        });

        if (response.status === 200) {
            const user = response.data.data; // Adjust if necessary based on the actual response structure
			id.value = user.id;
            name.value = user.name;
            gender.value = user.gender;
            nin.value = user.nin;
            country.value = user.country;
            code.value = user.code;
            phone_number.value = user.phone_number;
            email.value = user.email;
            role_id.value = user.role_id;
            client_id.value = user.client_id;
            user_type.value = user.user_type;
        } else {
            console.error('Error fetching personal details:', response.statusText);
        }
    } catch (error) {
        handleError(error); // Handle error using centralized error handler
    }
};

// Function to fetch roles from the API
const getRoles = async () => {
    try {
        const token = getToken(); // Retrieve the token

        // Fetch roles from the API
        const response = await axios.get('/userroles', {
            headers: {
                Authorization: `Bearer ${token}` // Include the token in the Authorization header
            }
        });

        if (response.status === 200) {
            roles.value = response.data; // Store the fetched roles
        } else {
            console.error('Error fetching roles:', response.statusText);
        }
    } catch (error) {
        handleError(error); // Handle error using centralized error handler
    }
};
// Function to fetch clients from the API
const getClients = async () => {
	try {
		const token = getToken(); // Retrieve the token

		// Fetch clients from the API
		const response = await axios.get("/getclients", {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
		});

		if (response.status === 200) {
			clients.value = response.data; // Store the fetched clients
		} else {
			console.error("Error fetching clients:", response.statusText);
		}
	} catch (error) {
		handleError(error); // Handle error using centralized error handler
	}
};
// Computed property to filter account types based on the logged-in user type
const filteredAccountTypes = computed(() => {
	const userType = store.state.userType; // Assuming `userType` is stored in the Vuex state
	const userIndex = accounts.findIndex(account => account.user_type === userType);

	if (userIndex !== -1) {
		return accounts.slice(userIndex).map(account => account.user_type);
	}
	return [];
});
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
			case "country":
				country.value = newValue || ""; // Handle country field
				break;
			case "client":
				client_id.value = newValue || ""; // Handle client_id field
				break;
			case "user_type":
				user_type.value = newValue || ""; // Handle user_type field
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

// Watch the `country` field and auto-fill the code field
watch(country, (newCountry) => {
	const selectedCountry = publicCountries.value.find((c) => c.country === newCountry);
	if (selectedCountry) {
		code.value = selectedCountry.code; // Set code with country code
	} else {
		code.value = ""; // Clear code if no valid country is selected
	}
});
// Initial data fetch (fetch roles and user details)
onMounted(() => {
    getRoles(); // Fetch roles when the component is mounted
	getClients(); // Fetch clients when the component is mounted
    getUserPersonalDetails();
    initializeSelect2();
});
// Reinitialize Select2 and validate form on dependency changes
watch([country], () => {    
    initializeSelect2();
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
				<li class="breadcrumb-item text-secondary" aria-current="page">Profile</li>
			</ol>
			<!-- Breadcrumb end -->
		</div>
		<!-- App Hero header ends -->

		<!-- App body starts -->
		<div class="app-body">
			<!-- Row start -->
			<div class="row justify-content-center">
				<div class="col-xxl-12">
					<div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title">My Personal Details</h5>
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
                            <!-- Row start -->
                            <div class="row gx-3">
                                <!-- Name Field -->
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input v-model="name" @input="validateForm" @blur="validateForm" type="text" class="form-control"
                                            placeholder="Enter fullname" />
                                        <!-- Display validation message for name -->
                                        <div v-if="alerts.name" class="text-danger mt-2">{{ alerts.name }}</div>
                                    </div>
                                </div>

                                <!-- Gender Field -->
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Gender</label>
                                        <select v-model="gender" @change="validateForm" id="gender" class="form-select select" disabled>
                                            <option value="" disabled>Select gender</option>
                                            <option value="Female">Female</option>
                                            <option value="Male">Male</option>
                                        </select>
                                        <!-- Display validation message for gender -->
                                        <div v-if="alerts.gender" class="text-danger mt-2">{{ alerts.gender }}</div>
                                    </div>
                                </div>

                                <!-- NIN Field -->
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">NIN</label>
                                        <input v-model="nin" @input="validateForm" @blur="validateForm" type="text" class="form-control" placeholder="Enter NIN"
                                            maxlength="14" readonly />
                                        <!-- Display validation message for NIN -->
                                        <div v-if="alerts.nin" class="text-danger mt-2">{{ alerts.nin }}</div>
                                    </div>
                                </div>

                                <!-- Country Field -->
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Country</label>
										<select
											v-model="country"
                                            @change="validateForm"
											id="country"
											class="form-select select" disabled
										>
											<option value="" disabled>
												Select country
											</option>
											<option
												v-for="c in publicCountries"
												:key="c.code"
												:value="c.country"
											>
												{{ c.country }}
											</option>
										</select>
										<!-- Display validation message for Country -->
										<div
											v-if="alerts.country"
											class="text-danger mt-2"
										>
											{{ alerts.country }}
										</div>
									</div>
								</div>
								
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="row">
										<!-- Code Number Field -->
										<div class="col-lg-3 col-sm-3 col-3">
											<div class="mb-3">
												<label class="form-label">Code</label>
												<input
													v-model="code"
													type="tel"
													class="form-control"
													placeholder="Code"
													disabled
												/>
												<!-- Display validation message for code -->
												<div
													v-if="alerts.code"
													class="text-danger mt-2"
												>
													{{ alerts.code }}
												</div>
											</div>
										</div>
										<!-- Phone Number Field -->
										<div class="col-lg-9 col-sm-9 col-9">
											<div class="mb-3">
												<label class="form-label">Phone</label>
												<input
													v-model="phone_number" @input="validateForm" @blur="validateForm"
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
									</div>
								</div>

                                <!-- Email Field -->
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input v-model="email" @input="validateForm" @blur="validateForm" type="email" class="form-control"
                                            placeholder="Enter email address" />
                                        <!-- Display validation message for email -->
                                        <div v-if="alerts.email" class="text-danger mt-2">{{ alerts.email }}</div>
                                    </div>
                                </div>

                                <!-- Role Field -->
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <select v-model="role_id" @change="validateForm" id="role" class="form-select select" disabled>
                                            <option value="" disabled>Select role</option>
                                            <option v-for="role in roles" :key="role.id" :value="role.id">
                                                {{ role.name + ' [' + role.business_name + ']' }}
                                            </option>
                                        </select>
                                        <!-- Display validation message for role -->
                                        <div v-if="alerts.role_id" class="text-danger mt-2">{{ alerts.role_id }}</div>
                                    </div>
                                </div>
                                <!-- Client Field -->
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Client</label>
										<select
											v-model="client_id"
                                            @change="validateForm"
											id="client"
											class="form-select select" disabled
										>
											<option value="" disabled>Select client</option>
											<option
												v-for="client in clients"
												:key="client.id"
												:value="client.id"
											>
												{{ client.business_name }}
											</option>
										</select>
										<!-- Display validation message for client -->
										<div
											v-if="alerts.client_id"
											class="text-danger mt-2"
										>
											{{ alerts.client_id }}
										</div>
									</div>
								</div>

								<!-- User Type Field -->
								<div class="col-lg-4 col-sm-4 col-12">
									<div class="mb-3">
										<label class="form-label">Account type</label>
										<select
											v-model="user_type"
											@change="validateForm"
											id="user_type"
											class="form-select select" disabled
										>
											<option value="">
												Select account type
											</option>
											<option
												v-for="type in filteredAccountTypes"
												:key="type"
												:value="type"
											>
												{{ type }}
											</option>
										</select>
										<!-- Display validation message for user_type -->
										<div
											v-if="alerts.user_type"
											class="text-danger mt-2"
										>
											{{ alerts.user_type }}
										</div>
									</div>
								</div>
                            </div>
                            <!-- Row end -->
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
