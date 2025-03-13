// store.js
import { createStore } from "vuex"; // Vuex's createStore function to create a store
import router from "./router"; // Importing router to control routing in actions
import axios from "@/axios"; // Adjust the path as necessary

// Creating a Vuex store
export default createStore({
    // State object contains all the reactive data that we want to share across components
    state: {
        // Initialize isLoggedIn from localStorage to persist login status across page refreshes
        isLoggedIn: !!localStorage.getItem("token"),
        // State for storing user details
        userName: "",
        userRole: localStorage.getItem("userRole") || "",
        twoFAData: null, // 2FA-related data
    },
    // Mutations are functions that directly mutate the state.
    // Each mutation handler gets the entire state tree as the first argument.
    mutations: {
        // Mutation to set isLoggedIn to true
        WhenIn(state) {
            state.isLoggedIn = true;
        },
        // Mutation to set isLoggedIn to false
        WhenOut(state) {
            state.isLoggedIn = false;
        },
        // Mutation to set user data (name and role)
        setUserData(state, { name, role_name }) {
            state.userName = name;
            state.userRole = role_name;
            // Persist userRole and userType to localStorage
            localStorage.setItem('userRole', role_name);
        },
        resetUserData(state) {
            state.userName = "";
            state.userRole = "";
            // Remove userRole and userType from localStorage
            localStorage.removeItem('userRole');
        },
        // mutation to store 2FA data
        setTwoFAData(state, data) {
            state.twoFAData = data;
        },
        // mutation to clear 2FA data
        clearTwoFAData(state) {
            state.twoFAData = null;
        },
    },
    // Actions are functions that cause side effects and can involve
    // asynchronous operations. Actions can also commit mutations.
    actions: {
        // Login action that commits WhenIn mutation
        login({ commit, dispatch }) {
            commit("WhenIn");
            dispatch("fetchUserData"); // Fetch the latest user data after login
        },
        // Logout action that commits WhenOut mutation, removes token from local storage and dispatches navigateToLogin action
        logout({ commit, dispatch }) {
            return new Promise(async (resolve) => {
                try {
                    const token = localStorage.getItem("token");
                    if (!token) {
                        throw new Error("No token found");
                    }
        
                    // Attempt to call the backend logout endpoint
                    const response = await axios.post(
                        "/logout",{},
                        { headers: { Authorization: `Bearer ${token}` } }
                    );
        
                    if (response.data.success) {
                        // Clear everything on successful backend logout
                        commit("WhenOut");
                        commit("resetUserData");
                        localStorage.removeItem("token");
                        resolve();
                    } else {
                        console.error("Backend logout failed:", response.data.message);
                        alert("An error occurred while logging out. Redirecting to the login page.");
                        dispatch("navigateToLogin");
                    }
                } catch (error) {
                    // Handle backend unreachable or other errors
                    console.error("Error during logout:", error);
                    alert("The backend is currently unreachable. You will be redirected to the login page.");
                    
                    // Clear local state and localStorage to ensure the user is logged out
                    commit("WhenOut");
                    commit("resetUserData");
                    localStorage.removeItem("token");
                    
                    // Redirect to the login page
                    dispatch("navigateToLogin");
                }
            });
        },
        // Action to navigate to login route using Vue Router
        navigateToLogin() {
            router.push({ name: "Login" });
        },
        // Action to fetch user data from API and commit setUserData mutation
        async fetchUserData({ state,commit }) {
            try {
                // If user data already exists, skip fetching
                if (state.userName && state.userRole) {
                    console.log("User data already fetched, skipping API call.");
                    return;
                }
                // Retrieve the token from local storage
                const token = localStorage.getItem("token");
                if (!token) throw new Error("No token found");

                // Send the GET request with the Authorization header
                const response = await axios.get("/loggedinuser", {
                    headers: { Authorization: `Bearer ${token}` },
                });

                // Check if the request was successful
                if (response.data.success) {
                    commit("setUserData", response.data.data.user);
                }
            } catch (error) {
                console.error("Error fetching user data:", error);

                // Check if the error response contains "Unauthenticated"
                if (error.response && error.response.data?.message === "Unauthenticated.") {
                    console.warn("Unauthenticated - Redirecting to login...");

                    // Clear local storage and redirect to login page
                    localStorage.removeItem("token");
                    commit("WhenOut"); // Ensure state reflects logged-out status
                    router.push({ name: "Login" });
                }
            }
        },
        // action to handle 2FA data
        setTwoFAData({ commit }, data) {
            commit("setTwoFAData", data);
        },
        // action to clear 2FA data
        clearTwoFAData({ commit }) {
            commit("clearTwoFAData");
        },
    },
});
