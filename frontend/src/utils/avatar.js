const TONES = ['#4f6df0', '#8b5cf6', '#d9468f', '#0f9bb8', '#10a37f', '#e0891b', '#e5534b']

export const avatarTone = (name = '') =>
  TONES[[...name].reduce((sum, char) => sum + char.charCodeAt(0), 0) % TONES.length]

export const avatarStyle = (name = '') => ({ background: avatarTone(name) })

export const initialOf = (name = '') => (name.trim().charAt(0) || '?').toUpperCase()