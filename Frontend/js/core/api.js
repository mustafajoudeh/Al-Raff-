import { request } from "./http.js";

// Keep all HTTP calls behind one small API facade.
export const api = {
  get: (path) => request(path),
  post: (path, body = {}) => request(path, { method: "POST", body }),
  put: (path, body = {}) => request(path, { method: "PUT", body }),
  patch: (path, body = {}) => request(path, { method: "PATCH", body }),
  delete: (path) => request(path, { method: "DELETE" }),
};
