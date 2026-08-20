// The stages a job moves through, in order. This mirrors TRANSITIONS in
// TransportJobService — the server is what enforces the rule, this copy only
// keeps the dropdown from offering a move that would be rejected.
const NEXT = {
  draft: ['confirmed'],
  confirmed: ['assigned'],
  assigned: ['in_transit'],
  in_transit: ['delivered'],
  delivered: ['completed'],
  completed: [],
}

export const nextStatuses = (status) => NEXT[status] ?? []

// The badge is capitalised in CSS, so the underscore is all that needs undoing.
export const statusLabel = (status) => String(status || '').replace(/_/g, ' ')
