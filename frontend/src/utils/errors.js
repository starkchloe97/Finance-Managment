export const getErrorMessage = (error, fallback = 'Please try again.') => {
  const validationErrors = error?.response?.data?.errors
  const firstValidationMessage = validationErrors && Object.values(validationErrors).flat()[0]

  if (typeof firstValidationMessage === 'string') return firstValidationMessage

  const serverMessage = error?.response?.data?.message
  if (typeof serverMessage === 'string' && serverMessage.trim()) return serverMessage

  if (!error?.response) {
    return 'Could not connect to the server. Check your connection and try again.'
  }

  return fallback
}
