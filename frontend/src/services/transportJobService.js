import api from "@/api/axios";

export const getJobs = () => api.get("/jobs");

export const getJob = (id) => api.get(`/jobs/${id}`);

export const convertEstimate = (id) =>
    api.post(`/estimates/${id}/convert`);

export const updateJobStatus = (id, status) =>
    api.patch(`/jobs/${id}/status`, { status });

export const updateJobNotes = (id, internal_notes) =>
    api.patch(`/jobs/${id}/notes`, { internal_notes });

export const getJobActivities = (id) => api.get(`/jobs/${id}/activities`);
