<script setup>
import { ref, reactive, onMounted, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useStore } from "vuex"; // Import Vuex store
import axios from "@/axios"; // Your configured Axios instance
import { accessKeys } from "@/permissions"; // Import menu items and categories

// Reactive variables
const roleName = ref(""); // Name of the role being edited
const selectedMenus = ref([]); // List of selected menu keys
const groupedMenus = ref({}); // Object to hold menus grouped by categories
const alerts = reactive({ success: "", error: "" }); // Alerts for success/error messages
const isLoading = ref(false); // Loading state
const isAllSelected = ref(false); // Indicates whether all checkboxes are selected

// Access userType from Vuex store
const store = useStore();

// Router instances
const router = useRouter();
const route = useRoute();
const roleId = route.params.id; // Role ID from the route

// Get token from localStorage
const token = localStorage.getItem("token");
if (!token) {
  	throw new Error("No token found");
}

// Configure Axios headers with token for authorization
axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;

// Function to navigate back
const goBack = () => {
  	router.go(-1);
};

// Fetch role and menus data from the backend
const fetchRoleDetails = async () => {
	try {
		isLoading.value = true;
		const response = await axios.get(`/assignedmenus/${roleId}`);

		console.log("API Response:", response.data); // Log the response for debugging

		if (
		response.data &&
		response.data.role &&
		Array.isArray(response.data.assignedMenuNames)
		) {
		roleName.value = response.data.role.name;
		selectedMenus.value = response.data.assignedMenuNames; // Populate selected menus IDs
		updateSelectAll(); // Ensure "Select All" is updated
		} else {
		alerts.error = "Role data or menus data is missing or not in the expected format.";
		}
	} catch (error) {
		console.error("Error fetching role details:", error); // Log error for debugging
		alerts.error = "Failed to load role details.";
	} finally {
		isLoading.value = false;
	}
};

// Group menus by category
const groupMenusByCategory = () => {
	groupedMenus.value = accessKeys.reduce((acc, menu) => {
		if (menu.visibility === "public") {
			if (!acc[menu.category]) acc[menu.category] = [];
			acc[menu.category].push(menu);
		}
		return acc;
	}, {});
};

// Toggle "Select All" checkboxes
const toggleSelectAll = () => {
	if (isAllSelected.value) {
		// Select all menus
		selectedMenus.value = Object.keys(groupedMenus.value).flatMap((category) =>
		groupedMenus.value[category].map((menu) => menu.key)
		);
	} else {
		// Deselect all menus
		selectedMenus.value = [];
	}
};

// Update the state of the "Select All" checkbox based on the individual selections
const updateSelectAll = () => {
	const allMenus = Object.keys(groupedMenus.value).flatMap((category) =>
		groupedMenus.value[category].map((menu) => menu.key)
	);
	// Check if all menus are selected
	isAllSelected.value = allMenus.length > 0 && allMenus.every((key) => selectedMenus.value.includes(key));
};

// Submit selected permissions
const assignPermissions = async () => {
	try {
		isLoading.value = true;
		const response = await axios.post(`/assignmenus/${roleId}`, {
		selectedMenus: selectedMenus.value,
		});

		if (response.data.success) {
		alerts.success = "Permissions assigned successfully!";
		} else {
		alerts.error = "Failed to assign permissions.";
		}
	} catch (error) {
		console.error("Error assigning permissions:", error);
		alerts.error = "Failed to assign permissions.";
	} finally {
		isLoading.value = false;
	}
};

// On component mount
onMounted(() => {
	fetchRoleDetails();
	groupMenusByCategory();
});
</script>

<template>
	<section>
		<!-- App hero header -->
		<div class="app-hero-header d-flex align-items-center">
			<ol class="breadcrumb">
				<li class="breadcrumb-item">
					<i class="bi bi-house lh-1 pe-3 me-3 border-end border-dark"></i>
					<RouterLink to="/home" class="text-decoration-none">Home</RouterLink>
				</li>
				<li class="breadcrumb-item">
					<RouterLink to="/rolelist" class="text-decoration-none"
						>Roles</RouterLink
					>
				</li>
				<li class="breadcrumb-item text-secondary" aria-current="page">
					Permission
				</li>
			</ol>
		</div>

		<!-- App body -->
		<div class="app-body">
			<div class="row gx-3">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-header">
							<h5 class="card-title">
								Assign Permissions to: {{ roleName }}
							</h5>
						</div>
						<div class="card-body">
							<!-- Loading Spinner -->
							<div
								v-if="isLoading"
								class="d-flex justify-content-left align-items-left mb-3"
							>
								<div class="spinner-border text-success" role="status">
									<span class="visually-hidden">Loading...</span>
								</div>
								<span class="ms-2">Please wait...</span>
							</div>

							<!-- Alerts -->
							<div
								v-if="alerts.success"
								class="alert alert-success alert-dismissible fade show"
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
							<div
								v-if="alerts.error"
								class="alert alert-danger alert-dismissible fade show"
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

							<!-- Permissions Section -->
							<div class="row">
								<!-- "Select All" checkbox -->
								<div class="col-md-12 mb-3">
									<div class="form-check">
										<input
											class="form-check-input"
											type="checkbox"
											v-model="isAllSelected"
											@change="toggleSelectAll"
										/>
										<label class="form-check-label">
											Select All
										</label>
									</div>
								</div>
								<!-- Dynamically generate sections for each category -->
								<div
									v-for="(menus, category) in groupedMenus"
									:key="category"
									class="col-md-4 mb-3"
								>
									<h5>{{ category }}</h5>
									<ul class="list-unstyled">
										<!-- Render menu items -->
										<li v-for="menu in menus" :key="menu.key">
											<div class="form-check">
												<input
													class="form-check-input"
													type="checkbox"
													:value="menu.key"
													v-model="selectedMenus"
													@change="updateSelectAll"
												/>
												<label class="form-check-label">{{
													menu.name
												}}</label>
											</div>
										</li>
									</ul>
								</div>
							</div>
						</div>
						<div class="card-footer">
							<div
								class="d-flex justify-content-between align-items-center"
							>
								<!-- Back Button -->
								<button
									type="button"
									class="btn btn-outline-secondary"
									@click="goBack"
								>
									<i class="fa fa-arrow-left"></i> Back
								</button>
								<!-- Save Button -->
								<button
									type="button"
									class="btn btn-success"
									@click="assignPermissions"
									:disabled="isLoading"
								>
									<i class="fa fa-save"></i> Save
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</template>
<style scoped></style>
