<script setup>
import { onMounted, ref } from 'vue';
import axios from '@/axios'; // Ensure axios is properly configured

// Reactive variables
const stockValue = ref(0);
const totalRevenue = ref(0);
const grossProfit = ref(0);
const netProfit = ref(0);
const totalLoss = ref(0);
const chartData = ref([]);
const error = ref(null);

// Fetch dashboard data
const fetchDashboardData = async () => {
    try {
        const token = localStorage.getItem("token");
        if (!token) throw new Error("No token found");

        const { data } = await axios.get('/dashboard', {
            headers: { Authorization: `Bearer ${token}` },
        });

        if (data.success) {
            stockValue.value = data.data.stock_value;
            totalRevenue.value = data.data.total_revenue;
            grossProfit.value = data.data.gross_profit;
            netProfit.value = data.data.net_profit;
            totalLoss.value = data.data.total_loss;
            chartData.value = data.data.chart_data;
        } else {
            error.value = data.message || "Failed to load dashboard data.";
        }
    } catch (err) {
        error.value = err.message || "An error occurred. Please try again later.";
    }
};

// Initialize chart
const initializeChart = () => {
    const chart = new window.CanvasJS.Chart("chartContainer", {
        animationEnabled: true,
        exportEnabled: true,
        theme: "light1",
        title: {
            text: "Top 10 Selling Products"
        },
        axisY: {
            title: "Quantity Sold", // Add title for Y-axis
        },
        data: [
            {
                type: "bar",
                dataPoints: chartData.value,
            }
        ]
    });

    chart.render();
};

onMounted(async () => {
    await fetchDashboardData();
    initializeChart();
});
</script>

<template>
    <section>
        <!-- App hero header starts -->
        <div class="app-hero-header d-flex align-items-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <i class="bi bi-house lh-1 pe-3 me-3 border-end border-dark"></i>
                    <RouterLink to="/home" class="text-decoration-none">Home</RouterLink>
                </li>
                <li class="breadcrumb-item text-secondary" aria-current="page">Dashboard</li>
            </ol>
        </div>

        <!-- App body -->
        <div class="app-body">
            <!-- Widgets Row -->
            <div class="row gx-3">
                <div class="col-xl-6 col-sm-6 col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="mb-3">Stock Value</h6>
                            <h2 class="mb-3 d-flex align-items-center justify-content-between">
                                <div class="p-3 border border-primary grd-primary-light rounded-5 d-flex">
                                    <i class="bi bi-bar-chart fs-4 lh-1 text-primary"></i>
                                </div>
                                <span class="text-info">UGX {{ stockValue }}</span>
                            </h2>
                            <p class="m-0 small text-secondary">
                                Monthly stock value
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-sm-6 col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="mb-3">Total Revenue</h6>
                            <h2 class="mb-3 d-flex align-items-center justify-content-between">
                                <div class="p-3 border border-primary grd-primary-light rounded-5 d-flex">
                                    <i class="bi bi-cash-stack fs-4 lh-1 text-primary"></i>
                                </div>
                                <span class="text-info">UGX {{ totalRevenue }}</span>
                            </h2>
                            <p class="m-0 small text-secondary">
                                Monthly revenue
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="mb-3">Gross Profit</h6>
                            <h2 class="mb-3 d-flex align-items-center justify-content-between">
                                <div class="p-3 border border-success grd-success-light rounded-5 d-flex">
                                    <i class="bi bi-currency-dollar fs-4 lh-1 text-success"></i>
                                </div>
                                <span class="text-info">UGX {{ grossProfit }}</span>
                            </h2>
                            <p class="m-0 small text-secondary">
                                Monthly gross profit
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="mb-3">Net Profit</h6>
                            <h2 class="mb-3 d-flex align-items-center justify-content-between">
                                <div class="p-3 border border-success grd-success-light rounded-5 d-flex">
                                    <i class="bi bi-currency-dollar fs-4 lh-1 text-success"></i>
                                </div>
                                <span class="text-info">UGX {{ netProfit }}</span>
                            </h2>
                            <p class="m-0 small text-secondary">
                                Monthly net profit
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-sm-6 col-12">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="mb-3">Total Loss</h6>
                            <h2 class="mb-3 d-flex align-items-center justify-content-between">
                                <div class="p-3 border border-danger grd-danger-light rounded-5 d-flex">
                                    <i class="bi bi-graph-down fs-4 lh-1 text-danger"></i>
                                </div>
                                <span class="text-info">UGX {{ totalLoss }}</span>
                            </h2>
                            <p class="m-0 small text-secondary">
                                Monthly total loss
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bar Chart -->
            <div class="row gx-3">
                <div class="col-xl-12 col-lg-12">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title">Top 10 Selling Products</h5>
                        </div>
                        <div class="card-body">
                            <div id="chartContainer" style="height: 450px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error -->
        <div v-if="error" class="alert alert-danger mt-3">
            {{ error }}
        </div>
    </section>
</template>

<style scoped></style>