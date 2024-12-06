<template>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <h1>My Short Links</h1>
      <div class="header-actions">
        <button class="btn-primary" @click="goHome">Home</button>
        <button class="btn-primary" @click="logout">Logout</button>
      </div>
    </div>
    <!-- 添加支付表单 -->
    <div v-if="showPaymentForm" class="payment-form">
      <div id="card-element"></div>
      <div id="card-errors" role="alert"></div>
      <button
        class="btn-primary"
        @click="processPayment"
        :disabled="processing"
      >
        {{ processing ? "Processing..." : "Pay Now" }}
      </button>
    </div>

    <div class="dashboard-content">
      <div class="table-container">
        <table class="links-table">
          <thead>
            <tr>
              <th>Short Code</th>
              <th>Original URL</th>
              <th>Expiration</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="link in links" :key="link.id">
              <td class="short-code-cell">
                <a :href="link.original_url" target="_blank" class="short-code">
                  {{ link.short_code }}
                </a>
              </td>
              <td class="url-cell">
                <div v-if="editingId === link.id" class="edit-form">
                  <input
                    v-model="editUrl"
                    type="url"
                    class="edit-input"
                    placeholder="Enter new URL"
                  />
                  <div class="edit-actions">
                    <button @click="saveEdit(link.id)" class="edit-button save">
                      <span class="icon">✓</span>
                    </button>
                    <button @click="cancelEdit()" class="edit-button cancel">
                      <span class="icon">✕</span>
                    </button>
                  </div>
                </div>
                <div v-else class="url-text">{{ link.original_url }}</div>
              </td>
              <td class="expiration-cell">
                <span
                  :class="[
                    'status-badge',
                    link.is_permanent ? 'permanent' : 'temporary',
                  ]"
                >
                  {{ link.is_permanent ? "Permanent" : link.expires_at }}
                </span>
              </td>
              <td class="actions-cell">
                <div class="actions">
                  <button
                    @click="copyLink(link.short_code)"
                    class="action-button copy"
                  >
                    <span class="icon">📋</span> Copy
                  </button>
                  <button @click="edit(link.id)" class="action-button edit">
                    <span class="icon">✏️</span> Edit
                  </button>
                  <button
                    v-if="!link.is_permanent"
                    @click="buy(link.id)"
                    class="action-button buy"
                  >
                    <span class="icon">💎</span> Buy
                  </button>
                  <button
                    @click="deleteLink(link.id)"
                    class="action-button delete"
                  >
                    <span class="icon">🗑️</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import copy from "copy-to-clipboard";
import { loadStripe } from "@stripe/stripe-js";

export default {
  name: "DashboardPage",
  data() {
    return {
      editingId: null,
      editingUrl: "",
      links: [],
      stripePromise: null,
      showPaymentForm: false,
      processing: false,
      cardElement: null,
      currentPaymentId: null,
    };
  },
  async created() {
    try {
      // 初始化 Stripe
      this.stripePromise = loadStripe(process.env.VUE_APP_STRIPE_PUBLIC);
      await this.fetchLinks();
    } catch (error) {
      console.error(error);
      alert("Failed to fetch links. Please login.");
      this.$router.push("/login");
    }
  },
  methods: {
    // 编辑
    edit(id) {
      const link = this.links.find((l) => l.id === id);
      if (link) {
        this.editingId = id;
        this.editUrl = link.original_url;
      }
    },
    // 取消编辑
    cancelEdit() {
      this.editingId = null;
      this.editUrl = "";
    },
    // 保存编辑
    async saveEdit(id) {
      if (!this.editUrl) {
        alert("Please enter a valid URL");
        return;
      }
      try {
        await axios.put(`/links/${id}`, {
          original_url: this.editUrl,
        });
        const link = this.links.find((l) => l.id === id);
        if (link) {
          link.original_url = this.editUrl;
        }
        this.cancelEdit();
      } catch (error) {
        console.error(error);
        alert("Failed to update URL");
      }
    },
    // 购买
    async buy(id) {
      this.currentPaymentId = id;
      this.showPaymentForm = true;

      // 初始化Stripe Elements
      const stripe = await this.stripePromise;
      const elements = stripe.elements();
      this.cardElement = elements.create("card");

      // 等待DOM更新后挂载卡片元素
      this.$nextTick(() => {
        this.cardElement.mount("#card-element");

        // 添加错误监听
        this.cardElement.on("change", ({ error }) => {
          const displayError = document.getElementById("card-errors");
          if (error) {
            displayError.textContent = error.message;
          } else {
            displayError.textContent = "";
          }
        });
      });
    },

    // 处理支付
    async processPayment() {
      if (!this.cardElement) {
        return;
      }

      this.processing = true;
      try {
        const stripe = await this.stripePromise;
        const response = await axios.post(
          `/purchase/${this.currentPaymentId}`,
          {
            linkId: this.currentPaymentId,
          }
        );

        const { clientSecret } = response.data;

        const result = await stripe.confirmCardPayment(clientSecret, {
          payment_method: {
            card: this.cardElement,
          },
        });

        if (result?.error) {
          alert(result.error.message);
        } else {
          if (result.paymentIntent.status === "succeeded") {
            // 开始轮询检查支付状态
            let attempts = 0;
            const maxAttempts = 10; // 最多检查10次
            const checkPaymentStatus = async () => {
              try {
                const statusResponse = await axios.get(
                  `/purchase/status/${this.currentPaymentId}`
                );
                console.log("Payment status:", statusResponse.data);

                if (statusResponse.data.status === "paid") {
                  alert("Payment successful!");
                  this.showPaymentForm = false;
                  this.cardElement.destroy();
                  this.cardElement = null;
                  await this.fetchLinks();
                } else if (attempts < maxAttempts) {
                  attempts++;
                  // 每2秒检查一次，最多检查20秒
                  setTimeout(checkPaymentStatus, 2000);
                } else {
                  // 超过最大尝试次数
                  alert(
                    "Payment status check timeout. Please contact support."
                  );
                }
              } catch (error) {
                console.error("Error checking payment status:", error);
                if (attempts < maxAttempts) {
                  attempts++;
                  setTimeout(checkPaymentStatus, 2000);
                } else {
                  alert(
                    "Error checking payment status. Please contact support."
                  );
                }
              }
            };

            // 开始检查支付状态
            checkPaymentStatus();
          }
        }
      } catch (error) {
        console.error("Payment Error:", error);
        alert("Payment failed. Please try again.");
      } finally {
        this.processing = false;
      }
    },
    // 获取链接列表
    async fetchLinks() {
      const response = await axios.get("/links");
      this.links = response.data;
    },
    copyLink(code) {
      const url = new URL(window.location.href);
      const domain = url.protocol + "//" + url.host;
      copy(`${domain}/${code}`);
    },
    async deleteLink(id) {
      if (!confirm("Are you sure you want to delete this link?")) return;
      try {
        await axios.delete(`/links/${id}`);
        this.links = this.links.filter((link) => link.id !== id);
        alert("Link deleted.");
      } catch (error) {
        console.error(error);
        alert("Failed to delete link.");
      }
    },
    async logout() {
      try {
        await axios.post("/logout");
        localStorage.removeItem("token");
        alert("Logged out successfully.");
        this.$router.push("/login");
      } catch (error) {
        console.error(error);
        alert("Logout failed. Please try again.");
      }
    },
    // 返回首页
    goHome() {
      this.$router.push("/");
    },
  },
};
</script>

<style scoped>
.dashboard-container {
  padding: 30px;
  max-width: 1200px;
  margin: 0 auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

h1 {
  color: #333;
  font-size: 28px;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 15px;
}

.back-link,
.logout-button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.3s;
}

.back-link {
  background-color: #f8f9fa;
  color: #4caf50;
}

.back-link:hover {
  background-color: #e9ecef;
}

.logout-button {
  background-color: #dc3545;
  color: white;
  border: none;
  cursor: pointer;
}

.logout-button:hover {
  background-color: #c82333;
}

.dashboard-content {
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.table-container {
  overflow-x: auto;
}

.links-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.links-table th {
  background-color: #f8f9fa;
  color: #495057;
  font-weight: 600;
  text-align: left;
  padding: 16px;
  border-bottom: 2px solid #dee2e6;
}

.links-table td {
  padding: 16px;
  border-bottom: 1px solid #dee2e6;
  vertical-align: middle;
}

.short-code-cell {
  width: 120px;
}

.short-code {
  color: #4caf50;
  text-decoration: none;
  font-weight: 500;
}

.short-code:hover {
  text-decoration: underline;
}

.url-cell {
  min-width: 200px;
  max-width: 400px;
}

.url-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.edit-form {
  display: flex;
  gap: 10px;
  align-items: center;
}

.edit-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  transition: border-color 0.3s;
}

.edit-input:focus {
  outline: none;
  border-color: #4caf50;
}

.edit-actions {
  display: flex;
  gap: 4px;
}

.edit-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
}

.edit-button.save {
  background-color: #4caf50;
  color: white;
}

.edit-button.save:hover {
  background-color: #45a049;
}

.edit-button.cancel {
  background-color: #f44336;
  color: white;
}

.edit-button.cancel:hover {
  background-color: #d32f2f;
}

.expiration-cell {
  width: 180px;
  white-space: nowrap;
}

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
}

.status-badge.permanent {
  background-color: #4caf50;
  color: white;
}

.status-badge.temporary {
  background-color: #ff9800;
  color: white;
}

.actions-cell {
  width: 250px;
}

.actions {
  display: flex;
  gap: 8px;
}

.action-button {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.action-button.copy {
  background-color: #e3f2fd;
  color: #1976d2;
}

.action-button.copy:hover {
  background-color: #bbdefb;
}

.action-button.edit {
  background-color: #f3e5f5;
  color: #7b1fa2;
}

.action-button.edit:hover {
  background-color: #e1bee7;
}

.action-button.buy {
  background-color: #e8f5e9;
  color: #2e7d32;
}

.action-button.buy:hover {
  background-color: #c8e6c9;
}

.action-button.delete {
  background-color: #ffebee;
  color: #c62828;
  padding: 6px;
}

.action-button.delete:hover {
  background-color: #ffcdd2;
}

.icon {
  font-size: 16px;
}

.payment-form {
  margin: 20px auto;
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 4px;
  max-width: 500px;
}

#card-element {
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-bottom: 16px;
  background: white;
}

#card-errors {
  color: #dc3545;
  margin-bottom: 16px;
  min-height: 20px;
}

.btn-primary {
  background-color: #4caf50;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.3s;
}

.btn-primary:hover {
  background-color: #45a049;
}

.btn-primary:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}
</style>
