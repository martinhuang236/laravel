<template>
  <div class="redirect-container">
    <div v-if="loading" class="loading-state">
      <div class="countdown-wrapper">
        <div class="pulse-ring"></div>
        <svg class="countdown-svg" viewBox="0 0 100 100">
          <defs>
            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#4CAF50" />
              <stop offset="100%" stop-color="#45a049" />
            </linearGradient>
          </defs>
          <circle
            class="countdown-circle-bg"
            cx="50"
            cy="50"
            r="45"
            fill="none"
            stroke="#e6e6e6"
            stroke-width="5"
          />
          <circle
            class="countdown-circle"
            cx="50"
            cy="50"
            r="45"
            fill="none"
            stroke="url(#gradient)"
            stroke-width="5"
            :style="{
              strokeDasharray: circumference,
              strokeDashoffset: dashOffset,
            }"
          />
        </svg>
        <div class="countdown-content">
          <div class="countdown-number">{{ countdownNumber }}</div>
          <div class="countdown-label">seconds</div>
        </div>
      </div>
      <p class="redirect-text">
        <span class="redirect-text-animated"
          >Redirecting you to the website</span
        >
        <span class="dots"> <span>.</span><span>.</span><span>.</span> </span>
      </p>
    </div>
    <div v-if="error" class="error-state">
      <div class="error-icon">🔗</div>
      <h2>Link Expired</h2>
      <p>{{ error }}</p>
      <router-link to="/" class="home-button"> Back to Home </router-link>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "RedirectPage",
  data() {
    return {
      loading: true,
      error: null,
      countdownNumber: 3,
      circumference: 2 * Math.PI * 45,
      dashOffset: 0,
    };
  },
  methods: {
    startCountdown() {
      const startTime = Date.now();
      const duration = this.countdownNumber * 1000;

      const animate = () => {
        const currentTime = Date.now();
        const elapsed = currentTime - startTime;
        const remaining = Math.max(duration - elapsed, 0);

        // Update countdown number
        this.countdownNumber = Math.ceil(remaining / 1000);

        // Update circle animation
        const progress = remaining / duration;
        this.dashOffset = this.circumference * progress;

        if (remaining > 0) {
          requestAnimationFrame(animate);
        }
      };

      animate();
    },
  },
  async created() {
    const shortCode = this.$route.params.shortCode;
    this.startCountdown(); // Start the countdown animation

    try {
      const response = await axios.get(`/u?code=${shortCode}`);
      if (response.data && response.data.original_url) {
        setTimeout(() => {
          window.location.href = response.data.original_url;
        }, this.countdownNumber * 1000);
      } else {
        this.error = "Invalid link";
        this.loading = false;
      }
    } catch (error) {
      this.error =
        error.response?.data?.message || "This link has expired or is invalid";
      this.loading = false;
    }
  },
};
</script>

<style scoped>
.redirect-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #f5f5f5;
}

.loading-state {
  text-align: center;
}

.countdown-wrapper {
  position: relative;
  width: 150px;
  height: 150px;
  margin: 0 auto 20px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.pulse-ring {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  animation: pulse 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
  box-shadow: 0 0 20px rgba(76, 175, 80, 0.3);
}

@keyframes pulse {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.3);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 15px rgba(76, 175, 80, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
  }
}

.countdown-svg {
  transform: rotate(-90deg);
  width: 100%;
  height: 100%;
  filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.1));
}

.countdown-circle {
  transition: stroke-dashoffset 0.1s linear;
  filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.2));
}

.countdown-content {
  position: absolute;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  animation: fadeIn 0.5s ease-out;
}

.countdown-number {
  font-size: 48px;
  font-weight: bold;
  background: linear-gradient(45deg, #4caf50, #45a049);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  text-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
  animation: scaleNumber 1s infinite;
}

.countdown-label {
  font-size: 14px;
  color: #666;
  margin-top: -5px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

@keyframes scaleNumber {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.redirect-text {
  color: #666;
  font-size: 1.2em;
  margin-top: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 4px;
}

.redirect-text-animated {
  background: linear-gradient(45deg, #4caf50, #45a049);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 500;
}

.dots span {
  opacity: 0;
  animation: dots 1.4s infinite;
  font-size: 1.4em;
  line-height: 0;
  color: #4caf50;
}

.dots span:nth-child(2) {
  animation-delay: 0.2s;
}

.dots span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes dots {
  0%,
  20% {
    opacity: 0;
    transform: translateY(0);
  }
  50% {
    opacity: 1;
    transform: translateY(-2px);
  }
  80%,
  100% {
    opacity: 0;
    transform: translateY(0);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.countdown-circle-bg {
  stroke-linecap: round;
  opacity: 0.2;
}

.countdown-circle {
  stroke-linecap: round;
  transition: stroke-dashoffset 0.1s linear;
  filter: drop-shadow(0px 2px 4px rgba(0, 0, 0, 0.2));
}

.error-state {
  text-align: center;
  padding: 40px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.error-icon {
  font-size: 48px;
  margin-bottom: 20px;
}

h2 {
  color: #333;
  margin-bottom: 16px;
}

p {
  color: #666;
  margin-bottom: 24px;
}

.home-button {
  display: inline-block;
  padding: 12px 24px;
  background-color: #4caf50;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  transition: background-color 0.3s;
}

.home-button:hover {
  background-color: #45a049;
}
</style>
