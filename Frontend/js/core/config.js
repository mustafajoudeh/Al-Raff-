// Frontend and API are sibling folders inside /Al-Raff.
// From /Al-Raff/Frontend/js/core/config.js, ../../../API resolves to /Al-Raff/API.
export const API_BASE_URL = new URL(
  "../../../API/",
  import.meta.url,
).href.replace(/\/$/, "");
export const CURRENCY = "JOD";
export const LOW_STOCK_LIMIT = 5;
