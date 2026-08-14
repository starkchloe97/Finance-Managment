import api from "@/api/axios";

export const getSummary = () => api.get("/reports/summary");
