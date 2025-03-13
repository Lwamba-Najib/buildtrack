<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import axios from '@/axios';
import LoadingIndicator from '../singles/SpinnerGrow.vue';

const route = useRoute();
const user = ref(null);
const isLoading = ref(true);

const fetchDetails = async () => {
    isLoading.value = true; // Start loading
    try {
        // Retrieve the token from local storage
        const token = localStorage.getItem('token');

        // Make sure the token exists before making the request
        if (!token) {
            throw new Error('No token found');
        }

        // Fetch the specific user by ID
        const response = await axios.get(`/usershow/${route.params.id}`, {
            headers: {
                Authorization: `Bearer ${token}`, // Include the token in the Authorization header
            },
        });

        if (response.status === 200 && response.data.success) {
            user.value = response.data.data; // Store the fetched user data
        } else {
            console.error('Error fetching user:', response.statusText);
        }
    } catch (error) {
        console.error('Error fetching user:', error);
    } finally {
        isLoading.value = false; // Stop loading
    }
};

// Method to trigger PDF download
const exportPDF = async () => {
    isLoading.value = true; // Set loading state to true while preparing the file
    try {
        const token = localStorage.getItem('token');
        if (!token) throw new Error('No token found');

        // Make an HTTP GET request to fetch the PDF file
        const response = await axios.get(`/userpdf/${route.params.id}`, {
            headers: {
                Authorization: `Bearer ${token}`,
            },
            responseType: 'blob', // Set response type to 'blob' for handling file downloads
        });

        // Generate filename with the current date
        const today = new Date().toISOString().split('T')[0].replace(/-/g, '_'); // Format: yyyy_mm_dd
        const user_id = route.params.id;
        const filename = `${today}_user_${user_id}.pdf`;

        // Extract filename from Content-Disposition header if available
        const disposition = response.headers['content-disposition'];
        const filenameMatch = disposition ? disposition.match(/filename="([^"]*)"/) : null;
        const finalFilename = filenameMatch ? filenameMatch[1] : filename;

        // Create a URL for the blob and trigger a download
        const urlBlob = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = urlBlob;
        link.download = finalFilename; // Set the file name for download
        document.body.appendChild(link);
        link.click(); // Programmatically click the link to trigger download
        link.remove(); // Remove the link from the DOM
        window.URL.revokeObjectURL(urlBlob); // Clean up the object URL
    } catch (error) {
        console.error('Error exporting PDF:', error);
    } finally {
        isLoading.value = false; // Reset loading state after processing
    }
};

onMounted(() => {
    fetchDetails();
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
                    <RouterLink to="/userlist" class="text-decoration-none">User</RouterLink>
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
                                            <button type="button" class="btn btn-success btn-sm dropdown-toggle"
                                                data-bs-toggle="dropdown"><i class="fa fa-download"></i>
                                                Export
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" style="right: 0; left: auto;">
                                                <li>
                                                    <a class="dropdown-item" href="#" @click.prevent="exportPDF">PDF</a>
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
                            <h5 class="card-title">View User</h5>
                        </div>
                        <div class="card-body">
                            <!-- Row start -->
                            <div class="row gx-3">
                                <div class="col-12">
                                    <div class="mb-3 position-relative">
                                        <!-- Display the LoadingIndicator component -->
                                        <LoadingIndicator :isLoading="isLoading" />
                                        <table class="table align-middle table-bordered table-hover m-0">
                                            <tbody>
                                                <tr>
                                                    <td class="thisth"><b>USER NUMBER/ID</b></td>
                                                    <td>{{ user?.user_number }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="thisth"><b>NAME</b></td>
                                                    <td>{{ user?.name }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="thisth"><b>GENDER</b></td>
                                                    <td>{{ user?.gender }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="thisth"><b>NIN</b></td>
                                                    <td class="wrap-column">{{ user?.nin }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="thisth"><b>CONTACT</b></td>
                                                    <td>{{ user?.phone_number }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="thisth"><b>EMAIL</b></td>
                                                    <td>{{ user?.email }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="thisth"><b>ROLE</b></td>
                                                    <td>{{ user?.role }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="thisth"><b>DATE</b></td>
                                                    <td>{{ user?.created_at }}</td>
                                                </tr>
                                                <tr v-if="!user">
                                                    <th colspan="10" class="text-center">No records found.</th>
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
                                <RouterLink to="/userlist" class="btn btn-outline-secondary"><i
                                        class="fa fa-arrow-left"></i>
                                    Back</RouterLink>
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
