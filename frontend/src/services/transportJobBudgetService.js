import api from "@/api/axios";

export const updateBudget = (jobId, data) =>
    api.put(`/jobs/${jobId}/budget`, data);