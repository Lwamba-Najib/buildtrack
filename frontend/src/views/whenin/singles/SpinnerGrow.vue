<script setup>
// Receive the loading state as a prop
const props = defineProps({
    isLoading: {
        type: Boolean,
        required: true,
    },
});
</script>

<template>
    <div v-if="isLoading" class="spinner-overlay d-flex justify-content-center align-items-center">
        <div class="spinner-grow spinner-grow-sm text-primary me-2" role="status">
            <span class="visually-hidden">Processing...</span>
        </div>
        <span>Processing please wait...</span>
    </div>
</template>

<style scoped>
/* Style for the spinner overlay */
.spinner-overlay {
    position: absolute;
    top: 0px;
    /* Adjust this value to set the distance from the top of the table */
    left: 0;
    width: 100%;
    height: calc(100% - 0px);
    /* Adjust height to account for the offset */
    background: rgba(255, 255, 255, 0.8);
    /* Semi-transparent background */
    z-index: 10;
    /* Ensure it appears above the table */
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>
