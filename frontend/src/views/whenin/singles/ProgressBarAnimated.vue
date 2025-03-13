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
    <div v-if="isLoading" class="progress-container">
        <div class="progress">
            <div
                class="progress-bar progress-bar-striped progress-bar-animated"
                role="progressbar"
                :style="{ width: '100%' }"
                aria-valuenow="100"
                aria-valuemin="0"
                aria-valuemax="100"
            >
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="progress-text">Processing please wait...</span>
        </div>
    </div>
</template>

<style scoped>
/* Container for the progress bar */
.progress-container {
    position: relative;
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Position the text inside the progress bar */
.progress {
    width: 100%;  /* Adjust the width to your preference */
    position: relative;
}

.progress-bar {
    width: 100%;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;  /* Adjust color based on your theme */
    font-weight: bold;
}
</style>

