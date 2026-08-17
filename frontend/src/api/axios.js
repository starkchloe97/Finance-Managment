import axios from "axios";

// Override with VITE_API_URL in .env when the API is not on the default port.
const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api/v1",
    headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem("token");

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

api.interceptors.response.use(
    (response) => response,

    (error) => {
        // A 401 from the login form means the credentials were wrong, not that
        // the session expired. Bouncing to /login there would reload the page
        // and throw away the message the user needs to see.
        const isLoginAttempt = error.config?.url?.includes("/auth/login");

        if (error.response?.status === 401 && !isLoginAttempt) {
            localStorage.removeItem("token");
            window.location.href = "/login";
        }

        return Promise.reject(error);
    },
);

export default api;
