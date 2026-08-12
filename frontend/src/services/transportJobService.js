import api from "@/api/axios";

export const convertEstimate = (id) =>
    api.post(`/estimates/${id}/convert`);