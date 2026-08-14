import api from "@/api/axios";

export const getEstimates = () => api.get("/estimates");

export const createEstimate = (data) => api.post("/estimates", data);
