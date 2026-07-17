<script setup>
import { ref, reactive} from "vue";
import { RouterLink } from "vue-router";
import axios, { fetchCsrfToken } from "@/axios"; // Ensure fetchCsrfToken is correctly exported

const props = defineProps({
	logoSrc: {
		type: String,
		required: true,
	}
});

// Initialize reactive variables
const email = ref("");
const cooldown = ref(0); // Cooldown timer in seconds
const alerts = reactive({
	success: "", // Success message
	error: "", // General error message
	email: "", // Email validation error message
});
const isLoading = ref(false); // Loading state
// Default logo
const logoSrc = ref("/assets/images/logo.png"); 

// Function to validate email format
const validateEmail = (email) => {
	const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return emailPattern.test(email);
};
// Function to validate the form fields
const validateForm = () => {
	alerts.email = ""; // Clear previous email error

	let isValid = true;

	// Validate email field
	if (!email.value) {
		alerts.email = "Email is required.";
		isValid = false;
	} else if (!validateEmail(email.value)) {
		alerts.email = "Invalid email format.";
		isValid = false;
	}

	return isValid; // Return true if form is valid, false otherwise
};

const handleSubmit = async (event) => {
	event.preventDefault(); // Prevent default form submission

	// Validate form before submission
	if (!validateForm()) {
		return; // Stop submission if validation fails
	}
    if (cooldown.value > 0) {
        alerts.error = `Please wait ${cooldown.value} seconds before reset password.`;
        return;
    }
    alerts.success = "";
    alerts.error = "";

	isLoading.value = true; // Set loading state to true

    try {
		// Fetch CSRF token before making the login request
        await fetchCsrfToken();
        // Make the request to reset Password
        const response = await axios.post("/forgotpassword", {
            email: email.value, // Pass the email to the backend
        });

        if (response.data.success) {
            alerts.success = "New password sent successfully!";
            cooldown.value = 60; // Set cooldown to 60 seconds
            startCooldownTimer();
        } else {
            alerts.error = response.data.message || "Failed to resend password.";
        }
    } catch (error) {
        alerts.error = error.response?.data?.message || "An error occurred. Please try again.";
    } finally {
        isLoading.value = false;
    }
};

const startCooldownTimer = () => {
    const timer = setInterval(() => {
        if (cooldown.value > 0) {
            cooldown.value--;
        } else {
            clearInterval(timer);
        }
    }, 1000);
};
</script>

<template>
	<section>
		<!-- Container start -->
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-xl-4 col-lg-5 col-sm-6 col-12">
					<form @submit="handleSubmit" class="my-5">
						<div class="border rounded-2 p-4 mt-5 container-login">
							<div class="login-form">
								<span class="mb-4 d-flex">
									<img
										:src="props.logoSrc"
										class="img-fluid login-logo"
										alt="Earth Admin Dashboard"
									/>
								</span>
								<h5 class="fw-light mb-5 lh-2">
									In order to access your account, please enter the
									email id you provided during the registration process.
								</h5>

								<!-- Loading Spinner with Text -->
								<div
									v-if="isLoading"
									class="d-flex justify-content-left align-items-left mb-3"
								>
									<div
										class="spinner-border text-success"
										role="status"
									>
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

								<!-- Email Input -->
								<div class="mb-3">
									<label class="form-label" for="email"
										>Your Email</label
									>
									<input
										v-model="email"
										id="email"
										name="email"
										type="email"
										class="form-control"
										placeholder="Enter your email"
										autocomplete="email"
									/>
									<div v-if="alerts.email" class="text-danger mt-2">
										{{ alerts.email }}
									</div>
								</div>

								<!-- Submit Button -->
								<div class="d-grid py-3 mt-2">
									<p v-if="cooldown > 0">Reset Password in {{ cooldown }} seconds</p>
									<button v-else type="submit" class="btn btn-lg btn-primary" :disabled="isLoading">
										Reset Password
									</button>
								</div>

								<!-- Login Link -->
								<div
									class="d-flex align-items-right justify-content-between"
								>
									<RouterLink
										to="/"
										class="text-blue text-decoration-underline"
										>Login</RouterLink
									>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- Container end -->
	</section>
</template>

<style scoped></style>
