<template>
  <div class="home-container">
    <div class="content-box">
      <h1>URL Shortener</h1>
      <p class="subtitle">Create short and memorable links in seconds</p>

      <div v-if="isAuthenticated" class="authenticated-content">
        <div class="nav-links">
          <router-link to="/dashboard" class="dashboard-link">
            <span class="icon">📊</span> My Dashboard
          </router-link>
        </div>

        <div class="url-form">
          <input
            v-model="originalUrl"
            placeholder="Enter your long URL here"
            class="url-input"
          />
          <button
            @click="shortenUrl"
            :disabled="loading"
            class="shorten-button"
          >
            {{ loading ? "Processing..." : "Shorten URL" }}
          </button>
        </div>

        <div v-if="shortUrl" class="result-box">
          <p class="success-text">Your shortened URL is ready!</p>
          <div class="short-url-box">
            <a :href="shortUrl" target="_blank" class="short-url">{{
              shortUrl
            }}</a>
          </div>
        </div>
      </div>

      <div v-else class="auth-prompt">
        <p>Get started by logging in to your account</p>
        <div class="auth-buttons">
          <router-link to="/login" class="auth-button login">Login</router-link>
          <router-link to="/register" class="auth-button register"
            >Register</router-link
          >
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import confetti from "canvas-confetti";

export default {
  name: "HomePage",
  data() {
    return {
      originalUrl: "",
      shortUrl: "",
      loading: false,
    };
  },
  computed: {
    isAuthenticated() {
      return !!localStorage.getItem("token");
    },
  },
  methods: {
    triggerConfetti() {
      confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 },
      });

      confetti({
        particleCount: 50,
        angle: 60,
        spread: 55,
        origin: { x: 0 },
      });

      confetti({
        particleCount: 50,
        angle: 120,
        spread: 55,
        origin: { x: 1 },
      });
    },
    async shortenUrl() {
      if (!this.originalUrl) {
        alert("Please enter a valid URL.");
        return;
      }
      this.loading = true;
      try {
        const response = await axios.post("/links", {
          original_url: this.originalUrl,
        });
        const url = new URL(window.location.href);
        const domain = url.protocol + "//" + url.host;
        this.shortUrl = `${domain}/${response.data.short_url}`;
        this.triggerConfetti();
      } catch (error) {
        console.error(error);
        alert("Failed to shorten URL.");
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.home-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f5f5f5;
  padding: 20px;
}

.content-box {
  background: white;
  padding: 40px;
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 600px;
  text-align: center;
}

h1 {
  margin: 0 0 10px;
  color: #333;
  font-size: 32px;
}

.subtitle {
  color: #666;
  margin-bottom: 30px;
  font-size: 16px;
}

.nav-links {
  margin-bottom: 30px;
}

.dashboard-link {
  display: inline-flex;
  align-items: center;
  padding: 8px 16px;
  background-color: #f8f9fa;
  color: #4caf50;
  text-decoration: none;
  border-radius: 6px;
  transition: background-color 0.3s;
}

.dashboard-link:hover {
  background-color: #e9ecef;
}

.icon {
  margin-right: 8px;
}

.url-form {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.url-input {
  flex: 1;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.3s;
}

.url-input:focus {
  outline: none;
  border-color: #4caf50;
}

.shorten-button {
  background-color: #4caf50;
  color: white;
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.3s;
  white-space: nowrap;
}

.shorten-button:hover:not(:disabled) {
  background-color: #45a049;
}

.shorten-button:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}

.result-box {
  margin-top: 30px;
  padding: 20px;
  background-color: #f8f9fa;
  border-radius: 6px;
}

.success-text {
  color: #4caf50;
  margin-bottom: 10px;
  font-weight: 500;
}

.short-url-box {
  padding: 10px;
  background-color: white;
  border-radius: 4px;
  border: 1px solid #ddd;
}

.short-url {
  color: #4caf50;
  text-decoration: none;
  font-weight: 500;
}

.short-url:hover {
  text-decoration: underline;
}

.auth-prompt {
  margin-top: 20px;
}

.auth-buttons {
  display: flex;
  gap: 15px;
  justify-content: center;
  margin-top: 20px;
}

.auth-button {
  padding: 12px 30px;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s;
}

.auth-button.login {
  background-color: #4caf50;
  color: white;
}

.auth-button.login:hover {
  background-color: #45a049;
}

.auth-button.register {
  background-color: white;
  color: #4caf50;
  border: 1px solid #4caf50;
}

.auth-button.register:hover {
  background-color: #f8f9fa;
}
</style>
