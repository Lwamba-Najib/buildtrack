<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import LoadingIndicator from '../../whenin/singles/SpinnerGrow.vue';

// Router instance
const router = useRouter();
const isLoading = ref(true);

// Function to scroll to the top of the page
const scrollToTop = () => {
	window.scrollTo({
		top: 0,
		behavior: "smooth", // Smooth scrolling
	});
};
</script>

<template>
	<section>
		<!-- Container start -->
		<div class="container">
			<!-- Row start -->
			<div class="row gx-3">
				<div class="col-xxl-12">
					<div class="card mb-3 mt-5 faq_wrap">
						<div class="card-header">
							<h5 class="card-title">FAQs</h5>
						</div>
						<div class="card-body">
							<div class="accordion" id="accordionExample2">
								<div class="accordion-item">
									<h2 class="accordion-header" id="headingOneLight">
										<button
											class="accordion-button"
											type="button"
											data-bs-toggle="collapse"
											data-bs-target="#collapseOneLight"
											aria-expanded="true"
											aria-controls="collapseOneLight"
										>
											Accordion Item #1
										</button>
									</h2>
									<div
										id="collapseOneLight"
										class="accordion-collapse collapse show"
										aria-labelledby="headingOneLight"
										data-bs-parent="#accordionExample2"
									>
										<div class="accordion-body">
											<h5 class="mb-3 fw-light lh-lg">
												<strong class="fw-bold"
													>This is the third item's accordion
													body.</strong
												>
												It is hidden by default, until the
												collapse plugin adds the appropriate
												classes that we use to style each element.
												These classes control the overall
												appearance, as well as the showing and
												hiding via CSS transitions. You can modify
												any of this with custom CSS or overriding
												our default variables. It's also worth
												noting that just about any HTML can go
												within the <code>.accordion-body</code>,
												though the transition does limit overflow.
											</h5>
										</div>
									</div>
								</div>
								<div class="accordion-item">
									<h2 class="accordion-header" id="headingTwoLight">
										<button
											class="accordion-button collapsed"
											type="button"
											data-bs-toggle="collapse"
											data-bs-target="#collapseTwoLight"
											aria-expanded="false"
											aria-controls="collapseTwoLight"
										>
											Accordion Item #2
										</button>
									</h2>
									<div
										id="collapseTwoLight"
										class="accordion-collapse collapse"
										aria-labelledby="headingTwoLight"
										data-bs-parent="#accordionExample2"
									>
										<div class="accordion-body">
											<h5 class="mb-3 fw-light lh-lg">
												<strong class="fw-bold"
													>This is the third item's accordion
													body.</strong
												>
												It is hidden by default, until the
												collapse plugin adds the appropriate
												classes that we use to style each element.
												These classes control the overall
												appearance, as well as the showing and
												hiding via CSS transitions. You can modify
												any of this with custom CSS or overriding
												our default variables. It's also worth
												noting that just about any HTML can go
												within the <code>.accordion-body</code>,
												though the transition does limit overflow.
											</h5>
										</div>
									</div>
								</div>
								<div class="accordion-item">
									<h2 class="accordion-header" id="headingThreeLight">
										<button
											class="accordion-button collapsed"
											type="button"
											data-bs-toggle="collapse"
											data-bs-target="#collapseThreeLight"
											aria-expanded="false"
											aria-controls="collapseThreeLight"
										>
											Accordion Item #3
										</button>
									</h2>
									<div
										id="collapseThreeLight"
										class="accordion-collapse collapse"
										aria-labelledby="headingThreeLight"
										data-bs-parent="#accordionExample2"
									>
										<div class="accordion-body">
											<h5 class="mb-3 fw-light lh-lg">
												<strong class="fw-bold"
													>This is the third item's accordion
													body.</strong
												>
												It is hidden by default, until the
												collapse plugin adds the appropriate
												classes that we use to style each element.
												These classes control the overall
												appearance, as well as the showing and
												hiding via CSS transitions. You can modify
												any of this with custom CSS or overriding
												our default variables. It's also worth
												noting that just about any HTML can go
												within the <code>.accordion-body</code>,
												though the transition does limit overflow.
											</h5>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="card-footer">
							<div
								class="d-flex justify-content-between align-items-center my-2 my-lg-0"
							>
								<!-- Back Button -->
								<button
									type="button"
									class="btn btn-outline-secondary"
									@click="router.go(-1)"
								>
									<i class="fa fa-arrow-left"></i> Back
								</button>
								<!-- Top Button -->
								<button
									type="button"
									class="btn btn-success"
									@click="scrollToTop"
								>
									<i class="fa fa-arrow-up"></i> Top
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- Row end -->
		</div>
		<!-- Container end -->
	</section>
</template>

<style scoped></style>
