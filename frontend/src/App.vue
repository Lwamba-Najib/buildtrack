<script setup>
import { onMounted, ref } from "vue";
import { useStore } from "vuex"; // Import Vuex store
import { RouterView } from "vue-router";
import axios from "@/axios";
import { backendBaseUrl } from "@/config";

// Vuex store
const store = useStore();

// Reactive variable for favicon and logo
const faviconSrc = ref("/assets/images/favicon.png"); // Default favicon
const logoSrc = ref("/assets/images/logo.png"); // Default logo
const appName = ref("BuildTrack"); // Default application name

// Function to fetch settings and update the favicon, title, and logo
const fetchSettings = async () => {
	try {
		const response = await axios.get("/getlogofavicon");

		if (response.data && response.data.success && response.data.data) {
			const settingsData = response.data.data;

			// Update application name dynamically in the title
			appName.value = settingsData.name || "BuildTrack"; // Default to "BuildTrack" if name is not found
			document.title = appName.value; // Update the document title

			// Update logo source if logo exists, otherwise use default
			if (settingsData.logo) {
				logoSrc.value = `${backendBaseUrl}/storage/${settingsData.logo}`;
			} else {
				logoSrc.value = "/assets/images/noimage.jpg"; // Default image
			}

			// Update favicon source if favicon exists, otherwise use default
			if (settingsData.favicon) {
				faviconSrc.value = `${backendBaseUrl}/storage/${settingsData.favicon}`;
			} else {
				faviconSrc.value = "/assets/images/favicon.png"; // Default favicon
			}

			// Dynamically update the favicon in the head section
			const link = document.querySelector("link[rel='icon']");
			if (link) {
				link.href = faviconSrc.value;
			}

			// Update background wallpaper
			if (settingsData.wallpaper) {
				document.body.style.backgroundImage = `url('${backendBaseUrl}/storage/${settingsData.wallpaper}')`;
				document.body.style.backgroundSize = "cover"; // Adjust size to cover the screen
				document.body.style.backgroundRepeat = "no-repeat"; // Prevent tiling
				document.body.style.backgroundColor = "none"; // Ensure no default background color
			} else {
				document.body.style.backgroundColor = "none"; // Ensure no background if wallpaper is not set
			}
		} else {
			console.error("Expected settings not found in response data.");
		}
	} catch (error) {
		console.error("Error fetching favicon and settings:", error);
	}
};

// Fetch settings when component is mounted
onMounted(() => {
  	// Fetch settings when component is mounted
	fetchSettings();

	// Check if the token exists in localStorage before dispatching fetchUserData
	const token = localStorage.getItem("token");
	if (token) {
		// Token found, dispatch fetchUserData to fetch the user data
		store.dispatch("fetchUserData");
	}
});
</script>

<template>
  	<RouterView :logo-src="logoSrc" />
</template>
