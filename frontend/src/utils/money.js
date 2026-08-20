// Money arrives from the API as strings like "70000.00", so coerce before
// formatting. Used everywhere a figure is displayed.
export const money = (value) => Number(value || 0).toLocaleString()
