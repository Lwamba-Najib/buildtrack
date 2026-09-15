<script setup>
import { ref, reactive } from "vue";
import { useRouter, RouterLink } from "vue-router";
import axios, { fetchCsrfToken } from "@/axios";
import { useStore } from "vuex";

// Initialize reactive variables for email, password, alerts, and loading state
const email = ref("");
const password = ref("");
const showPassword = ref(false);
const alerts = reactive({
	success: "",
	error: "",
	email: "",
	password: "",
	captcha: "",
});
const isLoading = ref(false);
const isCaptchaCorrect = ref(false);
const router = useRouter();
const store = useStore();

// CAPTCHA setup
const num1 = ref(Math.floor(Math.random() * 10) + 1);
const num2 = ref(Math.floor(Math.random() * 10) + 1);
const userCaptchaAnswer = ref("");

// Validate CAPTCHA
const validateCaptcha = () => {
	isCaptchaCorrect.value = parseInt(userCaptchaAnswer.value) === num1.value + num2.value;
	if (!isCaptchaCorrect.value) {
		alerts.captcha = "Incorrect answer. Please try again.";
	} else {
		alerts.captcha = "";
	}
};

// Reset CAPTCHA
const resetCaptcha = () => {
	num1.value = Math.floor(Math.random() * 10) + 1;
	num2.value = Math.floor(Math.random() * 10) + 1;
	userCaptchaAnswer.value = "";
	isCaptchaCorrect.value = false;
};

// Function to validate email format
const validateEmail = (email) => {
	const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return emailPattern.test(email);
};

// Function to validate the form fields
const validateForm = () => {
	alerts.email = "";
	alerts.password = "";
	alerts.captcha = "";

	let isValid = true;

	if (!email.value) {
		alerts.email = "Email is required.";
		isValid = false;
	} else if (!validateEmail(email.value)) {
		alerts.email = "Invalid email format.";
		isValid = false;
	}

	if (!password.value) {
		alerts.password = "Password is required.";
		isValid = false;
	}

	if (!isCaptchaCorrect.value) {
		alerts.captcha = "Please solve the CAPTCHA correctly.";
		isValid = false;
	}

	return isValid;
};

// Function to handle form submission
const handleSubmit = async (event) => {
	event.preventDefault();

	alerts.success = "";
	alerts.error = "";

	if (!validateForm()) {
		return;
	}

	isLoading.value = true;

	try {
		// Fetch CSRF token before making the login request
		await fetchCsrfToken();

		// Make login request
		const response = await axios.post('/login', {
			email: email.value,
			password: password.value,
		});

		// Check if the login was successful
		if (response.data.success) {
			if (response.data.message === '2FA required.') {
				// Store 2FA data in the Vuex store
				store.dispatch("setTwoFAData", response.data.data);
				// Redirect to 2FA verification page
				alerts.success = "Redirecting to 2FA verification...";
				setTimeout(() => router.push({ name: "TwoFA" }), 500);
			} else {
				// Proceed with normal login
				if (response.data.data.token) {
					// Store the token in local storage for session management
					localStorage.setItem("token", response.data.data.token);
					
					// Delay calling fetchUserData to ensure the token is fully stored
					setTimeout(() => {
						store.commit("WhenIn");
						store.dispatch("fetchUserData");
						alerts.success = "Redirecting ...";
						setTimeout(() => router.push("/home"), 500);
					}, 100);
				} else {
					alerts.error = "Authentication token missing. Please try again.";
				}
			}
		} else {
			alerts.error = response.data.message || "Invalid email or password. Please try again.";
		}

		// Clear sensitive data after submission
		email.value = "";
		password.value = "";
	} catch (error) {
		// Handle different types of errors
		if (error.response && error.response.status === 422) {
			const errors = error.response.data.errors;
			alerts.email = errors.email ? errors.email[0] : "";
			alerts.password = errors.password ? errors.password[0] : "";
		} else if (error.response && error.response.status === 401) {
			alerts.error = error.response.data.message || "Unauthorized. Please check your credentials.";
		} else {
			alerts.error = error.response?.data?.message || "An error occurred. Please try again later.";
		}
	} finally {
		isLoading.value = false;
	}
};
</script>

<template>
	<section>
		<!-- Container start -->
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-xl-4 col-lg-5 col-sm-6 col-12">
					<!-- Form submission with prevent default -->
					<form @submit="handleSubmit" class="my-5">
						<div class="border rounded-2 p-4 mt-5 container-login">
							<div class="login-form">
								<span class="mb-4 d-flex">
									<!-- Hardcoded to the correct public logo path -->
									<img
										src="/assets/images/logo.png"
										class="img-fluid login-logo"
										alt="BuildTrack Logo"
									/>
								</span>
								<h5 class="fw-light mb-4">
									Sign in to access your account.
								</h5>

								<!-- Loading Spinner with Text -->
								<div v-if="isLoading" class="d-flex justify-content-left align-items-left mb-3">
									<div class="spinner-border text-success" role="status">
										<span class="visually-hidden">Loading...</span>
									</div>
									<span class="ms-2">Please wait...</span>
								</div>

								<!-- Success Alert -->
								<div v-if="alerts.success" class="alert border border-success alert-dismissible fade show text-success" role="alert">
									{{ alerts.success }}
									<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
								</div>

								<!-- Error Alert -->
								<div v-if="alerts.error" class="alert border border-danger alert-dismissible fade show text-danger" role="alert">
									{{ alerts.error }}
									<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
								</div>

								<!-- Email Input -->
								<div class="mb-3">
									<label class="form-label" for="email">Your Email</label>
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

								<!-- Password Input with Checkbox -->
								<div class="mb-3">
									<label class="form-label" for="password">Your Password</label>
									<input
										:type="showPassword ? 'text' : 'password'"
										v-model="password"
										id="password"
										name="password"
										class="form-control"
										placeholder="Enter password"
										autocomplete="current-password"
									/>
									<div v-if="alerts.password" class="text-danger mt-2">
										{{ alerts.password }}
									</div>
								</div>

								<!-- Show/Hide Password Checkbox -->
								<div class="form-check d-flex justify-content-end align-items-center">
									<input
										class="form-check-input me-2"
										type="checkbox"
										v-model="showPassword"
										id="show-password"
										name="show-password"
									/>
									<label class="form-check-label">Show Password</label>
								</div>

								<!-- CAPTCHA -->
								<div class="mb-3">
									<div class="d-flex justify-content-between align-items-center mb-2">
										<label class="form-label me-2 fs-5" for="captcha">
											What is {{ num1 }} + {{ num2 }}?
										</label>

										<!-- Reset CAPTCHA Button -->
										<button type="button" class="btn btn-primary ms-3 py-0 px-2 small" @click="resetCaptcha">
											Reset
										</button>
									</div>
									<input 
										v-model="userCaptchaAnswer" 
										id="captcha" 
										name="captcha" 
										type="text" 
										class="form-control" 
										placeholder="Enter your answer" 
										@input="validateCaptcha" 
									/>
									<div v-if="alerts.captcha" class="text-danger mt-2">
										{{ alerts.captcha }}
									</div>
								</div>

								<!-- Submit Button -->
								<div class="d-grid py-3 mt-2">
									<button
										type="submit"
										class="btn btn-lg btn-primary"
										:disabled="!isCaptchaCorrect || isLoading"
									>
										Login
									</button>
								</div>

								<!-- Forgot Password Link -->
								<div class="d-flex align-items-right justify-content-between">
									<RouterLink to="/faqpublic" class="text-blue text-decoration-underline">FAQs</RouterLink>
									<RouterLink to="/forgotpassword" class="text-blue text-decoration-underline">Lost password?</RouterLink>
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

<style scoped>
/* Add any scoped styles here */
</style>