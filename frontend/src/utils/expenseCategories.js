// Mirrors App\Enums\ExpenseCategory — the server rejects anything outside this
// list, so the two have to be changed together. Order is the order shown in
// the dropdown.
export const EXPENSE_CATEGORIES = [
  { value: 'fuel', label: 'Fuel' },
  { value: 'repair', label: 'Repair' },
  { value: 'toll', label: 'Toll' },
  { value: 'parking', label: 'Parking' },
  { value: 'fine', label: 'Fine' },
  { value: 'driver_advance', label: 'Driver advance' },
  { value: 'accommodation', label: 'Accommodation' },
  { value: 'loading_unloading', label: 'Loading / unloading' },
  { value: 'permit', label: 'Permit' },
  { value: 'miscellaneous', label: 'Miscellaneous' },
]

export const categoryLabel = (value) =>
  EXPENSE_CATEGORIES.find((category) => category.value === value)?.label || value
