import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "/api",
  withCredentials: true,
});

const loadStoredToken = () => {
  try {
    const existingToken = localStorage.getItem("auth_token");

    if (existingToken) {
      api.defaults.headers.common.Authorization = `Bearer ${existingToken}`;
    }
  } catch (error) {
    console.warn("Token load failed:", error);
  }
};

export const setAuthToken = (token: string | null) => {
  if (token) {
    localStorage.setItem("auth_token", token);
    api.defaults.headers.common.Authorization = `Bearer ${token}`;
  } else {
    localStorage.removeItem("auth_token");
    delete api.defaults.headers.common.Authorization;
  }
};

loadStoredToken();

export default api;
