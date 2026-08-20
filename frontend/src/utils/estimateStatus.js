// The estimate lifecycle, mirroring the DB enum on estimates.status. The
// server is the source of truth — this only drives labels and badge classes.
export const ESTIMATE_STATUSES = [
  { value: 'draft', label: 'Draft' },
  { value: 'sent', label: 'Sent' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'expired', label: 'Expired' },
]

export const estimateStatusLabel = (status) =>
  ESTIMATE_STATUSES.find((item) => item.value === status)?.label || status

// .status-* badge classes are defined in style.css. Draft=neutral, Sent=info,
// Accepted=success, Rejected=danger, Expired=warning.
export const estimateStatusClass = (status) => {
  const map = {
    draft: 'status-draft',
    sent: 'status-info',
    accepted: 'status-accepted',
    rejected: 'status-danger',
    expired: 'status-warning',
  }
  return map[status] || 'status-draft'
}
