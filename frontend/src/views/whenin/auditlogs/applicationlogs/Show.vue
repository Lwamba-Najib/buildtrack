<script setup>
import { onMounted, ref } from "vue";
import { useRoute, RouterLink } from "vue-router";
import axios from "@/axios";
import LoadingIndicator from "../../singles/SpinnerGrow.vue";

const route = useRoute();
const applicationlog = ref(null);
const isLoading = ref(true);

const fetchApplicationLog = async () => {
	isLoading.value = true; // Start loading
	try {
		// Retrieve the token from local storage
		const token = localStorage.getItem("token");

		// Make sure the token exists before making the request
		if (!token) {
			throw new Error("No token found");
		}

		// Fetch the specific application log by ID
		const response = await axios.get(`/applicationlog/${route.params.id}`, {
			headers: {
				Authorization: `Bearer ${token}`, // Include the token in the Authorization header
			},
		});

		if (response.status === 200 && response.data.success) {
			applicationlog.value = response.data.data; // Store the fetched application log data
		} else {
			console.error("Error fetching application log:", response.statusText);
		}
	} catch (error) {
		console.error("Error fetching application log:", error);
	} finally {
		isLoading.value = false; // Stop loading
	}
};
const formatStringDetails = (details) => {
	if (!details) return "";

	// Regular expression to match 'from' and 'to' sections
	const fromRegex = /from:\s*\{[^}]*\}/g;
	const toRegex = /to:\s*\{[^}]*\}/g;

	// Replace 'from' and 'to' sections with colored HTML
	let formattedDetails = details
		.replace(fromRegex, (match) => `<span style="color: red;"><br>${match}</span>`)
		.replace(toRegex, (match) => `<span style="color: green;"><br>${match}</span>`);

	return formattedDetails;
};

const exportFile = async (fileType) => {
	// Set loading state to true while the file is being prepared
	isLoading.value = true;

	// Define URL endpoints for different file types
	const urls = {
		xlsx: `/applicationlogxlsx/${route.params.id}`, // Pass the application log ID to the URL
		csv: `/applicationlogcsv/${route.params.id}`, // Pass the application log ID to the URL
	};

	try {
		// Retrieve the authentication token from local storage
		const token = localStorage.getItem("token");
		if (!token) throw new Error("No token found");

		// Make an HTTP GET request to fetch the file
		const response = await axios.get(urls[fileType], {
			headers: { Authorization: `Bearer ${token}` },
			responseType: "blob", // Set response type to 'blob' to handle file downloads
		});

		// Generate filename using the current date and applicationlog_id
		const date = new Date().toISOString().split("T")[0].replace(/-/g, "_"); // Format: yyyy_mm_dd
		const applicationlog_id = route.params.id;
		const filename = `${date}_applicationlogs_${applicationlog_id}.${fileType}`;

		// Extract filename from Content-Disposition header if available
		const disposition = response.headers["content-disposition"];
		const filenameMatch = disposition
			? disposition.match(/filename="([^"]*)"/)
			: null;
		const finalFilename = filenameMatch ? filenameMatch[1] : filename;

		// Create a URL for the blob and trigger a download
		const urlBlob = window.URL.createObjectURL(new Blob([response.data]));
		const link = document.createElement("a");
		link.href = urlBlob;
		link.download = finalFilename; // Set the file name for download
		document.body.appendChild(link);
		link.click(); // Programmatically click the link to trigger download
		link.remove(); // Remove the link from the DOM
		window.URL.revokeObjectURL(urlBlob); // Clean up the object URL
	} catch (error) {
		// Log any errors that occur during the file export
		console.error(`Error exporting ${fileType} file:`, error);
	} finally {
		// Reset loading state after the file has been processed
		isLoading.value = false;
	}
};

// Methods to trigger export
const exportXlsx = () => exportFile("xlsx");
const exportCsv = () => exportFile("csv");

onMounted(() => {
	fetchApplicationLog();
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
				<li class="breadcrumb-item">
					<RouterLink to="/applicationloglist" class="text-decoration-none"
						>Application Log</RouterLink
					>
				</li>
				<li class="breadcrumb-item text-secondary" aria-current="page">View</li>
			</ol>
			<!-- Breadcrumb end -->
		</div>
		<!-- App Hero header ends -->

		<!-- App body starts -->
		<div class="app-body">
			<!-- Row start -->
			<div class="row">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-body p-2">
							<div class="d-flex justify-content-end my-1 my-lg-0">
								<div class="d-flex flex-row gap-2">
									<div class="d-flex">
										<div class="dropdown">
											<button
												type="button"
												class="btn btn-success btn-sm dropdown-toggle"
												data-bs-toggle="dropdown"
											>
												<i class="fa fa-download"></i>
												Export
											</button>
											<ul
												class="dropdown-menu dropdown-menu-end"
												style="right: 0; left: auto"
											>
												<li>
													<a
														class="dropdown-item"
														href="#"
														@click.prevent="exportXlsx"
														>XLSX</a
													>
												</li>
												<li>
													<hr class="dropdown-divider" />
												</li>
												<li>
													<a
														class="dropdown-item"
														href="#"
														@click.prevent="exportCsv"
														>CSV</a
													>
												</li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Row end -->
			<!-- Row start -->
			<div class="row gx-3">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-header">
							<h5 class="card-title">View Application Log</h5>
						</div>
						<div class="card-body">
							<!-- Row start -->
							<div class="row gx-3">
								<div class="col-12">
									<div class="mb-3 position-relative">
										<!-- Display the LoadingIndicator component -->
										<LoadingIndicator :isLoading="isLoading" />
										<table
											class="table align-middle table-bordered table-hover m-0"
										>
											<tbody>
												<tr>
													<td class="thisth"><b>TYPE</b></td>
													<td>{{ applicationlog?.type }}</td>
												</tr>
												<tr>
													<td class="thisth">
														<b>ACTIVITY</b>
													</td>
													<td>{{ applicationlog?.activity }}</td>
												</tr>
												<tr>
													<td class="thisth"><b>DETAILS</b></td>
													<td
														class="intel"
														v-html="
															formatStringDetails(
																applicationlog?.details
															)
														"
													></td>
												</tr>
												<tr>
													<td class="thisth"><b>BROWSER</b></td>
													<td>{{ applicationlog?.browser }}</td>
												</tr>
												<tr>
													<td class="thisth">
														<b>PLATFORM</b>
													</td>
													<td>{{ applicationlog?.platform }}</td>
												</tr>
												<tr>
													<td class="thisth"><b>IP</b></td>
													<td>{{ applicationlog?.ip }}</td>
												</tr>
												<tr>
													<td class="thisth"><b>USER</b></td>
													<td>{{ applicationlog?.user?.name }}</td>
												</tr>
												<tr>
													<td class="thisth"><b>DATE</b></td>
													<td>{{ applicationlog?.created_at }}</td>
												</tr>
												<tr v-if="!applicationlog">
													<th colspan="10" class="text-center">
														No records found.
													</th>
												</tr>
											</tbody>
										</table>
									</div>
								</div>
							</div>
							<!-- Row end -->
						</div>
						<div class="card-footer">
							<div class="d-flex justify-content-begin my-2 my-lg-0">
								<RouterLink
									to="/applicationloglist"
									class="btn btn-outline-secondary"
									><i class="fa fa-arrow-left"></i> Back</RouterLink
								>
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
.intel {
	white-space: normal;
	word-wrap: break-word;
	max-width: 200px;
}

.from-text {
	color: red;
}

.to-text {
	color: green;
}
</style>
