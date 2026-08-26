const TONES = ['blue', 'violet', 'cyan', 'amber', 'rose', 'green']

export const toneFor = (key = '') =>
  TONES[[...String(key)].reduce((sum, char) => sum + char.charCodeAt(0), 0) % TONES.length]