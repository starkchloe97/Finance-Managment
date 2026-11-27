import api from '@/api/axios'

export const ensureMyInvestor = async () => {
  const response = await api.post('/my-investor')

  return response.data
}
