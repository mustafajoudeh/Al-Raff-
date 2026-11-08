import { api } from "./api.js";

export const auth = {
  async login(email, password) {
    const response = await api.post("/auth/login", { email, password });
    return response?.data ?? null;
  },
  async me() {
    const response = await api.get("/auth/me");
    return response?.data ?? null;
  },
  async logout() {
    return api.post("/auth/logout", {});
  },
};
