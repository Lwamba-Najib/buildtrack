<script setup>
import { onMounted, ref, onBeforeUnmount, watch, computed } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useStore } from "vuex";
import { useMenuAccess } from "@/permissions"; // Adjust the path as needed

// Use the menu access composable
const { menuAccess } = useMenuAccess();
// Access Vuex store
const store = useStore();
// Computed properties for user data from Vuex state
const userRole = computed(() => store.state.userRole);
// Reference to store the currently active dropdown menu
const activeDropdown = ref(null);
// Use Vue Router's useRoute to access the current route
const route = useRoute();

// Check if the current route matches any pattern
function isActive(patterns) {
	return patterns.some((pattern) => route.path.startsWith(pattern));
}
onMounted(() => {
	// Function to handle clicks outside the dropdown menu
	const handleOutsideClick = (event) => {
		// Check if the click is outside the active dropdown and not on a .btn
		if (
			activeDropdown.value &&
			!activeDropdown.value.contains(event.target) &&
			!event.target.closest(
				".btn,.select2,.app-header,.app-hero-header,.app-body"
			) &&
			!event.target.closest("input, select, textarea")
		) {
			// Close the dropdown if the click is outside and not on a .btn
			$(activeDropdown.value)
				.find(".treeview-menu")
				.slideUp(300, function () {
					$(this).removeClass("menu-open");
				});
			$(activeDropdown.value).removeClass("active");
			activeDropdown.value = null;
		}
	};

	// Add event listener for clicks outside the dropdown
	document.addEventListener("click", handleOutsideClick);

	// Sidebar menu toggle script
	$.sidebarMenu = function (menu) {
		var animationSpeed = 300;
		$(menu).on("click", "li a", function (e) {
			var $this = $(this);
			var checkElement = $this.next();

			if (checkElement.is(".treeview-menu") && checkElement.is(":visible")) {
				// Hide the currently visible dropdown menu
				checkElement.slideUp(animationSpeed, function () {
					checkElement.removeClass("menu-open");
				});
				checkElement.parent("li").removeClass("active");
				activeDropdown.value = null;
			} else if (
				checkElement.is(".treeview-menu") &&
				!checkElement.is(":visible")
			) {
				// Hide other open dropdown menus
				var parent = $this.parents("ul").first();
				var ul = parent.find("ul:visible").slideUp(animationSpeed);
				ul.removeClass("menu-open");

				// Show the clicked dropdown menu
				var parent_li = $this.parent("li");
				checkElement.slideDown(animationSpeed, function () {
					checkElement.addClass("menu-open");
					parent.find("li.active").removeClass("active");
					parent_li.addClass("active");
				});
				activeDropdown.value = $this.parent("li")[0];
			}

			// Prevent the default action if a dropdown menu is being toggled
			if (checkElement.is(".treeview-menu")) {
				e.preventDefault();
			}
		});
	};

	// Initialize the sidebar menu script
	$.sidebarMenu($(".sidebar-menu"));

	// Custom sidebar toggle functionality
	$("#toggle-sidebar").on("click", function () {
		$(".page-wrapper").toggleClass("toggled");
	});

	// Pin/unpin sidebar functionality
	$("#pin-sidebar").on("click", function () {
		if ($(".page-wrapper").hasClass("pinned")) {
			$(".page-wrapper").removeClass("pinned");
			$("#sidebar").unbind("hover");
		} else {
			$(".page-wrapper").addClass("pinned");
			$("#sidebar").hover(
				function () {
					$(".page-wrapper").addClass("sidebar-hovered");
				},
				function () {
					$(".page-wrapper").removeClass("sidebar-hovered");
				}
			);
		}
	});

	// Add hover effect to the sidebar when pinned
	$(function () {
		$(".page-wrapper").hasClass("pinned");
		$("#sidebar").hover(
			function () {
				$(".page-wrapper").addClass("sidebar-hovered");
			},
			function () {
				$(".page-wrapper").removeClass("sidebar-hovered");
			}
		);
	});

	// Close sidebar on overlay click
	$("#overlay").on("click", function () {
		$(".page-wrapper").toggleClass("toggled");
	});

	// Handle window resize to adjust sidebar state
	$(window).resize(function () {
		if ($(window).width() <= 768) {
			$(".page-wrapper").removeClass("pinned");
		}
	});

	$(window).resize(function () {
		if ($(window).width() >= 768) {
			$(".page-wrapper").removeClass("toggled");
		}
	});

	// Remove the event listener when the component is unmounted
	onBeforeUnmount(() => {
		document.removeEventListener("click", handleOutsideClick);
	});
});
const lastPage = ref(""); // Track the last visited page

// Watch route changes to track where the user came from
watch(() => route.path, (newPath, oldPath) => {
    if (oldPath === "/salespos") {
        lastPage.value = "salespos";
    } else if (oldPath === "/saleslist") {
        lastPage.value = "saleslist";
    }
});

const isPOSActive = computed(() => {
    return route.path === "/salespos" || 
           (route.path.startsWith("/salesreceipt") && lastPage.value === "salespos") ||
           (route.path.startsWith("/salesinvoice") && lastPage.value === "salespos");
});

const isSalesListActive = computed(() => {
    return route.path === "/saleslist" || 
           (route.path.startsWith("/salesreceipt") && lastPage.value === "saleslist") ||
           (route.path.startsWith("/salesinvoice") && lastPage.value === "saleslist");
});
</script>

<template>
	<!-- Sidebar wrapper start -->
	<nav id="sidebar" class="sidebar-wrapper">
		<!-- Sidebar profile starts -->
		<div class="shop-profile">
			<p class="mb-1 fw-bold text-success">{{ userRole }}</p>
			<!-- <p class="m-0">{{ userRole }}</p> -->
		</div>
		<!-- Sidebar profile ends -->

		<!-- Sidebar menu starts -->
		<div class="sidebarMenuScroll">
			<ul class="sidebar-menu">
				<li :class="{ 'active current-page': isActive(['/home']) }">
					<router-link to="/home">
						<i class="fa fa-home"></i>
						<span class="menu-text">Home</span>
					</router-link>
				</li>
				<li
					v-if="menuAccess.dashboard"
					:class="{ 'active current-page': isActive(['/dashboard']) }"
				>
					<router-link to="/dashboard">
						<i class="fa fa-dashboard"></i>
						<span class="menu-text">Dashboard</span>
					</router-link>
				</li>
				<li
					v-if="
						menuAccess.roleList ||
						menuAccess.userList ||
						menuAccess.clientList
					"
					class="treeview"
					:class="{
						'active current-page': isActive(['/role', '/user', '/client']),
					}"
				>
					<a href="#!">
						<i class="fa fa-users"></i>
						<span class="menu-text">Manage Accounts</span>
					</a>
					<ul class="treeview-menu">
						<li v-if="menuAccess.roleList">
							<RouterLink
								to="/rolelist"
								:class="{
									'active-sub': isActive([
										'/rolelist',
										'/rolecreate',
										'/roleupdate',
										'/rolepermission',
									]),
								}"
							>
								Roles
							</RouterLink>
						</li>
						<li v-if="menuAccess.userList">
							<RouterLink
								to="/userlist"
								:class="{
									'active-sub': isActive([
										'/userlist',
										'/usercreate',
										'/userupdate',
										'/usershow',
									]),
								}"
							>
								Users</RouterLink
							>
						</li>
					</ul>
				</li>
				<li
					v-if="menuAccess.categoryList ||
						menuAccess.productList ||
						menuAccess.brandList ||
						menuAccess.measurementList ||
						menuAccess.supplierList ||
						menuAccess.stockList ||
						menuAccess.stockLevel"
					class="treeview"
					:class="{
						'active current-page': isActive([
							'/category',
							'/product',
							'/brand',
							'/measurement',
							'/supplier',
							'/stock',
							'/stocklevel'
						]),
					}"
				>
					<a href="#!">
						<i class="fa fa-coins"></i>
						<span class="menu-text">Manage Stock</span>
					</a>
					<ul class="treeview-menu">
						<li v-if="menuAccess.categoryList">
							<RouterLink
								to="/categorylist"
								:class="{
									'active-sub': isActive([
										'/categorylist',
										'/categorycreate',
										'/categoryupdate',
									]),
								}"
							>
								Categories
							</RouterLink>
						</li>
						<li v-if="menuAccess.productList">
							<RouterLink
								to="/productlist"
								:class="{
									'active-sub': isActive([
										'/productlist',
										'/productcreate',
										'/productupdate',
									]),
								}"
							>
								Products 
							</RouterLink>
						</li>
						<li v-if="menuAccess.brandList">
							<RouterLink
								to="/brandlist"
								:class="{
									'active-sub': isActive([
										'/brandlist',
										'/brandcreate',
										'/brandupdate',
									]),
								}"
							>
								Brands
							</RouterLink>
						</li>
						<li v-if="menuAccess.measurementList">
							<RouterLink
								to="/measurementlist"
								:class="{
									'active-sub': isActive([
										'/measurementlist',
										'/measurementcreate',
										'/measurementupdate',
										'/measurementshow'
									]),
								}"
							>
								Measurement
							</RouterLink>
						</li>
						<li v-if="menuAccess.supplierList">
							<RouterLink
								to="/supplierlist"
								:class="{
									'active-sub': isActive([
										'/supplierlist',
										'/suppliercreate',
										'/supplierupdate'
									]),
								}"
							>
								Suppliers
							</RouterLink>
						</li>
						<li v-if="menuAccess.stockList">
							<RouterLink
								to="/stocklist"
								:class="{
									'active-sub': isActive([
										'/stocklist',
										'/stockcreate',
										'/stockupdate',
										'/stockshow'
									]),
								}"
							>
								Stock Records
							</RouterLink>
						</li>
						<li v-if="menuAccess.stockLevel">
							<RouterLink
								to="/stocklevel"
								:class="{
									'active-sub': isActive([
										'/stocklevel',
									]),
								}"
							>
								Stock Levels
							</RouterLink>
						</li>
					</ul>
				</li>
				<li
					v-if="menuAccess.salesPOS || menuAccess.salesList"
					class="treeview"
					:class="{
						'active current-page': isActive(['/sales']),
					}"
				>
					<a href="#!">
						<i class="fa fa-exchange-alt"></i>
						<span class="menu-text">Manage Sales</span>
					</a>
					<ul class="treeview-menu">
						<li v-if="menuAccess.salesPOS">
							<RouterLink
								to="/salespos"
								:class="{ 'active-sub': isPOSActive }"
							>
								POS
							</RouterLink>
						</li>
						<li v-if="menuAccess.salesList">
							<RouterLink
								to="/saleslist"
								:class="{ 'active-sub': isSalesListActive }"
							>
								Sales
							</RouterLink>
						</li>
					</ul>

				</li>
				<li
					v-if="
					menuAccess.reportDailySalesList ||
					menuAccess.reportWeeklySalesList ||
					menuAccess.reportMothlySalesList || 
					menuAccess.reportInterimSalesList ||
					menuAccess.reportTopSalesList
					"
					class="treeview"
					:class="{
						'active current-page': isActive(['/report']),
					}"
				>
					<a href="#!">
						<i class="fa fa-chart-line"></i>
						<span class="menu-text">Reports</span>
					</a>
					<ul class="treeview-menu">
						<li
							v-if="
								menuAccess.reportDailySalesList || menuAccess.reportWeeklySalesList || menuAccess.reportMonthlySalesList || menuAccess.reportInterimSalesListt || menuAccess.reportTopSalesList
							" 
						 	:class="{'active': isActive(['/reportdailysaleslist', '/reportweeklysaleslist', '/reportmonthlysaleslist', '/reportinterimsaleslist', '/reporttopsaleslist'])}">
							<a href="#!">
								Sales Reports
								<i class="bi bi-chevron-right"></i>
							</a>
							<ul class="treeview-menu">
								<li v-if="menuAccess.reportDailySalesList">
									<RouterLink
										to="/reportdailysaleslist"
										:class="{
											'active-sub': isActive(['/reportdailysaleslist']),
										}"
									>
										Daily Sales
									</RouterLink>
								</li>
								<li v-if="menuAccess.reportWeeklySalesList">
									<RouterLink
										to="/reportweeklysaleslist"
										:class="{
											'active-sub': isActive(['/reportweeklysaleslist']),
										}"
									>
										Weekly Sales
									</RouterLink>
								</li>
								<li v-if="menuAccess.reportMonthlySalesList">
									<RouterLink
										to="/reportmonthlysaleslist"
										:class="{
											'active-sub': isActive(['/reportmonthlysaleslist']),
										}"
									>
										Monthly Sales
									</RouterLink>
								</li>
								<li v-if="menuAccess.reportInterimSalesList">
									<RouterLink
										to="/reportinterimsaleslist"
										:class="{
											'active-sub': isActive(['/reportinterimsaleslist']),
										}"
									>
										Interim Sales
									</RouterLink>
								</li>
								<li v-if="menuAccess.reportTopSalesList">
									<RouterLink
										to="/reporttopsaleslist"
										:class="{
											'active-sub': isActive(['/reporttopsaleslist']),
										}"
									>
										Top-Selling Items
									</RouterLink>
								</li>								
							</ul>
						</li>						
						<li
							v-if="
								menuAccess.reportRevenueList || menuAccess.reportProfitLossList || menuAccess.reportExpenseList || menuAccess.reportSupplierList || menuAccess.reportTaxList
							" 
						 	:class="{'active': isActive(['/reportrevenuelist', '/reportprofitlosslist', '/reportexpenselist', '/reportsupplierlist', '/reporttaxlist'])}">
							<a href="#!">
								Financial Reports
								<i class="bi bi-chevron-right"></i>
							</a>
							<ul class="treeview-menu">
								<li v-if="menuAccess.reportRevenueList">
									<RouterLink
										to="/reportrevenuelist"
										:class="{
											'active-sub': isActive(['/reportrevenuelist']),
										}"
									>
										Revenue
									</RouterLink>
								</li>
								<li v-if="menuAccess.reportProfitLossList">
									<RouterLink
										to="/reportprofitlosslist"
										:class="{
											'active-sub': isActive(['/reportprofitlosslist']),
										}"
									>
										Profit & Loss
									</RouterLink>
								</li>
								<li v-if="menuAccess.reportExpenseList">
									<RouterLink
										to="/reportexpenselist"
										:class="{
											'active-sub': isActive(['/reportexpenselist']),
										}"
									>
										Expense Report
									</RouterLink>
								</li>
								<li v-if="menuAccess.reportSupplierList">
									<RouterLink
										to="/reportsupplierlist"
										:class="{
											'active-sub': isActive(['/reportsupplierlist']),
										}"
									>
										Supplier Report
									</RouterLink>
								</li>
								<li v-if="menuAccess.reportTaxList">
									<RouterLink
										to="/reporttaxlist"
										:class="{
											'active-sub': isActive(['/reporttaxlist']),
										}"
									>
										Tax Report
									</RouterLink>
								</li>
							</ul>
						</li>
						<li
							v-if="
								menuAccess.reportStockList || menuAccess.reportStockBalanceList || menuAccess.reportLowStockAlertList || menuAccess.reportStockValuationList || menuAccess.reportStockAgingList
							" 
						 	:class="{'active': isActive(['/reportstocklist', '/reportstockbalancelist', '/reportlowstockalertlist', '/reportstockvaluationlist', '/reportstockaginglist'])}">
							<a href="#!">
								Inventory Reports
								<i class="bi bi-chevron-right"></i>
							</a>
							<ul class="treeview-menu">
								<li v-if="menuAccess.reportStockList">
									<RouterLink
										to="/reportstocklist"
										:class="{
											'active-sub': isActive(['/reportstocklist']),
										}"
									>
										Stock
									</RouterLink>
								</li>
								
								<li v-if="menuAccess.reportStockBalanceList">
									<RouterLink
										to="/reportstockbalancelist"
										:class="{
											'active-sub': isActive(['/reportstockbalancelist']),
										}"
									>
										Stock Balance
									</RouterLink>
								</li>
								
								<li v-if="menuAccess.reportLowStockAlertList">
									<RouterLink
										to="/reportlowstockalertlist"
										:class="{
											'active-sub': isActive(['/reportlowstockalertlist']),
										}"
									>
										Low Stock Alerts
									</RouterLink>
								</li>
								
								<li v-if="menuAccess.reportStockValuationList">
									<RouterLink
										to="/reportstockvaluationlist"
										:class="{
											'active-sub': isActive(['/reportstockvaluationlist']),
										}"
									>
										Inventory Valuation
									</RouterLink>
								</li>
								
								<li v-if="menuAccess.reportStockAgingList">
									<RouterLink
										to="/reportstockaginglist"
										:class="{
											'active-sub': isActive(['/reportstockaginglist']),
										}"
									>
										Stock Aging
									</RouterLink>
								</li>
							</ul>
						</li>
					</ul>
				</li>
				<li
					v-if="
						menuAccess.businessInfoSettings ||
						menuAccess.appearanceSettings ||
						menuAccess.emailSettings ||
						menuAccess.securitySettings
					"
					class="treeview"
					:class="{
						'active current-page': isActive([
							'/businessinfosettings',
							'/appearancesettings',
							'/emailsettings',
							'/securitysettings',
						]),
					}"
				>
					<a href="#!">
						<i class="fa fa-gears"></i>
						<span class="menu-text">Manage Settings</span>
					</a>
					<ul class="treeview-menu">
						<li v-if="menuAccess.businessInfoSettings">
							<RouterLink
								to="/businessinfosettings"
								:class="{ 'active-sub': isActive(['/businessinfosettings']) }"
							>
							Business Info</RouterLink
							>
						</li>
						<li v-if="menuAccess.appearanceSettings">
							<RouterLink
								to="/appearancesettings"
								:class="{ 'active-sub': isActive(['/appearancesettings']) }"
							>
							Appearance</RouterLink
							>
						</li>
						<li v-if="menuAccess.emailSettings">
							<RouterLink
								to="/emailsettings"
								:class="{ 'active-sub': isActive(['/emailsettings']) }"
							>
								Email</RouterLink
							>
						</li>
						<li v-if="menuAccess.securitySettings">
							<RouterLink
								to="/securitysettings"
								:class="{ 'active-sub': isActive(['/securitysettings']) }"
							>
								Security</RouterLink
							>
						</li>
					</ul>
				</li>
				<li
					v-if="menuAccess.trash"
					:class="{ 'active current-page': isActive(['/trash']) }"
				>
					<router-link to="/trash">
						<i class="fa fa-trash"></i>
						<span class="menu-text">Trash</span>
					</router-link>
				</li>
				<li
					v-if="
						menuAccess.applicationLogs
					"
					class="treeview"
					:class="{
						'active current-page': isActive([
							'/applicationlog'
						]),
					}"
				>
					<a href="#!">
						<i class="fa fa-shield"></i>
						<span class="menu-text">Audit Logs</span>
					</a>
					<ul class="treeview-menu">
						<li v-if="menuAccess.applicationLogs">
							<RouterLink
								to="/applicationloglist"
								:class="{
									'active-sub': isActive([
										'/applicationloglist',
										'/applicationlogshow',
									]),
								}"
							>
								Application
							</RouterLink>
						</li>
					</ul>
				</li>
			</ul>
		</div>
		<!-- Sidebar menu ends -->
	</nav>
	<!-- Sidebar wrapper end -->
</template>

<style scoped></style>
