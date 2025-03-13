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
				<li class="breadcrumb-item text-secondary" aria-current="page">Single Transfer Logs</li>
			</ol>
			<!-- Breadcrumb end -->
		</div>
		<!-- App Hero header ends -->

		<!-- App body starts -->
		<div class="app-body">
			<!-- Row start -->
			<div class="row justify-content-center">
				<div class="col-xxl-12">
					<div class="card mb-3">
						<div class="card-body">
							<!-- Row start -->
							<div class="row align-items-center">
								<div class="col">
									<h6 class="text-primary">UI</h6>
									<h4 class="m-0">Still under developement</h4>
								</div>
							</div>
							<!-- Row end -->
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
