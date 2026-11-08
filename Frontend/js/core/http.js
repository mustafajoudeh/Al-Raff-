import { API_BASE_URL } from "./config.js";

export class ApiError extends Error {
  constructor(message, status = 0, errors = []) {
    super(message || "حدث خطأ أثناء الاتصال بالخادم.");
    this.name = "ApiError";
    this.status = status;
    this.errors = Array.isArray(errors) ? errors : [];
  }
}

export async function request(path, options = {}) {
  const method = String(options.method || "GET").toUpperCase();
  const headers = new Headers(options.headers || {});
  headers.set("Accept", "application/json");

  const config = {
    method,
    headers,
    credentials: "include", // PHP Session cookie
    cache: "no-store",
  };

  if (options.body !== undefined) {
    headers.set("Content-Type", "application/json; charset=utf-8");
    config.body = JSON.stringify(options.body);
  }

  let response;
  try {
    response = await fetch(
      `${API_BASE_URL}${path.startsWith("/") ? path : `/${path}`}`,
      config,
    );
  } catch {
    throw new ApiError(
      "تعذر الاتصال بالخادم. تأكد من تشغيل Apache وصحة عنوان الـ API.",
      0,
    );
  }

  const raw = await response.text();
  let payload = null;
  if (raw.trim()) {
    try {
      payload = JSON.parse(raw);
    } catch {
      throw new ApiError(
        "استجابة الخادم ليست JSON صالحاً. راجع API وملف .htaccess.",
        response.status,
      );
    }
  }

  if (!response.ok || payload?.success === false) {
    const errors = Array.isArray(payload?.errors) ? payload.errors : [];
    const message =
      [payload?.message, ...errors].filter(Boolean).join("\n") ||
      `تعذر تنفيذ العملية (${response.status}).`;
    throw new ApiError(message, response.status, errors);
  }

  return payload ?? { success: true, data: null, message: "OK", errors: [] };
}
