<script setup>
import LeftNav from "@/views/whenin/navbar/LeftNav.vue";
import TopNav from "@/views/whenin/navbar/TopNav.vue";
</script>

<template>
    <!-- Page wrapper start -->
    <div class="page-wrapper">
        <!-- App header starts -->
        <TopNav />
        <!-- App header ends -->

        <!-- Main container start -->
        <div class="main-container">
            <!-- Sidebar wrapper start -->
            <LeftNav />
            <!-- Sidebar wrapper end -->

            <!-- App container starts -->
            <div class="app-container">
                <RouterView />
                <div class="app-footer">
                    <span>© BuildTrack</span>
                </div>
                <!-- App footer end -->
            </div>
            <!-- App container ends -->
        </div>
        <!-- Main container end -->
    </div>
    <!-- Page wrapper end -->
</template>

<style scoped></style>
