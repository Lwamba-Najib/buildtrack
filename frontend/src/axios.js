// Importing the axios library for making HTTP requests
import axios from "axios";
import { backendBaseUrl } from "./config"; // Import the backend base URL

// Creating a new instance of axios with default configuration
const instance = axios.create({
    baseURL: backendBaseUrl + "/api", // Setting the base URL for all the axios requests
    withCredentials: true, // Ensure cookies are included with requests
});

// Function to fetch CSRF token
export const fetchCsrfToken = async () => {
    try {
        await instance.get("/sanctum/csrf-cookie");
    } catch (error) { 
        console.error("Failed to fetch CSRF token:", error);
        throw error;
    }
};


export default instance;
