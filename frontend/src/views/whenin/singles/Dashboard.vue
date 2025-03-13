<script setup>
import { RouterLink } from "vue-router";
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
				<li class="breadcrumb-item text-secondary" aria-current="page">Dashboard</li>
			</ol>
			<!-- Breadcrumb end -->
		</div>
		<!-- App Hero header ends -->

		<!-- App body starts -->
		<div class="app-body">
			<!-- Row start -->
			<div class="row gx-3">
				<div class="col-xl-3 col-sm-6 col-12">
					<div class="card mb-3">
						<div class="card-body">
							<div class="mb-2">
								<i class="fa fa-users fs-1 text-success lh-1"></i>
							</div>
							<div class="d-flex align-items-center justify-content-between">
								<h5 class="m-0 text-secondary fw-normal">Users</h5>
								<h3 class="m-0 text-success">3500</h3>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-sm-6 col-12">
					<div class="card mb-3">
						<div class="card-body">
							<div class="mb-2">
								<i class="fa fa-cubes fs-1 text-success lh-1"></i>
							</div>
							<div class="d-flex align-items-center justify-content-between">
								<h5 class="m-0 text-secondary fw-normal">Accounts</h5>
								<h3 class="m-0 text-success">2900</h3>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-sm-6 col-12">
					<div class="card mb-3">
						<div class="card-body">
							<div class="arrow-label">+18%</div>
							<div class="mb-2">
								<i class="fa fa-paw fs-1 text-success lh-1"></i>
							</div>
							<div class="d-flex align-items-center justify-content-between">
								<h5 class="m-0 text-secondary fw-normal">Clients</h5>
								<h3 class="m-0 text-success">6500</h3>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-sm-6 col-12">
					<div class="card mb-3">
						<div class="card-body">
							<div class="arrow-label">+24%</div>
							<div class="mb-2">
								<i class="fa fa-refresh fs-1 text-success lh-1"></i>
							</div>
							<div class="d-flex align-items-center justify-content-between">
								<h5 class="m-0 text-secondary fw-normal">Transactions</h5>
								<h3 class="m-0 text-success">7200</h3>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Row end -->

			<!-- Row start -->
			<div class="row gx-3">
				<div class="col-xl-12 col-lg-12">
					<div class="card mb-3">
						<div class="card-header">
							<h5 class="card-title">view</h5>
						</div>
						<div class="card-body">
							<h1>Data</h1>
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
