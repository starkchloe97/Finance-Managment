import api from "@/api/axios";

export const addExpense = (jobId, data) =>
    api.post(`/jobs/${jobId}/expenses`, data);

export const updateExpense = (id, data) =>
    api.put(`/expenses/${id}`, data);

export const deleteExpense = (id) =>
    api.delete(`/expenses/${id}`);
