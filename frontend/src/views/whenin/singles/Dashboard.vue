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
							<div class="d-flex align-items-center flex-row">
								<div class="d-flex align-items-center">
									<div class="border border-primary grd-primary-light rounded-4 p-3">
										<i class="bi bi-check-circle text-primary fs-3 lh-1"></i>
									</div>
									<div class="mx-3">
										<h3 class="m-0">1800</h3>
										<p class="m-0 text-secondary">Likes</p>
									</div>
								</div>
								<div id="sparklineLine1" class="ms-auto"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-sm-6 col-12">
					<div class="card mb-3">
						<div class="card-body">
							<div class="d-flex align-items-center flex-row">
								<div class="d-flex align-items-center">
									<div class="border border-primary grd-primary-light rounded-4 p-3">
										<i class="bi bi-check-circle text-primary fs-3 lh-1"></i>
									</div>
									<div class="mx-3">
										<h3 class="m-0">4500</h3>
										<p class="m-0 text-secondary">Views</p>
									</div>
								</div>
								<div id="sparklineLine2" class="ms-auto"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-sm-6 col-12">
					<div class="card mb-3">
						<div class="card-body">
							<div class="d-flex align-items-center flex-row">
								<div class="d-flex align-items-center">
									<div class="border border-primary grd-primary-light rounded-4 p-3">
										<i class="bi bi-check-circle text-primary fs-3 lh-1"></i>
									</div>
									<div class="mx-3">
										<h3 class="m-0">3200</h3>
										<p class="m-0 text-secondary">Users</p>
									</div>
								</div>
								<div id="sparklineLine3" class="ms-auto"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xl-3 col-sm-6 col-12">
					<div class="card mb-3">
						<div class="card-body">
							<div class="d-flex align-items-center flex-row">
								<div class="d-flex align-items-center">
									<div class="border border-danger grd-danger-light rounded-4 p-3">
										<i class="bi bi-check-circle text-danger fs-3 lh-1"></i>
									</div>
									<div class="mx-3">
										<h3 class="m-0">9700</h3>
										<p class="m-0 text-secondary">Sales</p>
									</div>
								</div>
								<div id="sparklineLine4" class="ms-auto"></div>
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
