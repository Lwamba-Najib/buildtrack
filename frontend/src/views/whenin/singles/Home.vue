<script setup>
import { onMounted, computed, ref, reactive } from "vue";
import axios from '@/axios';
import { useStore } from 'vuex';
import ProgressBarAnimated from "./ProgressBarAnimated.vue";
import { useCustomUtils } from "@/utils/customUtils";
useCustomUtils();

// Access Vuex store
const store = useStore();

// Computed properties for user data from Vuex state
const userName = computed(() => store.state.userName);

const alerts = reactive({
	success: "",
	error: "",
});

const logoSrc = ref("/assets/images/logo.png");
const isLoading = ref(false);

</script>

<template>
    <section>
        <!-- App hero header starts -->
        <div class="app-hero-header d-flex align-items-center">
            <!-- Breadcrumb start -->
            <ol class="breadcrumb">
                <li class="breadcrumb-item text-secondary" aria-current="page">
                    <i class="bi bi-house lh-1 pe-3 me-3 border-end border-dark"></i>Home
                </li>
            </ol>
            <!-- Breadcrumb end -->
        </div>
        <!-- App Hero header ends -->

        <!-- App body starts -->
        <div class="app-body">
			<!-- Progress Bar Animated -->
			<ProgressBarAnimated :isLoading="isLoading" />

			<!-- Success Alert -->
			<div
				v-if="alerts.success"
				class="alert border border-success alert-dismissible fade show text-success"
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

			<!-- Error Alert -->
			<div
				v-if="alerts.error"
				class="alert border border-danger alert-dismissible fade show text-danger"
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
            <!-- Row start -->
            <div class="row justify-content-center">
                <div class="col-xxl-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <!-- Row start -->
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <img src="/assets/images/avatar6.png" class="img-5xx rounded-circle"
                                        alt="Bootstrap Gallery" />
                                </div>
                                <div class="col">
                                    <h6 class="text-primary">Hi {{ userName }},</h6>
                                    <h4 class="m-0">Welcome To BuildTrack, <br>The Platform To Streamline Your Construction Inventory Management.</h4>
                                </div>                                
                            </div>
                            <!-- Row end -->
                        </div>
                    </div>
                    <div class="d-flex justify-content-center mt-5">
                        <img :src="logoSrc" class="logo animated-logo" alt="Logo"/>
                    </div>                    
                </div>
            </div>
            <!-- Row end -->
        </div>
        <!-- App body ends -->
    </section>
</template>

<style scoped>
.animated-logo {
    width: 440px;
    height: 450px;
    border: 3px dashed #B22222;
    border-radius: 15px;
    padding: 5px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
    animation: fadeInUp 1s ease-out, bounceAnimation 3s infinite ease-in-out;
    transition: transform 0.5s ease-in-out, box-shadow 0.5s ease-in-out;
}

/* Hover effect */
.animated-logo:hover {
    transform: scale(1.05) rotate(2deg);
    box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.4);
}

/* Entrance fade-in animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Looping bounce animation */
@keyframes bounceAnimation {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-15px);
    }
}
</style>