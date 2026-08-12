import api from "@/api/axios";

export const getEstimates = () => api.get("/estimates");

export const getEstimate = (id) => api.get(`/estimates/${id}`);

export const createEstimate = (data) => api.post("/estimates", data);

export const updateEstimate = (id, data) =>
    api.put(`/estimates/${id}`, data);

export const deleteEstimate = (id) =>
    api.delete(`/estimates/${id}`);