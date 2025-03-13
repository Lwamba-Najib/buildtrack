<script setup>
defineProps({
    isLoading: {
        type: Boolean,
        required: true,
    }
});
</script>
<template>
    <div v-if="isLoading" class="spinner-overlay d-flex justify-content-center align-items-center">
        <div class="spinner-grow spinner-border-sm text-primary me-2" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <span>Processing, please wait...</span>
    </div>
</template>

<style scoped>
/* Style for the spinner overlay */
.spinner-overlay {
    position: fixed;
    /* Changed from absolute to fixed */
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    /* Ensure it covers the entire viewport */
    background: rgba(255, 255, 255, 0.8);
    z-index: 9999;
    /* Use a high z-index to ensure it appears above other elements */
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>
