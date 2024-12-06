import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import axios from "axios";

// Set Axios base URL
axios.defaults.baseURL = process.env.VUE_APP_API_URL;
// console.log(process.env);

// Add request interceptor to include the token
axios.interceptors.request.use(
  (config) => {
    // Set JSON headers
    config.headers["Accept"] = "application/json";
    // config.headers["Content-Type"] = "application/json";

    const token = localStorage.getItem("token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Add response interceptor to handle common errors
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response) {
      const currentPath = router.currentRoute.value.fullPath;
      switch (error.response.status) {
        case 401:
          // Handle unauthorized error
          // Save current path for redirect after login
          router.push({
            path: "/login",
            query: { redirect: currentPath },
          });
          break;
        case 403:
          // Handle forbidden error
          console.error("Access forbidden");
          break;
        case 500:
          // Handle server error
          console.error("Server error");
          break;
      }
    }
    return Promise.reject(error);
  }
);

const app = createApp(App);

app.use(router);

// Mount axios to global properties
// app.config.globalProperties.$axios = axios;
// Also make it available through window object for non-component usage
// window.axios = axios;

app.mount("#app");
