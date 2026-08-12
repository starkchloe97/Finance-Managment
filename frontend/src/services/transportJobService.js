import api from "@/api/axios";

export const getJobs = () => api.get("/jobs");

export const getJob = (id) => api.get(`/jobs/${id}`);

export const convertEstimate = (id) =>
    api.post(`/estimates/${id}/convert`);
