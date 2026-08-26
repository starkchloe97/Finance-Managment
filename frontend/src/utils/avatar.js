const TONES = ['#2563eb', '#7c3aed', '#db2777', '#0891b2', '#059669', '#d97706', '#dc2626']

export const avatarTone = (name = '') =>
  TONES[[...name].reduce((sum, char) => sum + char.charCodeAt(0), 0) % TONES.length]

export const avatarStyle = (name = '') => ({ background: avatarTone(name) })

export const initialOf = (name = '') => (name.trim().charAt(0) || '?').toUpperCase()