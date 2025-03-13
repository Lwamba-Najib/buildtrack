<script setup>
import { ref, reactive, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios, { fetchCsrfToken } from "@/axios"; // Ensure fetchCsrfToken is correctly exported
import { useStore } from "vuex"; // Make sure you import and use your Vuex store if you use Vuex

const props = defineProps({
	logoSrc: {
		type: String,
		required: true,
	}
});

// Initialize reactive variables
const router = useRouter(); // Router instance for navigation
const store = useStore(); // Vuex store for state management
const user = ref(null);
const otp = ref("");
const cooldown = ref(0); // Cooldown timer in seconds
const alerts = reactive({
	success: "", // Success message
	error: "", // General error message
	otp: "", // OTP validation error message
});
const isLoading = ref(false); // Loading state
// Default logo
const logoSrc = ref("/assets/images/logo.png"); 

onMounted(() => {
    // Retrieve 2FA data from the Vuex store
    user.value = store.state.twoFAData?.user;

    if (!user.value) {
        console.error("User data not found. Redirecting to login...");
        router.push({ name: "Login" });
    } else {
        console.log("User data:", user.value);
    }
});
// Function to validate the form fields
const validateForm = () => {
	alerts.otp = ""; // Clear previous otp error

	let isValid = true;

	// Validate otp field
	if (!otp.value) {
		alerts.otp = "OTP is required.";
		isValid = false;
	}

	return isValid; // Return true if form is valid, false otherwise
};

const handleSubmit = async (event) => {
	event.preventDefault(); // Prevent default form submission

	// Clear previous alerts
	alerts.success = "";
	alerts.error = "";

	// Validate form before submission
	if (!validateForm()) {
		return; // Stop submission if validation fails
	}

	if (!user.value || !user.value.id) {
		alerts.error = "User ID is missing.";
		console.error("User ID is missing. Cannot submit OTP.");
		return;
	}

	isLoading.value = true; // Set loading state to true

    try {
		// Fetch CSRF token before making the login request
        await fetchCsrfToken();
        const response = await axios.post("/verifyotp", {
            otp: otp.value,
            user_id: user.value.id,
        });

        if (response.data.success) {
            localStorage.setItem("token", response.data.data.token);
            store.commit("WhenIn");
            store.dispatch("fetchUserData");
			alerts.success = "Redirecting ...";
            setTimeout(() => router.push({ name: "Home" }), 500);
        } else {
            alerts.error = response.data.message || "Invalid OTP.";
        }
    } catch (error) {
        alerts.error = error.response?.data?.message || "An error occurred. Please try again.";
    } finally {
        isLoading.value = false;
    }
};

const resendOTP = async () => {
    if (cooldown.value > 0) {
        alerts.error = `Please wait ${cooldown.value} seconds before resending OTP.`;
        return;
    }

    alerts.success = "";
    alerts.error = "";

	isLoading.value = true; // Set loading state to true

    try {
        // Retrieve the user ID from the Vuex store
        const userId = store.state.twoFAData?.user?.id;

        if (!userId) {
            alerts.error = "User ID not found. Please try logging in again.";
            return;
        }
		// Fetch CSRF token before making the login request
        await fetchCsrfToken();
        // Make the request to resend OTP
        const response = await axios.post("/getotp", {
            user_id: userId, // Pass the user ID to the backend
        });

        if (response.data.success) {
            alerts.success = "New OTP sent successfully!";
            cooldown.value = 60; // Set cooldown to 60 seconds
            startCooldownTimer();
        } else {
            alerts.error = response.data.message || "Failed to resend OTP.";
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
								<h5 class="fw-light mb-3 lh-2">
									Two-Factor Authentication
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

								<!-- OTP Input -->
								<div class="mb-3">
									<label class="form-label" for="otp"
										>Enter OTP</label
									>
									<input
										v-model="otp"
										id="otp"
										name="otp"
										type="password"
										class="form-control"
										placeholder="Enter your otp"
										autocomplete="otp"
									/>
									<div v-if="alerts.otp" class="text-danger mt-2">
										{{ alerts.otp }}
									</div>
								</div>

								<!-- Submit Button -->
								<div class="d-grid py-3 mt-2">
									<button type="submit" class="btn btn-lg btn-primary" :disabled="isLoading">
                                        Verify OTP
									</button>
								</div>

								<!-- Resend OTP Link -->
								<div class="d-flex align-items-right justify-content-end">
									<p v-if="cooldown > 0">Resend OTP in {{ cooldown }} seconds</p>
    								<p v-else>Didn't receive the OTP? <a href="#" @click.prevent="resendOTP">Resend OTP</a></p>
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
