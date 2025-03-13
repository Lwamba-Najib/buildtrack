<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "@/axios"; // Adjust the path as necessary
import { backendBaseUrl } from "@/config"; // Import the backend base URL from config
import { useRouter } from "vue-router";
import { useStore } from "vuex";
import Preloader from "../singles/Preloader.vue"; // Adjust the path as necessary

// Access Vuex store
const store = useStore();
// Computed properties for user data from Vuex state
const userName = computed(() => store.state.userName);
// Router instance for navigation
const router = useRouter();
// Define loading state
const isLoading = ref(false);
// Default logo
const logoSrc = ref("/assets/images/logo.png"); 

// Function to handle logout
const handleLogout = async (isVoluntary = true) => {
    if (isVoluntary && !confirm("Are you sure you want to logout?")) {
        return;
    }

    isLoading.value = true; // Start loading

    try {
        await store.dispatch("logout"); // Call centralized Vuex logout action
        if (isVoluntary) {
            localStorage.setItem("voluntaryLogout", "true"); // Optional flag
        }
        router.push("/"); // Redirect after logout
    } catch (error) {
        console.error("Logout failed:", error);

        // Handle backend unreachable or other errors
        alert("The backend is currently unreachable. You will be redirected to the login page.");

        // Clear local state and localStorage to ensure the user is logged out
        store.commit("WhenOut");
        store.commit("resetUserData");
        localStorage.removeItem("token");

        // Redirect to login
        router.push("/"); // Ensure redirection
    } finally {
        isLoading.value = false; // Stop loading
    }
};

// Function to check if the session is still valid
const checkSessionTimeout = async () => {
	if (!store.state.isLoggedIn || !localStorage.getItem("token")) return;

	try {
		// Check session validity
		const { data } = await axios.get("/session", {
			headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
		});

		if (data.success && data.remaining_time_in_minutes > 0) {
			console.log(`Session is valid. Remaining time: ${data.remaining_time_in_minutes} minutes`);

			// Optionally warn the user if time is low
			if (data.remaining_time_in_minutes < 5) {
				alert(`Your session will expire in ${data.remaining_time_in_minutes} minutes.`);
			}
		} else {
			alert("Your session has expired.");
			await handleLogout(false);
		}
	} catch (error) {
		console.error("Error checking session:", error);
		await handleLogout(false);
	}
};

// Function to fetch and populate saved data
const fetchSettings = async () => {
    try {
        const token = localStorage.getItem("token");
        const response = await axios.get("/generalsettingslist", {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        });

        if (response.data && response.data.success && response.data.data) {
            const settingsData = response.data.data;

            // Update logo source if logo exists, otherwise use default
            if (settingsData.logo) {
                logoSrc.value = `${backendBaseUrl}/storage/${settingsData.logo}`; // Assuming full URL is returned
            } else {
                logoSrc.value = "/assets/images/noimage.jpg"; // Default image
            }
        } else {
            console.error("Expected settings not found in response data.");
            alert("Failed to load settings. Please try again later.");
        }
    } catch (error) {
        console.error("Error fetching settings:", error);
        alert("Failed to load settings. Please try again later.");
    } 
};

// Fetch user data and start session check on component mount
onMounted(() => {
	fetchSettings(); // Load logo
	// Set an interval to check the session status every 5 minutes only if logged in
	setInterval(checkSessionTimeout, 5 * 60 * 1000); // 5 minutes
});
</script>

<template>
	<section>
		<!-- App header starts -->
		<div class="app-header d-flex align-items-center">
			<!-- Toggle buttons start -->
			<div class="d-flex">
				<button class="toggle-sidebar" id="toggle-sidebar">
					<i class="bi bi-list lh-1"></i>
				</button>
				<button class="pin-sidebar" id="pin-sidebar">
					<i class="bi bi-list lh-1"></i>
				</button>
			</div>
			<!-- Toggle buttons end -->

			<!-- App brand starts -->
			<div class="app-brand py-2 ms-3">
				<RouterLink to="/">
					<img
                        :src="logoSrc"
                        class="logo"
                        alt="App Logo"
                    />
				</RouterLink>
			</div>
			<!-- App brand ends -->

			<!-- App header actions start -->
			<div class="header-actions col">
				<div class="d-lg-flex d-none">
					<div class="dropdown border-start">
						<a
							class="dropdown-toggle d-flex px-3 py-4 position-relative"
							href="#!"
							role="button"
							data-bs-toggle="dropdown"
							aria-expanded="false"
						>
							<i class="bi bi-bell fs-4 lh-1 text-secondary"></i>
							<span class="count-label info"></span>
						</a>
						<div class="dropdown-menu dropdown-menu-end shadow-lg">
							<h5 class="fw-semibold px-3 py-2 text-primary">Updates</h5>
							<div class="dropdown-item">
								<div class="d-flex py-2 border-bottom">
									<div
										class="icon-box md bg-success rounded-circle me-3"
									>
										<span class="fw-bold text-white">ST</span>
									</div>
									<div class="m-0">
										<h6 class="mb-1 fw-semibold">Single Transfer</h6>
										<p class="mb-1">
											You have pending single transfers.
										</p>
									</div>
								</div>
								<div class="d-flex py-2 border-bottom">
									<div
										class="icon-box md bg-success rounded-circle me-3"
									>
										<span class="fw-bold text-white">DT</span>
									</div>
									<div class="m-0">
										<h6 class="mb-1 fw-semibold">bulk Transfer</h6>
										<p class="mb-1">
											You have pending Bulk transfers.
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="dropdown ms-2">
					<a
						id="userSettings"
						class="dropdown-toggle d-flex py-2 align-items-center text-decoration-none"
						href="#!"
						role="button"
						data-bs-toggle="dropdown"
						aria-expanded="false"
					>
						<img
							src="/assets/images/avatar6.png"
							class="rounded-2 img-3x"
							alt="Bootstrap Gallery"
						/>
						<span class="ms-2 text-truncate d-lg-block d-none">{{
							userName
						}}</span>
					</a>
					<div class="dropdown-menu dropdown-menu-end shadow-lg">
						<div class="header-action-links mx-3 gap-2">
							<RouterLink class="dropdown-item" to="/profile"
								><i class="bi bi-person text-primary"></i>Profile
							</RouterLink>
							<RouterLink class="dropdown-item" to="/usersettings"
								><i class="bi bi-gear text-danger"></i
								>Settings</RouterLink
							>
						</div>
						<div class="mx-3 mt-2 d-grid">
							<button @click="handleLogout" class="btn btn-primary btn-sm">
								<i class="fa fa-sign-out"></i> Logout
							</button>
						</div>
					</div>
				</div>
			</div>
			<!-- App header actions end -->
		</div>
		<!-- App header ends -->

		<!-- Loading indicator -->
		<Preloader :isLoading="isLoading" />
	</section>
</template>

<style scoped></style>
